import "package:flutter/material.dart";

import "../../../app/app_scope.dart";
import "../data/support_service.dart";
import "../models/support_ticket.dart";

const Color kPrimaryColor = Color(0xFF2C9139);
const Color kBackgroundColor = Color(0xFFF8F9FD);
const Color kSurfaceColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);
const Color kErrorColor = Color(0xFFE53935);
const Color kAccentOrange = Color(0xFFFFA000);

class SupportTicketDetailScreen extends StatefulWidget {
  const SupportTicketDetailScreen({
    super.key,
    required this.ticketId,
    this.initialTicket,
  });

  final String ticketId;
  final SupportTicket? initialTicket;

  @override
  State<SupportTicketDetailScreen> createState() =>
      _SupportTicketDetailScreenState();
}

class _SupportTicketDetailScreenState extends State<SupportTicketDetailScreen> {
  SupportTicket? _ticket;
  bool _loading = true;
  String? _error;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _ticket = widget.initialTicket;
    _loadTicket();
  }

  Future<void> _loadTicket() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final service = SupportService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.fetchTicket(widget.ticketId);
      if (!mounted) return;
      setState(() {
        _ticket = result.data ?? _ticket;
        _error = result.success ? null : result.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Failed to load ticket.");
    } finally {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBackgroundColor,
      appBar: AppBar(
        backgroundColor: kBackgroundColor,
        elevation: 0,
        centerTitle: true,
        title: const Text(
          "Ticket Details",
          style: TextStyle(color: kTextDark, fontWeight: FontWeight.w800),
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: kTextDark),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: kPrimaryColor))
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: kErrorColor)))
              : _ticket == null
                  ? const Center(
                      child: Text("Ticket not found", style: TextStyle(color: kTextLight)),
                    )
                  : SafeArea(
                      child: ListView(
                        padding: const EdgeInsets.all(24),
                        children: [
                          _buildHeaderCard(_ticket!),
                          const SizedBox(height: 16),
                          _buildMessageCard(_ticket!),
                          const SizedBox(height: 16),
                          _buildAdminResponseCard(_ticket!),
                        ],
                      ),
                    ),
    );
  }

  Widget _buildHeaderCard(SupportTicket ticket) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF2C3E50).withOpacity(0.06),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _buildStatusChip(ticket.status),
              _buildPriorityChip(ticket.priority),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            ticket.subject,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: kTextDark,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            "Created: ${ticket.createdAt ?? "N/A"}",
            style: const TextStyle(color: kTextLight, fontSize: 12),
          ),
          if (ticket.resolvedAt != null) ...[
            const SizedBox(height: 4),
            Text(
              "Resolved: ${ticket.resolvedAt}",
              style: const TextStyle(color: kTextLight, fontSize: 12),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildMessageCard(SupportTicket ticket) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            "Your Message",
            style: TextStyle(fontWeight: FontWeight.bold, color: kTextDark),
          ),
          const SizedBox(height: 12),
          Text(
            ticket.message,
            style: const TextStyle(color: kTextDark, height: 1.4),
          ),
        ],
      ),
    );
  }

  Widget _buildAdminResponseCard(SupportTicket ticket) {
    final response = ticket.meta?["admin_response"]?.toString();
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            "Admin Response",
            style: TextStyle(fontWeight: FontWeight.bold, color: kTextDark),
          ),
          const SizedBox(height: 12),
          Text(
            response?.isNotEmpty == true
                ? response!
                : "No response yet. Our team will get back to you soon.",
            style: const TextStyle(color: kTextDark, height: 1.4),
          ),
        ],
      ),
    );
  }

  Widget _buildStatusChip(String status) {
    Color bg = kTextLight.withOpacity(0.1);
    Color text = kTextLight;
    final label = status.toUpperCase();

    if (label == "OPEN" || label == "NEW") {
      bg = kPrimaryColor.withOpacity(0.1);
      text = kPrimaryColor;
    } else if (label == "PENDING" || label == "IN_PROGRESS") {
      bg = kAccentOrange.withOpacity(0.1);
      text = kAccentOrange;
    } else if (label == "RESOLVED" || label == "CLOSED") {
      bg = kPrimaryColor.withOpacity(0.12);
      text = kPrimaryColor;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(8)),
      child: Text(
        label,
        style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: text),
      ),
    );
  }

  Widget _buildPriorityChip(String priority) {
    final label = priority.toUpperCase();
    final isHigh = label == "HIGH";
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: (isHigh ? kErrorColor : kPrimaryColor).withOpacity(0.12),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.bold,
          color: isHigh ? kErrorColor : kPrimaryColor,
        ),
      ),
    );
  }
}
