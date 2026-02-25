import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import "../../../app/app_scope.dart";
import "../data/support_service.dart";
import "../models/support_ticket.dart";
import "support_ticket_detail_screen.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFF8F9FD);
const Color kSurfaceColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);
const Color kErrorColor = Color(0xFFE53935);
const Color kAccentOrange = Color(0xFFFFA000);

class SupportTicketsScreen extends StatefulWidget {
  const SupportTicketsScreen({super.key});

  @override
  State<SupportTicketsScreen> createState() => _SupportTicketsScreenState();
}

class _SupportTicketsScreenState extends State<SupportTicketsScreen> {
  List<SupportTicket> _tickets = [];
  bool _loading = true;
  String? _error;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadTickets();
  }

  Future<void> _loadTickets() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final service = SupportService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.fetchTickets();
      if (!mounted) return;

      setState(() {
        _tickets = result.data ?? [];
        _error = result.success ? null : result.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Failed to load tickets.");
    } finally {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  void _openCreateTicket() {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const _CreateTicketSheet(),
    ).then((_) => _loadTickets());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBackgroundColor,
      appBar: AppBar(
        backgroundColor: kBackgroundColor,
        elevation: 0,
        centerTitle: true,
        leading: GestureDetector(
          onTap: () => Navigator.pop(context),
          child: Container(
            margin: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: kSurfaceColor,
              shape: BoxShape.circle,
              boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10)],
            ),
            child: const Icon(Icons.arrow_back, color: kTextDark, size: 20),
          ),
        ),
        title: const Text(
          "Support Inbox",
          style: TextStyle(color: kTextDark, fontWeight: FontWeight.w800),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: kPrimaryColor))
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: kErrorColor)))
              : SafeArea(
                  child: ListView(
                    padding: const EdgeInsets.all(24),
                    physics: const BouncingScrollPhysics(),
                    children: [
                      if (_tickets.isEmpty)
                        _buildEmptyState()
                      else
                        ..._tickets.map(_buildTicketCard),
                      
                      const SizedBox(height: 80), // Space for FAB
                    ],
                  ),
                ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _openCreateTicket,
        backgroundColor: kPrimaryColor,
        elevation: 4,
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text("New Ticket", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const SizedBox(height: 60),
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: kPrimaryColor.withOpacity(0.05),
              shape: BoxShape.circle,
            ),
            child: Icon(Icons.mark_email_unread_outlined, size: 64, color: kPrimaryColor.withOpacity(0.5)),
          ),
          const SizedBox(height: 24),
          const Text(
            "No Tickets Yet",
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: kTextDark),
          ),
          const SizedBox(height: 8),
          const Text(
            "Need help? Create a ticket to start a conversation.",
            textAlign: TextAlign.center,
            style: TextStyle(color: kTextLight),
          ),
        ],
      ),
    );
  }

  Widget _buildTicketCard(SupportTicket ticket) {
    final isClosed = ticket.status.toUpperCase() == "CLOSED";
    final isHighPriority = ticket.priority.toUpperCase() == "HIGH";

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
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
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => SupportTicketDetailScreen(
                  ticketId: ticket.id,
                  initialTicket: ticket,
                ),
              ),
            );
          },
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _buildStatusChip(ticket.status),
                    if (isHighPriority)
                      Row(
                        children: [
                          Icon(Icons.priority_high_rounded, size: 14, color: kErrorColor),
                          const SizedBox(width: 4),
                          Text(
                            "HIGH PRIORITY",
                            style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: kErrorColor),
                          ),
                        ],
                      ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  ticket.subject,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: kTextDark,
                  ),
                ),
                // If message snippet exists in your model, add it here
                // const SizedBox(height: 4),
                // Text(ticket.message, maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(color: kTextLight)),
                const SizedBox(height: 16),
                Container(height: 1, color: kBackgroundColor),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    Text(
                      "View Details",
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: isClosed ? kTextLight : kPrimaryColor,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Icon(
                      Icons.arrow_forward_rounded,
                      size: 14,
                      color: isClosed ? kTextLight : kPrimaryColor,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatusChip(String status) {
    Color bg = kTextLight.withOpacity(0.1);
    Color text = kTextLight;
    String label = status.toUpperCase();

    if (label == "OPEN" || label == "NEW") {
      bg = kPrimaryColor.withOpacity(0.1);
      text = kPrimaryColor;
    } else if (label == "PENDING") {
      bg = kAccentOrange.withOpacity(0.1);
      text = kAccentOrange;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: text),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// CREATE TICKET SHEET (Redesigned)
// ---------------------------------------------------------------------------
class _CreateTicketSheet extends StatefulWidget {
  const _CreateTicketSheet();

  @override
  State<_CreateTicketSheet> createState() => _CreateTicketSheetState();
}

class _CreateTicketSheetState extends State<_CreateTicketSheet> {
  final _subjectController = TextEditingController();
  final _messageController = TextEditingController();
  String _priority = "NORMAL";
  bool _saving = false;
  String? _error;

  @override
  void dispose() {
    _subjectController.dispose();
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_subjectController.text.trim().isEmpty ||
        _messageController.text.trim().isEmpty) {
      setState(() => _error = "Please fill in all fields.");
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    final service = SupportService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.createTicket(
        subject: _subjectController.text.trim(),
        message: _messageController.text.trim(),
        priority: _priority,
      );

      if (!mounted) return;

      if (result.success) {
        Navigator.of(context).pop();
      } else {
        setState(() => _error = result.message.isEmpty
            ? "Failed to create ticket."
            : result.message);
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Failed to create ticket.");
    } finally {
      if (!mounted) return;
      setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 24,
        bottom: 24 + MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40, height: 4,
              decoration: BoxDecoration(color: kTextLight.withOpacity(0.3), borderRadius: BorderRadius.circular(2)),
            ),
          ),
          const SizedBox(height: 24),
          const Text(
            "New Support Ticket",
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: kTextDark),
          ),
          const SizedBox(height: 24),
          
          if (_error != null)
            Container(
              padding: const EdgeInsets.all(12),
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: kErrorColor.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: [
                  const Icon(Icons.error_outline, size: 20, color: kErrorColor),
                  const SizedBox(width: 8),
                  Expanded(child: Text(_error!, style: const TextStyle(color: kErrorColor))),
                ],
              ),
            ),

          _buildInput(_subjectController, "Subject", Icons.title),
          const SizedBox(height: 16),
          _buildInput(_messageController, "Describe your issue...", Icons.description_outlined, maxLines: 4),
          const SizedBox(height: 20),
          
          const Text("Priority Level", style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: kTextLight)),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(child: _buildPriorityChip("LOW", Colors.blue)),
              const SizedBox(width: 8),
              Expanded(child: _buildPriorityChip("NORMAL", kPrimaryColor)),
              const SizedBox(width: 8),
              Expanded(child: _buildPriorityChip("HIGH", kErrorColor)),
            ],
          ),
          
          const SizedBox(height: 32),
          SizedBox(
            width: double.infinity,
            height: 56,
            child: ElevatedButton(
              onPressed: _saving ? null : _submit,
              style: ElevatedButton.styleFrom(
                backgroundColor: kPrimaryColor,
                foregroundColor: Colors.white,
                elevation: 0,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              ),
              child: _saving 
                ? const SizedBox(height: 24, width: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : const Text("Submit Ticket", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInput(TextEditingController controller, String label, IconData icon, {int maxLines = 1}) {
    return Container(
      decoration: BoxDecoration(
        color: kBackgroundColor,
        borderRadius: BorderRadius.circular(16),
      ),
      child: TextField(
        controller: controller,
        maxLines: maxLines,
        style: const TextStyle(fontWeight: FontWeight.w600, color: kTextDark),
        decoration: InputDecoration(
          labelText: label,
          alignLabelWithHint: true,
          labelStyle: const TextStyle(color: kTextLight),
          prefixIcon: maxLines == 1 ? Icon(icon, color: kTextLight) : Padding(padding: const EdgeInsets.only(bottom: 60), child: Icon(icon, color: kTextLight)),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        ),
      ),
    );
  }

  Widget _buildPriorityChip(String level, Color color) {
    final isSelected = _priority == level;
    return GestureDetector(
      onTap: () => setState(() => _priority = level),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? color.withOpacity(0.1) : kBackgroundColor,
          border: Border.all(color: isSelected ? color : Colors.transparent, width: 2),
          borderRadius: BorderRadius.circular(12),
        ),
        alignment: Alignment.center,
        child: Text(
          level,
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 12,
            color: isSelected ? color : kTextLight,
          ),
        ),
      ),
    );
  }
}