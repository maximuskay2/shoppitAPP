import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFF8F9FD);
const Color kSurfaceColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);

class LegalScreen extends StatelessWidget {
  const LegalScreen({super.key});

  void _openDetail(BuildContext context, String title, String body, IconData icon) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => Container(
        height: MediaQuery.of(context).size.height * 0.75, // Take up 75% height
        decoration: const BoxDecoration(
          color: kSurfaceColor,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          children: [
            // Drag Handle
            const SizedBox(height: 16),
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: kTextLight.withOpacity(0.3),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            
            // Header
            Padding(
              padding: const EdgeInsets.all(24),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: kPrimaryColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(icon, color: kPrimaryColor, size: 24),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Text(
                      title,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                        color: kTextDark,
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: BoxDecoration(
                        color: kBackgroundColor,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.close, size: 18, color: kTextDark),
                    ),
                  )
                ],
              ),
            ),
            const Divider(height: 1, color: kBackgroundColor),
            
            // Scrollable Content
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                physics: const BouncingScrollPhysics(),
                child: Text(
                  body,
                  style: const TextStyle(
                    fontSize: 16,
                    height: 1.6,
                    color: kTextDark,
                  ),
                ),
              ),
            ),
            
            // Footer Action
            Padding(
              padding: EdgeInsets.only(
                left: 24, 
                right: 24, 
                bottom: 24 + MediaQuery.of(context).viewInsets.bottom,
                top: 16
              ),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => Navigator.pop(context),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: kPrimaryColor,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: const Text("I Understand", style: TextStyle(fontWeight: FontWeight.bold)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
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
          "Legal Center",
          style: TextStyle(color: kTextDark, fontWeight: FontWeight.w800),
        ),
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          physics: const BouncingScrollPhysics(),
          children: [
            const Text(
              "Agreements & Policies",
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w900,
                color: kTextDark,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              "Please review the terms that govern your partnership with ShopittPlus.",
              style: TextStyle(fontSize: 14, color: kTextLight),
            ),
            const SizedBox(height: 32),
            
            _buildLegalTile(
              context,
              title: "Terms of Service",
              subtitle: "Rules of engagement & driver conduct",
              icon: Icons.gavel_rounded,
              color: Colors.blueAccent,
              body: "By using ShopittPlus Driver, you agree to deliver orders "
                  "safely, follow local traffic laws, and keep customer "
                  "information private. Payments are subject to payout "
                  "schedules and platform policies.\n\n"
                  "1. SAFETY FIRST\n"
                  "Drivers must adhere to all local traffic regulations.\n\n"
                  "2. PAYOUTS\n"
                  "Payouts are processed weekly. Ensure your bank details are correct.",
            ),
            const SizedBox(height: 16),
            _buildLegalTile(
              context,
              title: "Privacy Policy",
              subtitle: "How we handle your data",
              icon: Icons.shield_rounded,
              color: kPrimaryColor,
              body: "We collect driver profile, location, and delivery data to "
                  "support routing, payouts, and safety. We do not sell "
                  "personal data to third parties.\n\n"
                  "LOCATION DATA\n"
                  "We track location only when you are Online to assign nearby orders.",
            ),
            const SizedBox(height: 16),
            _buildLegalTile(
              context,
              title: "Community Guidelines",
              subtitle: "Standards for customer interaction",
              icon: Icons.people_alt_rounded,
              color: Colors.orangeAccent,
              body: "Respect customers and vendors. Zero tolerance for harassment or discrimination.",
            ),

            const SizedBox(height: 40),
            Center(
              child: Column(
                children: [
                  const Icon(Icons.lock_outline, size: 16, color: kTextLight),
                  const SizedBox(height: 8),
                  Text(
                    "Last Updated: October 2025",
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: kTextLight.withOpacity(0.6),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildLegalTile(
    BuildContext context, {
    required String title,
    required String subtitle,
    required String body,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF9EA3AE).withOpacity(0.1),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: () => _openDetail(context, title, body, icon),
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, color: color, size: 24),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: kTextDark,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        subtitle,
                        style: const TextStyle(
                          fontSize: 13,
                          color: kTextLight,
                        ),
                      ),
                    ],
                  ),
                ),
                const Icon(Icons.arrow_forward_ios_rounded, size: 16, color: kTextLight),
              ],
            ),
          ),
        ),
      ),
    );
  }
}