import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import "../../../app/app_scope.dart";
import "../data/order_service.dart";
import "../models/order_models.dart";
import "order_detail_screen.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFF8F9FD);
const Color kSurfaceColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);
const Color kErrorColor = Color(0xFFE53935);

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key, this.initialTab = 0, this.highlightOrderId});

  final int initialTab;
  final String? highlightOrderId;

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  bool _loading = true;
  String? _error;
  bool _handledDeepLink = false;
  int _sortIndex = 0;

  List<DriverOrder> _available = [];
  List<DriverOrder> _active = [];
  List<DriverOrder> _history = [];

  // Sort labels
  static const List<String> _sortLabels = [
    "Newest First",
    "Oldest First",
    "Highest Pay",
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(
      length: 3,
      vsync: this,
      initialIndex: widget.initialTab.clamp(0, 2),
    );
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadOrders();
  }

  // -------------------------------------------------------------------------
  // LOGIC (UNCHANGED)
  // -------------------------------------------------------------------------
  Future<void> _loadOrders() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final service = OrderService(apiClient: AppScope.of(context).apiClient);
    try {
      final availableResult = await service.fetchAvailableOrders();
      final activeResult = await service.fetchActiveOrders();
      final historyResult = await service.fetchOrderHistory();

      if (!mounted) return;

      setState(() {
        _available = availableResult.data ?? [];
        _active = activeResult.data ?? [];
        _history = historyResult.data ?? [];
        _error = (!availableResult.success && availableResult.message.isNotEmpty)
            ? availableResult.message
            : null;
      });

      if (!_handledDeepLink && widget.highlightOrderId != null) {
        _handledDeepLink = true;
        final orderId = widget.highlightOrderId!;
        final match = [..._available, ..._active, ..._history]
            .where((order) => order.id == orderId)
            .toList();

        if (match.isNotEmpty) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => OrderDetailScreen(order: match.first),
              ),
            );
          });
        }
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Failed to load orders.");
    } finally {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  Future<void> _acceptOrder(String orderId) async {
    final service = OrderService(apiClient: AppScope.of(context).apiClient);
    final result = await service.acceptOrder(orderId);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(result.success ? "Order accepted" : result.message)),
    );
    _loadOrders();
  }

  Future<void> _rejectOrder(String orderId) async {
    final service = OrderService(apiClient: AppScope.of(context).apiClient);
    final result = await service.rejectOrder(orderId);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(result.success ? "Order rejected" : result.message)),
    );
    _loadOrders();
  }

  void _openOrder(DriverOrder order) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => OrderDetailScreen(order: order),
      ),
    );
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  // -------------------------------------------------------------------------
  // UI BUILD
  // -------------------------------------------------------------------------
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBackgroundColor,
      body: Column(
        children: [
          // --- 1. Custom 3D Tab Bar ---
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: kSurfaceColor,
              borderRadius: const BorderRadius.vertical(bottom: Radius.circular(24)),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.03),
                  blurRadius: 10,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            child: Container(
              height: 50,
              decoration: BoxDecoration(
                color: kBackgroundColor,
                borderRadius: BorderRadius.circular(16),
              ),
              child: TabBar(
                controller: _tabController,
                indicator: BoxDecoration(
                  color: kSurfaceColor,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.08),
                      blurRadius: 4,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                indicatorPadding: const EdgeInsets.all(4),
                labelColor: kPrimaryColor,
                unselectedLabelColor: kTextLight,
                labelStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
                tabs: const [
                  Tab(text: "Available"),
                  Tab(text: "Active"),
                  Tab(text: "History"),
                ],
              ),
            ),
          ),

          // --- 2. Tab Views ---
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator(color: kPrimaryColor))
                : _error != null
                    ? Center(child: Text(_error!, style: const TextStyle(color: kErrorColor)))
                    : TabBarView(
                        controller: _tabController,
                        children: [
                          _buildOrderList(_available, showActions: true),
                          _buildOrderList(_active),
                          _buildOrderList(_history),
                        ],
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildOrderList(List<DriverOrder> orders, {bool showActions = false}) {
    final sortedOrders = _applySort(orders);

    if (sortedOrders.isEmpty) {
      return Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.inbox_rounded, size: 64, color: kTextLight.withOpacity(0.5)),
          const SizedBox(height: 16),
          const Text(
            "No orders found",
            style: TextStyle(fontSize: 16, color: kTextLight, fontWeight: FontWeight.w600),
          ),
        ],
      );
    }

    return ListView(
      padding: const EdgeInsets.all(20),
      physics: const BouncingScrollPhysics(),
      children: [
        // Filter Section
        _buildFilterRow(orders.length),
        const SizedBox(height: 20),
        
        // Orders
        ...sortedOrders.map((order) => _build3DOrderCard(order, showActions: showActions)),
        
        // Bottom padding for FAB/Nav bar
        const SizedBox(height: 80), 
      ],
    );
  }

  // --- 3. 3D Filter Chips ---
  Widget _buildFilterRow(int count) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      physics: const BouncingScrollPhysics(),
      child: Row(
        children: [
          Text(
            "$count Orders", 
            style: const TextStyle(fontWeight: FontWeight.bold, color: kTextDark)
          ),
          const SizedBox(width: 16),
          ...List.generate(_sortLabels.length, (index) {
            final isSelected = _sortIndex == index;
            return GestureDetector(
              onTap: () => setState(() => _sortIndex = index),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                margin: const EdgeInsets.only(right: 12),
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: isSelected ? kPrimaryColor : kSurfaceColor,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: isSelected 
                    ? [BoxShadow(color: kPrimaryColor.withOpacity(0.4), blurRadius: 8, offset: const Offset(0, 4))]
                    : [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 4, offset: const Offset(0, 2))],
                ),
                child: Text(
                  _sortLabels[index],
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: isSelected ? Colors.white : kTextDark,
                  ),
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  // --- 4. The Hero 3D Order Card ---
  Widget _build3DOrderCard(DriverOrder order, {required bool showActions}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF2C3E50).withOpacity(0.08),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(24),
          onTap: () => _openOrder(order),
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header: Name + Price
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _statusPill(order.status),
                          const SizedBox(height: 8),
                          Text(
                            order.vendor.businessName,
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                              color: kTextDark,
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (order.netTotal != null)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: kPrimaryColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        "${order.netTotal!.amount}",
                        style: const TextStyle(
                          fontWeight: FontWeight.w900,
                          color: kPrimaryColor,
                          fontSize: 16,
                        ),
                      ),
                    ),
                  ],
                ),
                
                const SizedBox(height: 16),
                
                // Info Row
                Row(
                  children: [
                    const Icon(Icons.access_time_rounded, size: 16, color: kTextLight),
                    const SizedBox(width: 4),
                    Text(
                      _formatDate(order.createdAt),
                      style: const TextStyle(fontSize: 13, color: kTextLight, fontWeight: FontWeight.w500),
                    ),
                    const SizedBox(width: 16),
                    if (order.receiverName != null) ...[
                      const Icon(Icons.person_rounded, size: 16, color: kTextLight),
                      const SizedBox(width: 4),
                      Text(
                        order.receiverName!,
                        style: const TextStyle(fontSize: 13, color: kTextLight, fontWeight: FontWeight.w500),
                      ),
                    ]
                  ],
                ),

                const SizedBox(height: 20),

                // Actions
                if (showActions)
                  Row(
                    children: [
                      // Reject Button
                      Expanded(
                        child: TextButton(
                          onPressed: () => _rejectOrder(order.id),
                          style: TextButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                              side: BorderSide(color: kErrorColor.withOpacity(0.3)),
                            ),
                          ),
                          child: const Text(
                            "Decline",
                            style: TextStyle(color: kErrorColor, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      // Accept Button
                      Expanded(
                        child: Container(
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(14),
                            boxShadow: [
                              BoxShadow(
                                color: kPrimaryColor.withOpacity(0.3),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              )
                            ],
                          ),
                          child: ElevatedButton(
                            onPressed: () => _acceptOrder(order.id),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: kPrimaryColor,
                              foregroundColor: Colors.white,
                              elevation: 0,
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                            ),
                            child: const Text(
                              "Accept",
                              style: TextStyle(fontWeight: FontWeight.bold),
                            ),
                          ),
                        ),
                      ),
                    ],
                  )
                else
                  Align(
                    alignment: Alignment.centerRight,
                    child: Text(
                      "View Details →",
                      style: TextStyle(
                        color: kPrimaryColor,
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // -------------------------------------------------------------------------
  // HELPERS
  // -------------------------------------------------------------------------

  Widget _statusPill(String status) {
    Color color = kTextLight;
    Color bg = kBackgroundColor;

    if (status.contains("PENDING")) { color = Colors.orange; bg = Colors.orange.withOpacity(0.1); }
    else if (status.contains("DELIVERED")) { color = kPrimaryColor; bg = kPrimaryColor.withOpacity(0.1); }
    else if (status.contains("CANCEL")) { color = kErrorColor; bg = kErrorColor.withOpacity(0.1); }
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        status,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }

  List<DriverOrder> _applySort(List<DriverOrder> orders) {
    final sorted = [...orders];
    if (_sortIndex == 2) { // Highest Pay
      sorted.sort((a, b) {
        final aValue = a.netTotal?.amount ?? 0;
        final bValue = b.netTotal?.amount ?? 0;
        return bValue.compareTo(aValue);
      });
      return sorted;
    }

    sorted.sort((a, b) {
      final aDate = _parseOrderDate(a);
      final bDate = _parseOrderDate(b);
      return _sortIndex == 0 // Newest
          ? bDate.compareTo(aDate)
          : aDate.compareTo(bDate); // Oldest
    });
    return sorted;
  }

  DateTime _parseOrderDate(DriverOrder order) {
    if (order.createdAt == null) {
      return DateTime.fromMillisecondsSinceEpoch(0);
    }
    return DateTime.tryParse(order.createdAt!) ??
        DateTime.fromMillisecondsSinceEpoch(0);
  }

  String _formatDate(String? value) {
    if (value == null) return "Unknown Date";
    final parsed = DateTime.tryParse(value);
    if (parsed == null) return value;
    final local = parsed.toLocal();
    // Simple formatting: HH:mm · DD/MM
    return "${local.hour.toString().padLeft(2,'0')}:${local.minute.toString().padLeft(2,'0')} · ${local.day}/${local.month}";
  }
}