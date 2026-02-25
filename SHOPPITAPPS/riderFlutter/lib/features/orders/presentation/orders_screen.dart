import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import "../../../app/app_scope.dart";
import "../data/order_service.dart";
import "../models/order_models.dart";
import "order_detail_screen.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS (DARK COCKPIT)
// ---------------------------------------------------------------------------
const Color kPrimaryGreen = Color(0xFF4CE5B1); // Vivid Mint
const Color kDarkBg = Color(0xFF0F1115); // Deepest Background
const Color kSurfaceDark = Color(0xFF1F222A); // Card Background
const Color kTextWhite = Color(0xFFFFFFFF);
const Color kTextGrey = Color(0xFF9E9E9E);
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
      SnackBar(
        backgroundColor: result.success ? kPrimaryGreen : Colors.redAccent,
        content: Text(
          result.success ? "Order accepted" : result.message,
          style: TextStyle(color: result.success ? Colors.black : Colors.white),
        ),
      ),
    );
    _loadOrders();
  }

  Future<void> _rejectOrder(String orderId) async {
    final service = OrderService(apiClient: AppScope.of(context).apiClient);
    final result = await service.rejectOrder(orderId);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        backgroundColor: Colors.redAccent,
        content: Text(
          result.success ? "Order rejected" : result.message,
          style: const TextStyle(color: Colors.white),
        ),
      ),
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
      backgroundColor: kDarkBg,
      appBar: AppBar(
        backgroundColor: kDarkBg,
        elevation: 0,
        title: const Text(
          "Orders",
          style: TextStyle(color: kTextWhite, fontWeight: FontWeight.w800, fontSize: 24),
        ),
        actions: [
          IconButton(
            onPressed: _loadOrders,
            icon: Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: kSurfaceDark, shape: BoxShape.circle),
              child: const Icon(Icons.refresh, color: kTextWhite, size: 20),
            ),
          ),
          const SizedBox(width: 16),
        ],
        bottom: TabBar(
          controller: _tabController,
          // REQ: Remove border box, use only text color for indication
          indicatorColor: Colors.transparent, // Hides the bottom underline
          dividerColor: Colors.transparent, // Hides the divider line
          labelColor: kPrimaryGreen, // Active text color
          unselectedLabelColor: kTextGrey, // Inactive text color
          labelStyle: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
          unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
          overlayColor: MaterialStateProperty.all(Colors.transparent), // Removes hover/splash effect
          tabs: const [
            Tab(text: "Available"),
            Tab(text: "Active"),
            Tab(text: "History"),
          ],
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: kPrimaryGreen))
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: Colors.redAccent)))
              : TabBarView(
                  controller: _tabController,
                  children: [
                    _buildOrderList(_available, showActions: true),
                    _buildOrderList(_active),
                    _buildOrderList(_history),
                  ],
                ),
    );
  }

  Widget _buildOrderList(List<DriverOrder> orders, {bool showActions = false}) {
    final sortedOrders = _applySort(orders);

    if (sortedOrders.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.inbox_outlined, size: 64, color: kTextGrey.withOpacity(0.3)),
            const SizedBox(height: 16),
            const Text(
              "No orders found",
              style: TextStyle(fontSize: 16, color: kTextGrey, fontWeight: FontWeight.w600),
            ),
          ],
        ),
      );
    }

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 100), // Bottom padding for Nav Bar
      physics: const BouncingScrollPhysics(),
      children: [
        // Filter Section
        _buildFilterRow(orders.length),
        const SizedBox(height: 24),
        
        // Orders
        ...sortedOrders.map((order) => _buildDarkOrderCard(order, showActions: showActions)),
      ],
    );
  }

  // --- 3. Dark Filter Chips ---
  Widget _buildFilterRow(int count) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      physics: const BouncingScrollPhysics(),
      child: Row(
        children: [
          Text(
            "$count Orders", 
            style: const TextStyle(fontWeight: FontWeight.bold, color: kTextWhite)
          ),
          const SizedBox(width: 24),
          ...List.generate(_sortLabels.length, (index) {
            final isSelected = _sortIndex == index;
            return GestureDetector(
              onTap: () => setState(() => _sortIndex = index),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                margin: const EdgeInsets.only(right: 12),
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: isSelected ? kPrimaryGreen.withOpacity(0.2) : kSurfaceDark,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: isSelected ? kPrimaryGreen : Colors.transparent),
                ),
                child: Text(
                  _sortLabels[index],
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: isSelected ? kPrimaryGreen : kTextGrey,
                  ),
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  // --- 4. Dark Cockpit Order Card ---
  Widget _buildDarkOrderCard(DriverOrder order, {required bool showActions}) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: kSurfaceDark,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.white.withOpacity(0.05)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
            blurRadius: 15,
            offset: const Offset(0, 8),
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
                // Header: Status + Price
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _statusPill(order.status),
                    if (order.netTotal != null)
                      Text(
                        "${order.netTotal!.amount} ${order.netTotal!.currency}",
                        style: const TextStyle(
                          fontWeight: FontWeight.w900,
                          color: kTextWhite,
                          fontSize: 18,
                        ),
                      ),
                  ],
                ),
                
                const SizedBox(height: 16),
                
                // Vendor Name
                Text(
                  order.vendor.businessName,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: kTextWhite,
                  ),
                ),
                
                const SizedBox(height: 12),
                
                // Info Row (Date & Receiver)
                Row(
                  children: [
                    const Icon(Icons.access_time_rounded, size: 14, color: kTextGrey),
                    const SizedBox(width: 4),
                    Text(
                      _formatDate(order.createdAt),
                      style: const TextStyle(fontSize: 12, color: kTextGrey, fontWeight: FontWeight.w500),
                    ),
                    const SizedBox(width: 16),
                    if (order.receiverName != null) ...[
                      const Icon(Icons.person_rounded, size: 14, color: kTextGrey),
                      const SizedBox(width: 4),
                      Text(
                        order.receiverName!,
                        style: const TextStyle(fontSize: 12, color: kTextGrey, fontWeight: FontWeight.w500),
                      ),
                    ]
                  ],
                ),

                const SizedBox(height: 20),

                // Actions Footer
                if (showActions)
                  Row(
                    children: [
                      // Reject Button
                      Expanded(
                        child: TextButton(
                          onPressed: () => _rejectOrder(order.id),
                          style: TextButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                              side: BorderSide(color: Colors.redAccent.withOpacity(0.5)),
                            ),
                          ),
                          child: const Text(
                            "Decline",
                            style: TextStyle(color: Colors.redAccent, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      // Accept Button
                      Expanded(
                        child: ElevatedButton(
                          onPressed: () => _acceptOrder(order.id),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: kPrimaryGreen,
                            foregroundColor: Colors.black, // Dark text on bright green
                            elevation: 0,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          ),
                          child: const Text(
                            "Accept",
                            style: TextStyle(fontWeight: FontWeight.w900),
                          ),
                        ),
                      ),
                    ],
                  )
                else
                  Align(
                    alignment: Alignment.centerRight,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Text(
                          "View Details",
                          style: TextStyle(
                            color: kPrimaryGreen,
                            fontWeight: FontWeight.w700,
                            fontSize: 13,
                          ),
                        ),
                        const SizedBox(width: 4),
                        const Icon(Icons.arrow_forward_rounded, color: kPrimaryGreen, size: 16),
                      ],
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
    Color color = kTextGrey;
    Color bg = Colors.white.withOpacity(0.05);

    final s = status.toUpperCase();
    if (s.contains("PENDING")) { 
      color = Colors.orangeAccent; 
      bg = Colors.orangeAccent.withOpacity(0.1); 
    } else if (s.contains("DELIVERED") || s.contains("ACTIVE")) { 
      color = kPrimaryGreen; 
      bg = kPrimaryGreen.withOpacity(0.1); 
    } else if (s.contains("CANCEL")) { 
      color = Colors.redAccent; 
      bg = Colors.redAccent.withOpacity(0.1); 
    }
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        s,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w800,
          color: color,
          letterSpacing: 0.5,
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
    return "${local.hour.toString().padLeft(2,'0')}:${local.minute.toString().padLeft(2,'0')} · ${local.day}/${local.month}";
  }
}