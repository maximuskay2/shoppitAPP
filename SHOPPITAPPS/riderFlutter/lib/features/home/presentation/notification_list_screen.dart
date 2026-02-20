import 'package:flutter/material.dart';
import '../../../core/notifications/app_notification.dart';
import '../../../core/notifications/notification_service.dart';

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
      appBar: AppBar(title: const Text('Notifications')),
      body: FutureBuilder<List<AppNotification>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          }
          final notifications = snapshot.data ?? [];
          if (notifications.isEmpty) {
            return const Center(child: Text('No notifications.'));
          }
          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView.separated(
              itemCount: notifications.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, i) {
                final n = notifications[i];
                return ListTile(
                  title: Text(n.title),
                  subtitle: Text(n.body),
                  trailing: n.readAt == null
                      ? Icon(Icons.circle, color: Colors.green, size: 12)
                      : null,
                  onTap: () async {
                    if (n.readAt == null) {
                      await widget.notificationService.markUnifiedAsRead(n.id);
                      _refresh();
                    }
                  },
                );
              },
            ),
          );
        },
      ),
    );
  }
}
