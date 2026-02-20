import "package:flutter/material.dart";

import "../../home/presentation/home_shell.dart";
import "login_screen.dart";
import "../../../app/app_scope.dart";

class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  bool _loading = true;
  bool _isAuthenticated = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final token = await AppScope.of(context).tokenStorage.readToken();
    if (!mounted) return;
    setState(() {
      _isAuthenticated = token != null && token.isNotEmpty;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    return _isAuthenticated ? const HomeShell() : const LoginScreen();
  }
}
