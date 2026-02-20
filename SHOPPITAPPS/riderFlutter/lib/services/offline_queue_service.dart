import 'dart:convert';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

enum QueueItemType {
  locationUpdate,
  statusUpdate,
  deliveryProof,
  signature,
  sosAlert,
}

class QueueItem {
  final String id;
  final QueueItemType type;
  final Map<String, dynamic> data;
  final DateTime createdAt;
  int retryCount;

  QueueItem({
    required this.id,
    required this.type,
    required this.data,
    required this.createdAt,
    this.retryCount = 0,
  });

  Map<String, dynamic> toJson() => {
    'id': id,
    'type': type.index,
    'data': data,
    'createdAt': createdAt.toIso8601String(),
    'retryCount': retryCount,
  };

  factory QueueItem.fromJson(Map<String, dynamic> json) => QueueItem(
    id: json['id'],
    type: QueueItemType.values[json['type']],
    data: Map<String, dynamic>.from(json['data']),
    createdAt: DateTime.parse(json['createdAt']),
    retryCount: json['retryCount'] ?? 0,
  );
}

class OfflineQueueService {
  static final OfflineQueueService _instance = OfflineQueueService._internal();
  factory OfflineQueueService() => _instance;
  OfflineQueueService._internal();

  static const String _queueKey = 'offline_queue';
  static const int _maxRetries = 3;
  
  final List<QueueItem> _queue = [];
  bool _isProcessing = false;
  bool _isOnline = true;

  final ApiService _apiService = ApiService();

  /// Initialize the service and set up connectivity listener
  Future<void> initialize() async {
    await _loadQueue();
    _setupConnectivityListener();
    
    // Check initial connectivity
    final connectivityResult = await Connectivity().checkConnectivity();
    _isOnline = connectivityResult != ConnectivityResult.none;
    
    if (_isOnline) {
      processQueue();
    }
  }

  void _setupConnectivityListener() {
    Connectivity().onConnectivityChanged.listen((ConnectivityResult result) {
      final wasOffline = !_isOnline;
      _isOnline = result != ConnectivityResult.none;

      if (_isOnline && wasOffline) {
        print('Connection restored - processing offline queue');
        processQueue();
      }
    });
  }

  /// Add an item to the offline queue
  Future<void> enqueue({
    required QueueItemType type,
    required Map<String, dynamic> data,
  }) async {
    final item = QueueItem(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      type: type,
      data: data,
      createdAt: DateTime.now(),
    );

    _queue.add(item);
    await _saveQueue();

    // Try to process immediately if online
    if (_isOnline) {
      processQueue();
    }
  }

  /// Process all items in the queue
  Future<void> processQueue() async {
    if (_isProcessing || _queue.isEmpty || !_isOnline) return;

    _isProcessing = true;

    final itemsToProcess = List<QueueItem>.from(_queue);

    for (final item in itemsToProcess) {
      try {
        final success = await _processItem(item);
        
        if (success) {
          _queue.removeWhere((q) => q.id == item.id);
        } else {
          item.retryCount++;
          if (item.retryCount >= _maxRetries) {
            print('Max retries reached for item ${item.id}, removing from queue');
            _queue.removeWhere((q) => q.id == item.id);
          }
        }
      } catch (e) {
        print('Error processing queue item: $e');
        item.retryCount++;
        if (item.retryCount >= _maxRetries) {
          _queue.removeWhere((q) => q.id == item.id);
        }
      }
    }

    await _saveQueue();
    _isProcessing = false;
  }

  /// Process a single queue item
  Future<bool> _processItem(QueueItem item) async {
    switch (item.type) {
      case QueueItemType.locationUpdate:
        return await _processLocationUpdate(item.data);
      
      case QueueItemType.statusUpdate:
        return await _processStatusUpdate(item.data);
      
      case QueueItemType.deliveryProof:
        return await _processDeliveryProof(item.data);
      
      case QueueItemType.signature:
        return await _processSignature(item.data);
      
      case QueueItemType.sosAlert:
        return await _processSosAlert(item.data);
    }
  }

  Future<bool> _processLocationUpdate(Map<String, dynamic> data) async {
    try {
      await _apiService.updateLocation(
        latitude: data['latitude'],
        longitude: data['longitude'],
        timestamp: data['timestamp'],
      );
      return true;
    } catch (e) {
      print('Failed to sync location update: $e');
      return false;
    }
  }

  Future<bool> _processStatusUpdate(Map<String, dynamic> data) async {
    try {
      await _apiService.updateDeliveryStatus(
        orderId: data['orderId'],
        status: data['status'],
        notes: data['notes'],
      );
      return true;
    } catch (e) {
      print('Failed to sync status update: $e');
      return false;
    }
  }

  Future<bool> _processDeliveryProof(Map<String, dynamic> data) async {
    try {
      await _apiService.uploadDeliveryProof(
        orderId: data['orderId'],
        imagePath: data['imagePath'],
        notes: data['notes'],
      );
      return true;
    } catch (e) {
      print('Failed to sync delivery proof: $e');
      return false;
    }
  }

  Future<bool> _processSignature(Map<String, dynamic> data) async {
    try {
      await _apiService.uploadSignature(
        orderId: data['orderId'],
        signatureData: data['signatureData'],
        receiverName: data['receiverName'],
      );
      return true;
    } catch (e) {
      print('Failed to sync signature: $e');
      return false;
    }
  }

  Future<bool> _processSosAlert(Map<String, dynamic> data) async {
    try {
      await _apiService.sendSOS(
        orderId: data['orderId'],
        latitude: data['latitude'],
        longitude: data['longitude'],
      );
      return true;
    } catch (e) {
      print('Failed to sync SOS alert: $e');
      return false;
    }
  }

  /// Load queue from persistent storage
  Future<void> _loadQueue() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final queueJson = prefs.getString(_queueKey);
      
      if (queueJson != null) {
        final List<dynamic> items = json.decode(queueJson);
        _queue.clear();
        _queue.addAll(items.map((item) => QueueItem.fromJson(item)));
      }
    } catch (e) {
      print('Error loading offline queue: $e');
    }
  }

  /// Save queue to persistent storage
  Future<void> _saveQueue() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final queueJson = json.encode(_queue.map((item) => item.toJson()).toList());
      await prefs.setString(_queueKey, queueJson);
    } catch (e) {
      print('Error saving offline queue: $e');
    }
  }

  /// Get queue status
  int get queueLength => _queue.length;
  bool get isOnline => _isOnline;
  bool get hasPendingItems => _queue.isNotEmpty;

  /// Clear the queue (use with caution)
  Future<void> clearQueue() async {
    _queue.clear();
    await _saveQueue();
  }

  /// Get pending items count by type
  Map<QueueItemType, int> getPendingCountByType() {
    final counts = <QueueItemType, int>{};
    for (final type in QueueItemType.values) {
      counts[type] = _queue.where((item) => item.type == type).length;
    }
    return counts;
  }
}
