import "package:flutter/material.dart";

import "app_scope.dart";
import "../features/onboarding/presentation/splash_screen.dart";

class App extends StatelessWidget {
  const App({super.key, required this.dependencies, required this.navigatorKey});

  final AppDependencies dependencies;
  final GlobalKey<NavigatorState> navigatorKey;

  @override
  Widget build(BuildContext context) {
    const brandColor = Color(0xFF2C9139);
    const cardRadius = 12.0;
    const buttonRadius = 50.0;

    final lightScheme = ColorScheme.fromSeed(seedColor: brandColor);
    final darkScheme = ColorScheme.fromSeed(
      seedColor: brandColor,
      brightness: Brightness.dark,
    );

    return AppScope(
      dependencies: dependencies,
      child: MaterialApp(
        title: "ShopittPlus Driver",
        navigatorKey: navigatorKey,
        themeMode: ThemeMode.system,
        theme: ThemeData(
          colorScheme: lightScheme,
          useMaterial3: true,
          fontFamily: "Roboto",
          scaffoldBackgroundColor: brandColor,
          cardTheme: CardThemeData(
            color: Colors.white,
            elevation: 2,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(cardRadius),
            ),
          ),
          filledButtonTheme: FilledButtonThemeData(
            style: FilledButton.styleFrom(
              backgroundColor: brandColor,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(buttonRadius),
              ),
              minimumSize: const Size(48, 48),
            ),
          ),
          outlinedButtonTheme: OutlinedButtonThemeData(
            style: OutlinedButton.styleFrom(
              foregroundColor: brandColor,
              side: const BorderSide(color: brandColor),
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(buttonRadius),
              ),
              minimumSize: const Size(48, 48),
            ),
          ),
          inputDecorationTheme: InputDecorationTheme(
            filled: true,
            fillColor: Colors.white,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(cardRadius),
              borderSide: BorderSide(color: Colors.grey.shade300),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(cardRadius),
              borderSide: const BorderSide(color: brandColor),
            ),
          ),
          appBarTheme: const AppBarTheme(
            backgroundColor: brandColor,
            foregroundColor: Colors.white,
            elevation: 0,
          ),
          textTheme: const TextTheme(
            titleLarge: TextStyle(fontSize: 22, fontWeight: FontWeight.w700),
            bodyLarge: TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
            bodyMedium: TextStyle(fontSize: 16, color: Color(0xFF333333)),
            labelMedium: TextStyle(fontSize: 14, color: Color(0xFF757575)),
          ),
        ),
        darkTheme: ThemeData(
          colorScheme: darkScheme,
          useMaterial3: true,
          fontFamily: "Roboto",
          scaffoldBackgroundColor: const Color(0xFF0F2416),
          cardTheme: CardThemeData(
            color: const Color(0xFF1B2B20),
            elevation: 2,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(cardRadius),
            ),
          ),
          filledButtonTheme: FilledButtonThemeData(
            style: FilledButton.styleFrom(
              backgroundColor: brandColor,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(buttonRadius),
              ),
              minimumSize: const Size(48, 48),
            ),
          ),
          outlinedButtonTheme: OutlinedButtonThemeData(
            style: OutlinedButton.styleFrom(
              foregroundColor: Colors.white,
              side: const BorderSide(color: Colors.white70),
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(buttonRadius),
              ),
              minimumSize: const Size(48, 48),
            ),
          ),
          inputDecorationTheme: InputDecorationTheme(
            filled: true,
            fillColor: const Color(0xFF1B2B20),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(cardRadius),
              borderSide: BorderSide(color: Colors.grey.shade700),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(cardRadius),
              borderSide: const BorderSide(color: brandColor),
            ),
          ),
          appBarTheme: const AppBarTheme(
            backgroundColor: Color(0xFF0F2416),
            foregroundColor: Colors.white,
            elevation: 0,
          ),
          textTheme: const TextTheme(
            titleLarge: TextStyle(fontSize: 22, fontWeight: FontWeight.w700),
            bodyLarge: TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
            bodyMedium: TextStyle(fontSize: 16, color: Colors.white70),
            labelMedium: TextStyle(fontSize: 14, color: Colors.white60),
          ),
        ),
        home: const SplashScreen(),
      ),
    );
  }
}
