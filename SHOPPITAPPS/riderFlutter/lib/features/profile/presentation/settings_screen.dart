import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import "../../../core/storage/settings_storage.dart";
import "../../messaging/presentation/messages_screen.dart";
import "../../onboarding/presentation/document_upload_screen.dart";
import "../../support/presentation/help_center_screen.dart";
import "../../support/presentation/legal_screen.dart";
import "../../support/presentation/support_tickets_screen.dart";
import "profile_screen.dart";
import "ratings_screen.dart";
import "vehicle_manager_screen.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFF8F9FD);
const Color kSurfaceColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);
const Color kDividerColor = Color(0xFFF0F2F5);

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final _storage = SettingsStorage();
  DriverSettings _settings = DriverSettings.defaults;
  bool _loading = true;

  void _open(Widget screen) {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => screen),
    );
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    final loaded = await _storage.load();
    if (!mounted) return;
    setState(() {
      _settings = loaded;
      _loading = false;
    });
  }

  Future<void> _saveSettings(DriverSettings settings) async {
    setState(() => _settings = settings);
    await _storage.save(settings);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBackgroundColor,
      // We use a custom header instead of the default AppBar for better styling
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: kPrimaryColor))
          : SafeArea(
              child: ListView(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
                physics: const BouncingScrollPhysics(),
                children: [
                  // --- Header ---
                  const Text(
                    "Settings",
                    style: TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.w800,
                      color: kTextDark,
                      letterSpacing: -0.5,
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    "Manage your account and preferences",
                    style: TextStyle(fontSize: 16, color: kTextLight),
                  ),
                  const SizedBox(height: 32),

                  // --- Section 1: Account ---
                  _buildSectionLabel("ACCOUNT"),
                  _buildSettingsGroup(
                    children: [
                      _buildTile(
                        icon: Icons.person_rounded,
                        color: Colors.blueAccent,
                        title: "Profile",
                        subtitle: "Personal details",
                        onTap: () => _open(const ProfileScreen()),
                      ),
                      _buildDivider(),
                      _buildTile(
                        icon: Icons.description_rounded,
                        color: Colors.tealAccent,
                        title: "Documents",
                        subtitle: "Upload or update verification documents",
                        onTap: () => _open(const DocumentUploadScreen()),
                      ),
                      _buildDivider(),
                      _buildTile(
                        icon: Icons.directions_car_filled_rounded,
                        color: Colors.orangeAccent,
                        title: "Vehicle Manager",
                        subtitle: "Manage your rides",
                        onTap: () => _open(const VehicleManagerScreen()),
                      ),
                      _buildDivider(),
                      _buildTile(
                        icon: Icons.star_rounded,
                        color: Colors.amber,
                        title: "Ratings & Reviews",
                        subtitle: "See what riders say",
                        onTap: () => _open(const RatingsScreen()),
                      ),
                      _buildDivider(),
                      _buildTile(
                        icon: Icons.chat_bubble_outline_rounded,
                        color: Colors.greenAccent,
                        title: "Messages",
                        subtitle: "Chat with admin, customers, vendors",
                        onTap: () => _open(const MessagesScreen()),
                      ),
                    ],
                  ),

                  const SizedBox(height: 24),

                  // --- Section 2: Preferences ---
                  _buildSectionLabel("PREFERENCES"),
                  _buildSettingsGroup(
                    children: [
                      // Notification Switch
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
                        child: Row(
                          children: [
                            _buildIconBox(Icons.notifications_active_rounded, Colors.pinkAccent),
                            const SizedBox(width: 16),
                            const Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text("Notifications", style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16, color: kTextDark)),
                                  SizedBox(height: 2),
                                  Text("Order alerts & updates", style: TextStyle(color: kTextLight, fontSize: 13)),
                                ],
                              ),
                            ),
                            Switch(
                              value: _settings.notificationsEnabled,
                              activeColor: kPrimaryColor,
                              onChanged: (value) => _saveSettings(
                                _settings.copyWith(notificationsEnabled: value),
                              ),
                            ),
                          ],
                        ),
                      ),
                      _buildDivider(),
                      
                      // Navigation Dropdown
                      _buildDropdownTile(
                        icon: Icons.navigation_rounded,
                        color: Colors.teal,
                        label: "Navigation",
                        value: _settings.navigationPreference,
                        items: const [
                          DropdownMenuItem(value: "google_maps", child: Text("Google Maps")),
                          DropdownMenuItem(value: "in_app", child: Text("In-App (Default)")),
                        ],
                        onChanged: (val) {
                          if (val != null) _saveSettings(_settings.copyWith(navigationPreference: val));
                        },
                      ),
                      _buildDivider(),

                      // Language Dropdown
                      _buildDropdownTile(
                        icon: Icons.language_rounded,
                        color: Colors.indigoAccent,
                        label: "Language",
                        value: _settings.languageCode,
                        items: const [
                          DropdownMenuItem(value: "en", child: Text("English")),
                          DropdownMenuItem(value: "ha", child: Text("Hausa")),
                          DropdownMenuItem(value: "ig", child: Text("Igbo")),
                          DropdownMenuItem(value: "yo", child: Text("Yoruba")),
                        ],
                        onChanged: (val) {
                          if (val != null) _saveSettings(_settings.copyWith(languageCode: val));
                        },
                      ),
                    ],
                  ),

                  const SizedBox(height: 24),

                  // --- Section 3: Support ---
                  _buildSectionLabel("SUPPORT"),
                  _buildSettingsGroup(
                    children: [
                      _buildTile(
                        icon: Icons.help_center_rounded,
                        color: Colors.green,
                        title: "Help Center",
                        onTap: () => _open(const HelpCenterScreen()),
                      ),
                      _buildDivider(),
                      _buildTile(
                        icon: Icons.confirmation_number_rounded,
                        color: Colors.purpleAccent,
                        title: "Support Tickets",
                        onTap: () => _open(const SupportTicketsScreen()),
                      ),
                      _buildDivider(),
                      _buildTile(
                        icon: Icons.gavel_rounded,
                        color: kTextLight,
                        title: "Legal",
                        onTap: () => _open(const LegalScreen()),
                      ),
                    ],
                  ),
                  
                  const SizedBox(height: 40),
                  
                  Center(
                    child: Text(
                      "App Version 1.0.0",
                      style: TextStyle(color: kTextLight.withOpacity(0.5), fontSize: 12, fontWeight: FontWeight.w600),
                    ),
                  ),
                  const SizedBox(height: 80),
                ],
              ),
            ),
    );
  }

  // ---------------------------------------------------------------------------
  // HELPER WIDGETS
  // ---------------------------------------------------------------------------

  Widget _buildSectionLabel(String label) {
    return Padding(
      padding: const EdgeInsets.only(left: 8, bottom: 12),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w800,
          color: kTextLight,
          letterSpacing: 1.2,
        ),
      ),
    );
  }

  Widget _buildSettingsGroup({required List<Widget> children}) {
    return Container(
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF9EA3AE).withOpacity(0.1),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(children: children),
    );
  }

  Widget _buildIconBox(IconData icon, Color color) {
    return Container(
      width: 36,
      height: 36,
      decoration: BoxDecoration(
        color: color.withOpacity(0.15),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Icon(icon, color: color, size: 20),
    );
  }

  Widget _buildTile({
    required IconData icon,
    required Color color,
    required String title,
    String? subtitle,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        child: Row(
          children: [
            _buildIconBox(icon, color),
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
                  if (subtitle != null) ...[
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: const TextStyle(fontSize: 13, color: kTextLight),
                    ),
                  ],
                ],
              ),
            ),
            const Icon(Icons.arrow_forward_ios_rounded, size: 16, color: kTextLight),
          ],
        ),
      ),
    );
  }

  Widget _buildDropdownTile({
    required IconData icon,
    required Color color,
    required String label,
    required String value,
    required List<DropdownMenuItem<String>> items,
    required ValueChanged<String?> onChanged,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          _buildIconBox(icon, color),
          const SizedBox(width: 16),
          Expanded(
            child: DropdownButtonHideUnderline(
              child: DropdownButtonFormField<String>(
                value: value,
                items: items,
                onChanged: onChanged,
                icon: const Icon(Icons.keyboard_arrow_down_rounded, color: kTextLight),
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: kTextDark),
                decoration: InputDecoration(
                  labelText: label,
                  labelStyle: const TextStyle(color: kTextLight, fontSize: 14),
                  border: InputBorder.none,
                  contentPadding: EdgeInsets.zero,
                ),
                dropdownColor: kSurfaceColor,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDivider() {
    return Divider(height: 1, thickness: 1, color: kDividerColor, indent: 68);
  }
}