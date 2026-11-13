import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'config.dart';

class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode, this.fields = const {}});
  final String message;
  final int? statusCode;
  final Map<String, dynamic> fields;
  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({FlutterSecureStorage? storage}) : _storage = storage ?? const FlutterSecureStorage();
  final FlutterSecureStorage _storage;

  Future<Map<String, dynamic>> request(String path, {String method = 'GET', Map<String, dynamic>? body, bool auth = false}) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}$path');
    final headers = <String, String>{'Accept': 'application/json', 'Content-Type': 'application/json'};
    if (auth) {
      final token = await _storage.read(key: 'luma_access_token');
      if (token != null) headers['Authorization'] = 'Bearer $token';
    }
    final response = await switch (method) {
      'POST' => http.post(uri, headers: headers, body: body == null ? null : jsonEncode(body)),
      'PATCH' => http.patch(uri, headers: headers, body: body == null ? null : jsonEncode(body)),
      'PUT' => http.put(uri, headers: headers, body: body == null ? null : jsonEncode(body)),
      'DELETE' => http.delete(uri, headers: headers),
      _ => http.get(uri, headers: headers),
    };
    final payload = response.body.isEmpty ? <String, dynamic>{} : jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw ApiException(payload['message'] as String? ?? 'Something went wrong. Please try again.', statusCode: response.statusCode, fields: (payload['errors'] as Map?)?.cast<String, dynamic>() ?? {});
    }
    return payload;
  }
}
