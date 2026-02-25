import 'package:flutter/material.dart';

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import '../../../core/notifications/app_notification.dart';
import '../../../core/notifications/notification_service.dart';

// ---------------------------------------------------------------------------
// THEME CONSTANTS (DARK COCKPIT)
// ---------------------------------------------------------------------------
const Color kPrimaryGreen = Color(0xFF4CE5B1); // Vivid Mint
const Color kDarkBg = Color(0xFF0F1115); // Deepest Background
const Color kSurfaceDark = Color(0xFF1F222A); // Card Background
const Color kTextWhite = Color(0xFFFFFFFF);
const Color kTextGrey = Color(0xFF9E9E9E);

class NotificationListScreen extends StatefulWidget {
  const NotificationListScreen({Key? key, required this.notificationService}) : super(key: key);
  
  final NotificationService notificationService;

  @override
  State<NotificationListScreen> createState() => _NotificationListScreenState();
}

class _NotificationListScreenState extends State<NotificationListScreen> {
  late Future<List<AppNotification>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.notificationService.fetchUnifiedNotifications();
  }

  Future<void> _refresh() async {
    setState(() {
      _future = widget.notificationService.fetchUnifiedNotifications();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kDarkBg,
      appBar: AppBar(
        backgroundColor: kDarkBg,
        elevation: 0,
        centerTitle: true,
        leading: GestureDetector(
          onTap: () => Navigator.pop(context),
          child: Container(
            margin: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: kSurfaceDark,
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10),
              ],
            ),
            child: const Icon(Icons.arrow_back, color: kTextWhite, size: 20),
          ),
        ),
        title: const Text(
          'Notifications',
          style: TextStyle(color: kTextWhite, fontWeight: FontWeight.w800),
        ),
      ),
      body: FutureBuilder<List<AppNotification>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: kPrimaryGreen),
            );
          }
          
          if (snapshot.hasError) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error_outline_rounded, color: Colors.redAccent, size: 48),
                  const SizedBox(height: 16),
                  Text(
                    'Failed to load notifications',
                    style: const TextStyle(color: kTextWhite, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '${snapshot.error}',
                    style: const TextStyle(color: kTextGrey, fontSize: 12),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: _refresh,
                    style: FilledButton.styleFrom(backgroundColor: kSurfaceDark),
                    child: const Text("Try Again"),
                  ),
                ],
              ),
            );
          }
          
          final notifications = snapshot.data ?? [];
          if (notifications.isEmpty) {
            return _buildEmptyState();
          }
          
          return RefreshIndicator(
            onRefresh: _refresh,
            color: kPrimaryGreen,
            backgroundColor: kSurfaceDark,
            child: ListView.builder(
              padding: const EdgeInsets.all(24),
              physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
              itemCount: notifications.length,
              itemBuilder: (context, i) {
                final n = notifications[i];
                return _buildNotificationCard(n);
              },
            ),
          );
        },
      ),
    );
  }

  // --- WIDGETS ---

  Widget _buildEmptyState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: kSurfaceDark,
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.notifications_off_rounded, size: 64, color: kTextGrey.withOpacity(0.5)),
            ),
            const SizedBox(height: 24),
            const Text(
              "You're all caught up!",
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: kTextWhite),
            ),
            const SizedBox(height: 8),
            const Text(
              "We'll notify you when new orders arrive or when there are updates to your account.",
              textAlign: TextAlign.center,
              style: TextStyle(color: kTextGrey, height: 1.5),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildNotificationCard(AppNotification n) {
    final bool isUnread = n.readAt == null;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: isUnread ? kPrimaryGreen.withOpacity(0.05) : kSurfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isUnread ? kPrimaryGreen.withOpacity(0.3) : Colors.white.withOpacity(0.05),
        ),
        boxShadow: [
          if (isUnread)
            BoxShadow(
              color: kPrimaryGreen.withOpacity(0.02),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () async {
            if (isUnread) {
              await widget.notificationService.markUnifiedAsRead(n.id);
              _refresh();
            }
          },
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Icon / Indicator Area
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: isUnread ? kPrimaryGreen.withOpacity(0.1) : kDarkBg,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.notifications_active_rounded,
                    color: isUnread ? kPrimaryGreen : kTextGrey,
                    size: 20,
                  ),
                ),
                const SizedBox(width: 16),
                
                // Content Area
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        n.title,
                        style: TextStyle(
                          color: kTextWhite,
                          fontWeight: isUnread ? FontWeight.w800 : FontWeight.w600,
                          fontSize: 15,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        n.body,
                        style: TextStyle(
                          color: isUnread ? kTextGrey : kTextGrey.withOpacity(0.7),
                          fontSize: 13,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ),
                ),
                
                // Unread Dot Indicator
                if (isUnread) ...[
                  const SizedBox(width: 12),
                  Container(
                    margin: const EdgeInsets.only(top: 6),
                    width: 10,
                    height: 10,
                    decoration: BoxDecoration(
                      color: kPrimaryGreen,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: kPrimaryGreen.withOpacity(0.6),
                          blurRadius: 6,
                          spreadRadius: 1,
                        ),
                      ],
                    ),
                  ),
                ]
              ],
            ),
          ),
        ),
      ),
    );
  }
}