import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'api_client.dart';
import 'models.dart';

class AuthController extends ChangeNotifier {
  AuthController({ApiClient? api, FlutterSecureStorage? storage})
      : _api = api ?? ApiClient(storage: storage),
        _storage = storage ?? const FlutterSecureStorage();
  final ApiClient _api;
  final FlutterSecureStorage _storage;
  User? user;
  bool loading = true;
  ApiException? error;

  bool get isAuthenticated => user != null;

  Future<void> restore() async {
    try {
      if (await _storage.read(key: 'luma_access_token') != null) {
        final response = await _api.request('/user', auth: true);
        user = User.fromJson(response['data'] as Map<String, dynamic>);
      }
    } on ApiException catch (exception) {
      error = exception;
      await _storage.delete(key: 'luma_access_token');
    } finally {
      loading = false;
      notifyListeners();
    }
  }

  Future<void> signIn(String email, String password) => _authenticate('/mobile/auth/login', {'email': email, 'password': password});

  Future<void> register(String name, String email, String password, String confirmation) => _authenticate('/mobile/auth/register', {
    'name': name, 'email': email, 'password': password, 'password_confirmation': confirmation,
  });

  Future<void> _authenticate(String path, Map<String, dynamic> body) async {
    error = null;
    final response = await _api.request(path, method: 'POST', body: {...body, 'device_name': 'luma-flutter'});
    await _storage.write(key: 'luma_access_token', value: response['token'] as String);
    user = User.fromJson(response['data'] as Map<String, dynamic>);
    notifyListeners();
  }

  Future<void> forgotPassword(String email) async {
    await _api.request('/auth/forgot-password', method: 'POST', body: {'email': email});
  }

  Future<void> signOut() async {
    try { await _api.request('/mobile/auth/logout', method: 'POST', auth: true); } finally {
      await _storage.delete(key: 'luma_access_token');
      user = null;
      notifyListeners();
    }
  }
}
