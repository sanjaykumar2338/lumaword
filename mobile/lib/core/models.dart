class User {
  const User({required this.id, required this.name, required this.email, required this.isAdmin, required this.createdAt});
  final int id;
  final String name;
  final String email;
  final bool isAdmin;
  final String createdAt;

  factory User.fromJson(Map<String, dynamic> json) => User(
    id: json['id'] as int,
    name: json['name'] as String,
    email: json['email'] as String,
    isAdmin: json['is_admin'] as bool? ?? false,
    createdAt: json['created_at']?.toString() ?? '',
  );
}

class Membership {
  const Membership({required this.plan, required this.status, this.endsAt, required this.billingEnabled});
  final String plan;
  final String status;
  final String? endsAt;
  final bool billingEnabled;

  factory Membership.fromJson(Map<String, dynamic> json) => Membership(
    plan: json['plan'] as String? ?? 'free',
    status: json['status'] as String? ?? 'free',
    endsAt: json['renews_or_ends_at'] as String?,
    billingEnabled: json['billing_enabled'] as bool? ?? false,
  );
}

class Entitlements {
  const Entitlements({required this.membership});
  final Membership membership;

  factory Entitlements.fromJson(Map<String, dynamic> json) => Entitlements(
    membership: Membership.fromJson(json['membership'] as Map<String, dynamic>),
  );
}
