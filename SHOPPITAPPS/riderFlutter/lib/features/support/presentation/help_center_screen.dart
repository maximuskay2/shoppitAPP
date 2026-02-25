import "package:flutter/material.dart";
import "package:url_launcher/url_launcher.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFF8F9FD);
const Color kSurfaceColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);

class HelpCenterScreen extends StatelessWidget {
  const HelpCenterScreen({super.key});

  void _openAnswer(BuildContext context, String question, String answer) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => Container(
        decoration: const BoxDecoration(
          color: kSurfaceColor,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: EdgeInsets.only(
          left: 24,
          right: 24,
          top: 16,
          bottom: 24 + MediaQuery.of(context).viewInsets.bottom,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Drag Handle
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
            const SizedBox(height: 24),
            
            // Question Icon
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: kPrimaryColor.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.help_outline_rounded, color: kPrimaryColor, size: 28),
            ),
            const SizedBox(height: 16),
            
            // Content
            Text(
              question,
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: kTextDark,
                height: 1.2,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              answer,
              style: const TextStyle(
                fontSize: 16,
                color: kTextDark,
                height: 1.5,
              ),
            ),
            const SizedBox(height: 24),
            
            // Close Button
            SizedBox(
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
                child: const Text("Got it", style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final faqs = const [
      {
        "q": "How do I update my vehicle?",
        "a": "Go to Settings > Vehicle Manager. You can add a new vehicle or edit existing details there. Note that changes might require re-verification.",
      },
      {
        "q": "How do I request a payout?",
        "a": "Navigate to the Earnings tab, tap on 'Wallet & Payouts', and select 'Request Payout'. Ensure your bank details are verified before requesting.",
      },
      {
        "q": "What if I cannot reach the customer?",
        "a": "Try calling the customer via the in-app dialer. If there is no response after 5 minutes, use the 'Support Tickets' section to report the issue.",
      },
      {
        "q": "Why is my account offline?",
        "a": "Check if your documents are expired in the Profile section. If everything looks good, contact support for a manual review.",
      },
    ];

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
          "Help Center",
          style: TextStyle(color: kTextDark, fontWeight: FontWeight.w800),
        ),
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          physics: const BouncingScrollPhysics(),
          children: [
            // --- 1. Search Hero ---
            const Text(
              "How can we help?",
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.w900,
                color: kTextDark,
              ),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                color: kSurfaceColor,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF9EA3AE).withOpacity(0.15),
                    blurRadius: 15,
                    offset: const Offset(0, 5),
                  ),
                ],
              ),
              child: Row(
                children: [
                  const Icon(Icons.search, color: kTextLight),
                  const SizedBox(width: 12),
                  Text(
                    "Search FAQs...",
                    style: TextStyle(
                      color: kTextLight.withOpacity(0.8),
                      fontWeight: FontWeight.w500,
                      fontSize: 16,
                    ),
                  ),
                ],
              ),
            ),
            
            const SizedBox(height: 32),
            
            // --- 2. FAQ List ---
            const Text(
              "FREQUENTLY ASKED",
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w800,
                color: kTextLight,
                letterSpacing: 1.2,
              ),
            ),
            const SizedBox(height: 16),
            
            ...faqs.map((faq) => _buildFaqTile(context, faq["q"]!, faq["a"]!)),

            const SizedBox(height: 40),

            // --- 3. Contact Support CTA ---
            GestureDetector(
              onTap: () async {
                final uri = Uri.parse("mailto:contact@shopittplus.com");
                try {
                  if (await canLaunchUrl(uri)) {
                    await launchUrl(uri);
                  } else {
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text("Email: contact@shopittplus.com")),
                      );
                    }
                  }
                } catch (_) {
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text("Email: contact@shopittplus.com")),
                    );
                  }
                }
              },
              child: Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [kPrimaryColor, kPrimaryColor.withOpacity(0.8)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: kPrimaryColor.withOpacity(0.3),
                      blurRadius: 20,
                      offset: const Offset(0, 10),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.2),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.headset_mic, color: Colors.white),
                    ),
                    const SizedBox(width: 16),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            "Still need help?",
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                              fontSize: 16,
                            ),
                          ),
                          Text(
                            "Email us at contact@shopittplus.com",
                            style: TextStyle(
                              color: Colors.white70,
                              fontSize: 13,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const Icon(Icons.arrow_forward, color: Colors.white),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFaqTile(BuildContext context, String question, String answer) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF9EA3AE).withOpacity(0.1),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () => _openAnswer(context, question, answer),
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    question,
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                      color: kTextDark,
                    ),
                  ),
                ),
                const Icon(Icons.keyboard_arrow_right_rounded, color: kTextLight),
              ],
            ),
          ),
        ),
      ),
    );
  }
}