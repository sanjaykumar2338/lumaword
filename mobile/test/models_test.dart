import 'package:flutter_test/flutter_test.dart';
import 'package:luma_mobile/core/models.dart';

void main() {
  test('parses user and membership resources', () {
    final user = User.fromJson({'id': 7, 'name': 'Ada', 'email': 'ada@example.com', 'is_admin': false, 'created_at': '2026-01-01'});
    final membership = Membership.fromJson({'plan': 'premium', 'status': 'active', 'renews_or_ends_at': null, 'billing_enabled': true});
    expect(user.name, 'Ada');
    expect(membership.plan, 'premium');
    expect(membership.billingEnabled, isTrue);
  });
}
