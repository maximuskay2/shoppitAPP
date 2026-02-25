import "package:flutter/material.dart";

import "../../../app/app_scope.dart";
import "../../../core/network/api_paths.dart";

const Color kPrimaryColor = Color(0xFF2C9139);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);

class MessagesScreen extends StatefulWidget {
  const MessagesScreen({super.key});

  @override
  State<MessagesScreen> createState() => _MessagesScreenState();
}

class _MessagesScreenState extends State<MessagesScreen> {
  List<Map<String, dynamic>> _conversations = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadConversations();
  }

  Future<void> _loadConversations() async {
    setState(() => _loading = true);
    try {
      final response = await AppScope.of(context).apiClient.dio.get(ApiPaths.driverMessaging);
      if (response.statusCode == 200 && response.data['success'] == true) {
        final data = response.data['data'];
        setState(() {
          _conversations = data is List ? List<Map<String, dynamic>>.from(data.map((e) => e as Map<String, dynamic>)) : [];
        });
      }
    } catch (_) {
      setState(() => _conversations = []);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text("Messages", style: TextStyle(fontWeight: FontWeight.w700, color: kTextDark)),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: kTextDark),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: kPrimaryColor))
          : _conversations.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.chat_bubble_outline, size: 64, color: kTextLight.withOpacity(0.5)),
                      const SizedBox(height: 16),
                      Text(
                        "No conversations yet",
                        style: TextStyle(fontSize: 16, color: kTextLight),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        "Messages from admin, customers, or vendors will appear here.",
                        textAlign: TextAlign.center,
                        style: TextStyle(fontSize: 14, color: kTextLight.withOpacity(0.8)),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadConversations,
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    itemCount: _conversations.length,
                    itemBuilder: (context, i) {
                      final c = _conversations[i];
                      final other = c['other'] as Map<String, dynamic>?;
                      final latest = c['latest_message'] as Map<String, dynamic>?;
                      return ListTile(
                        leading: CircleAvatar(
                          backgroundColor: kPrimaryColor.withOpacity(0.2),
                          child: Text(
                            (other?['name'] ?? '?').toString().substring(0, 1).toUpperCase(),
                            style: const TextStyle(color: kPrimaryColor, fontWeight: FontWeight.bold),
                          ),
                        ),
                        title: Text(
                          other?['name'] ?? 'Unknown',
                          style: const TextStyle(fontWeight: FontWeight.w600, color: kTextDark),
                        ),
                        subtitle: Text(
                          latest?['content'] ?? 'No messages yet',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(color: kTextLight, fontSize: 13),
                        ),
                        onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => ConversationScreen(
                              conversationId: c['id'] as String,
                              otherName: other?['name'] ?? 'Unknown',
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}

class ConversationScreen extends StatefulWidget {
  final String conversationId;
  final String otherName;

  const ConversationScreen({super.key, required this.conversationId, required this.otherName});

  @override
  State<ConversationScreen> createState() => _ConversationScreenState();
}

class _ConversationScreenState extends State<ConversationScreen> {
  final _controller = TextEditingController();
  List<Map<String, dynamic>> _messages = [];
  bool _loading = true;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _loadMessages();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _loadMessages() async {
    setState(() => _loading = true);
    try {
      final response = await AppScope.of(context).apiClient.dio.get(
        ApiPaths.driverMessagingMessages(widget.conversationId),
      );
      if (response.statusCode == 200 && response.data['success'] == true) {
        final data = response.data['data']?['data'];
        setState(() {
          _messages = data is List ? List<Map<String, dynamic>>.from(data.map((e) => e as Map<String, dynamic>)) : [];
        });
      }
    } catch (_) {
      setState(() => _messages = []);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _sendMessage() async {
    final text = _controller.text.trim();
    if (text.isEmpty || _sending) return;
    _controller.clear();
    setState(() => _sending = true);
    try {
      final response = await AppScope.of(context).apiClient.dio.post(
        ApiPaths.driverMessagingMessages(widget.conversationId),
        data: {'content': text},
      );
      if (response.statusCode == 201 && response.data['success'] == true) {
        setState(() {
          _messages = [..._messages, response.data['data'] as Map<String, dynamic>];
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _sending = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text(widget.otherName, style: const TextStyle(fontWeight: FontWeight.w700, color: kTextDark)),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: kTextDark),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: Column(
        children: [
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator(color: kPrimaryColor))
                : ListView.builder(
                    padding: const EdgeInsets.all(16),
                    reverse: true,
                    itemCount: _messages.length,
                    itemBuilder: (context, i) {
                      final m = _messages[_messages.length - 1 - i];
                      final isMine = m['is_mine'] == true;
                      return Align(
                        alignment: isMine ? Alignment.centerRight : Alignment.centerLeft,
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          decoration: BoxDecoration(
                            color: isMine ? kPrimaryColor.withOpacity(0.15) : Colors.grey.shade100,
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(m['content'] ?? '', style: const TextStyle(color: kTextDark, fontSize: 15)),
                              const SizedBox(height: 4),
                              Text(
                                m['sender_name'] ?? '',
                                style: TextStyle(fontSize: 11, color: kTextLight),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
          ),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: Colors.grey.shade50),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _controller,
                    decoration: InputDecoration(
                      hintText: "Type a message...",
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(24)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    ),
                    onSubmitted: (_) => _sendMessage(),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  onPressed: _sending ? null : _sendMessage,
                  icon: _sending
                      ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2))
                      : const Icon(Icons.send_rounded, color: kPrimaryColor, size: 28),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
