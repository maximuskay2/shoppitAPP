import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS (MATCHING YOUR ORIGINAL CODE)
// ---------------------------------------------------------------------------
import "../../earnings/presentation/earnings_screen.dart";
import "../../orders/presentation/orders_screen.dart";
import "../../profile/presentation/settings_screen.dart";
import "home_map_screen.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kTextLight = Color(0xFF9EA3AE);

class HomeShell extends StatefulWidget {
  const HomeShell({
    super.key, 
    this.initialIndex = 0, 
    this.highlightOrderId
  });

  final int initialIndex;
  final String? highlightOrderId;

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  late int _currentIndex;
  late final List<Widget> _screens;

  // Titles corresponding to each tab index for the Top Bar
  final List<String> _screenTitles = [
    "Dashboard", // Map
    "My Orders",
    "Earnings",
    "Profile",
  ];

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex.clamp(0, 3);
    
    // Initialize screens. IndexedStack + const constructors ensure 
    // the Map doesn't reload when switching tabs.
    _screens = [
      const HomeMapScreen(),
      OrdersScreen(
        initialTab: 0,
        highlightOrderId: widget.highlightOrderId,
      ),
      const EarningsScreen(),
      const SettingsScreen(),
    ];
  }

  void _onItemTapped(int index) {
    setState(() {
      _currentIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final primary = scheme.primary;
    final background = scheme.background;
    final surface = scheme.surface;
    final textPrimary = scheme.onSurface;
    final textMuted = isDark ? Colors.white70 : kTextLight;

    return Scaffold(
      backgroundColor: background,
      
      // --- 1. The "Clarifying" Top Bar ---
      // We use a custom PreferredSizeWidget to give it that 'floating header' feel
      appBar: PreferredSize(
        preferredSize: const Size.fromHeight(70),
        child: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [
                surface.withOpacity(isDark ? 0.88 : 0.98),
                surface.withOpacity(isDark ? 0.82 : 0.94),
              ],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: const BorderRadius.vertical(bottom: Radius.circular(24)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(isDark ? 0.35 : 0.08),
                blurRadius: 15,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              child: Row(
                children: [
                  // Dynamic Title with Animation
                  Expanded(
                    child: AnimatedSwitcher(
                      duration: const Duration(milliseconds: 300),
                      transitionBuilder: (child, animation) {
                        return FadeTransition(
                          opacity: animation,
                          child: SlideTransition(
                            position: Tween<Offset>(
                              begin: const Offset(0.0, 0.2), 
                              end: Offset.zero
                            ).animate(animation),
                            child: child,
                          ),
                        );
                      },
                      child: Text(
                        _screenTitles[_currentIndex],
                        key: ValueKey<String>(_screenTitles[_currentIndex]),
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.w800,
                          color: textPrimary,
                          letterSpacing: -0.5,
                        ),
                      ),
                    ),
                  ),
                  
                  // Notification/Profile Action
                  Container(
                    width: 44, height: 44,
                    decoration: BoxDecoration(
                      color: isDark ? Colors.black.withOpacity(0.2) : background,
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 2),
                      boxShadow: [
                         BoxShadow(
                           color: Colors.black.withOpacity(isDark ? 0.3 : 0.08), 
                           blurRadius: 5, 
                           offset: const Offset(0, 2)
                        ),
                      ],
                    ),
                    child: Stack(
                      children: [
                        Center(
                          child: Icon(
                            Icons.notifications_none_rounded,
                            color: textPrimary,
                          ),
                        ),
                        // Red Dot Indicator
                        Positioned(
                          top: 10,
                          right: 12,
                          child: Container(
                            width: 8, height: 8,
                            decoration: BoxDecoration(
                              color: Colors.red,
                              shape: BoxShape.circle,
                              border: Border.all(color: background, width: 1.5),
                            ),
                          ),
                        )
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),

      // --- 2. The Body (Map & Lists) ---
      // IndexedStack preserves state.
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),

      // --- 3. The Navigation Layout ---
      // extendBody allows the map to slide BEHIND the floating bottom bar
      extendBody: true,
      
      // --- 4. The 3D Floating Bottom Bar ---
      bottomNavigationBar: _buildCool3DNavBar(
        surface: surface,
        primary: primary,
        textMuted: textMuted,
        isDark: isDark,
      ),
    );
  }

  Widget _buildCool3DNavBar({
    required Color surface,
    required Color primary,
    required Color textMuted,
    required bool isDark,
  }) {
    return Container(
      // Float off the bottom
      padding: const EdgeInsets.fromLTRB(24, 0, 24, 34), 
      decoration: const BoxDecoration(
        color: Colors.transparent,
      ),
      child: Container(
        height: 72,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [
              surface.withOpacity(isDark ? 0.92 : 0.98),
              surface.withOpacity(isDark ? 0.85 : 0.95),
            ],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(24),
          // The Premium 3D Shadow Effect
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(isDark ? 0.5 : 0.12),
              blurRadius: 30,
              offset: const Offset(0, 10),
            ),
            // Subtle top rim light
            BoxShadow(
              color: Colors.white.withOpacity(isDark ? 0.06 : 0.8),
              blurRadius: 0,
              offset: const Offset(0, -1),
            ),
          ],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: [
            _buildNavItem(0, Icons.map_outlined, Icons.map_rounded, "Home", primary, textMuted),
            _buildNavItem(1, Icons.receipt_long_outlined, Icons.receipt_long_rounded, "Orders", primary, textMuted),
            _buildNavItem(2, Icons.account_balance_wallet_outlined, Icons.account_balance_wallet_rounded, "Earn", primary, textMuted),
            _buildNavItem(3, Icons.person_outline_rounded, Icons.person_rounded, "Profile", primary, textMuted),
          ],
        ),
      ),
    );
  }

  Widget _buildNavItem(
    int index,
    IconData iconOutlined,
    IconData iconFilled,
    String label,
    Color primary,
    Color textMuted,
  ) {
    final bool isSelected = _currentIndex == index;

    return GestureDetector(
      onTap: () => _onItemTapped(index),
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOutBack,
        // When selected, pill expands. When not, it's just the icon.
        padding: EdgeInsets.symmetric(
          horizontal: isSelected ? 16 : 10, 
          vertical: 10
        ),
        decoration: BoxDecoration(
          color: isSelected ? primary.withOpacity(0.14) : Colors.transparent,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            Icon(
              isSelected ? iconFilled : iconOutlined,
              color: isSelected ? primary : textMuted,
              size: 26,
            ),
            
            // Animated Text Reveal
            AnimatedSize(
              duration: const Duration(milliseconds: 300),
              curve: Curves.easeInOut,
              child: SizedBox(
                width: isSelected ? null : 0,
                child: Padding(
                  padding: const EdgeInsets.only(left: 8),
                  child: Text(
                    label,
                    style: TextStyle(
                      color: primary,
                      fontWeight: FontWeight.w700,
                      fontSize: 14,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.clip,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}