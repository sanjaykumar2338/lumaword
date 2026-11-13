import 'package:flutter/material.dart';
import 'core/api_client.dart';
import 'core/auth_controller.dart';
import 'core/models.dart';

const ink = Color(0xFF171717);
const cream = Color(0xFFF5F1E8);
const lime = Color(0xFFC8F500);

void main() => runApp(const LumaApp());

class LumaApp extends StatefulWidget {
  const LumaApp({super.key});
  @override State<LumaApp> createState() => _LumaAppState();
}

class _LumaAppState extends State<LumaApp> {
  late final AuthController auth = AuthController()..restore();
  @override void dispose() { auth.dispose(); super.dispose(); }
  @override Widget build(BuildContext context) => AnimatedBuilder(
    animation: auth,
    builder: (_, __) => MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'LUMA',
      theme: ThemeData(useMaterial3: true, scaffoldBackgroundColor: cream, colorScheme: ColorScheme.fromSeed(seedColor: lime, brightness: Brightness.light), fontFamily: 'Arial'),
      home: auth.loading ? const SplashScreen() : auth.isAuthenticated ? HomeScreen(auth: auth) : WelcomeScreen(auth: auth),
    ),
  );
}

class BrandMark extends StatelessWidget {
  const BrandMark({super.key, this.dark = false});
  final bool dark;
  @override Widget build(BuildContext context) => Image.asset('assets/logo-mark.png', width: 84, height: 84, color: dark ? cream : null);
}

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});
  @override Widget build(BuildContext context) => const Scaffold(backgroundColor: ink, body: Center(child: Column(mainAxisSize: MainAxisSize.min, children: [BrandMark(dark: true), SizedBox(height: 18), Text('LUMA', style: TextStyle(color: cream, fontSize: 28, fontWeight: FontWeight.w800, letterSpacing: 5))])));
}

class WelcomeScreen extends StatelessWidget {
  const WelcomeScreen({required this.auth, super.key});
  final AuthController auth;
  @override Widget build(BuildContext context) => Scaffold(backgroundColor: ink, body: SafeArea(child: Padding(padding: const EdgeInsets.all(28), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [const Spacer(), const BrandMark(dark: true), const SizedBox(height: 28), const Text('Make space for a deeper faith.', style: TextStyle(color: cream, fontSize: 38, height: 1.03, fontWeight: FontWeight.w700)), const SizedBox(height: 16), const Text('Study Scripture, reflect on sermons, and build a rhythm that lasts.', style: TextStyle(color: Colors.white70, fontSize: 17, height: 1.45)), const Spacer(), FilledButton(onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => AuthScreen(auth: auth))), style: FilledButton.styleFrom(backgroundColor: lime, foregroundColor: ink, minimumSize: const Size.fromHeight(54)), child: const Text('Get started', style: TextStyle(fontWeight: FontWeight.w700))), const SizedBox(height: 12), Center(child: TextButton(onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => AuthScreen(auth: auth, login: true))), child: const Text('I already have an account', style: TextStyle(color: cream))),), const SizedBox(height: 14)])));
}

class AuthScreen extends StatefulWidget {
  const AuthScreen({required this.auth, this.login = false, super.key});
  final AuthController auth; final bool login;
  @override State<AuthScreen> createState() => _AuthScreenState();
}
class _AuthScreenState extends State<AuthScreen> {
  late bool login = widget.login; final form = GlobalKey<FormState>(); final name = TextEditingController(); final email = TextEditingController(); final password = TextEditingController(); final confirmation = TextEditingController(); bool busy = false;
  @override void dispose() { name.dispose(); email.dispose(); password.dispose(); confirmation.dispose(); super.dispose(); }
  Future<void> submit() async { if (!(form.currentState?.validate() ?? false)) return; setState(() => busy = true); try { if (login) { await widget.auth.signIn(email.text.trim(), password.text); } else { await widget.auth.register(name.text.trim(), email.text.trim(), password.text, confirmation.text); } if (mounted) Navigator.pop(context); } on ApiException catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message))); } finally { if (mounted) setState(() => busy = false); } }
  @override Widget build(BuildContext context) => Scaffold(appBar: AppBar(backgroundColor: cream, title: Text(login ? 'Welcome back' : 'Create your account')), body: SafeArea(child: Form(key: form, child: ListView(padding: const EdgeInsets.all(24), children: [Text(login ? 'Continue your journey.' : 'A thoughtful space for Scripture and reflection.', style: const TextStyle(fontSize: 30, fontWeight: FontWeight.w700)), const SizedBox(height: 30), if (!login) _field(name, 'Name', Icons.person_outline), _field(email, 'Email', Icons.mail_outline, email: true), _field(password, 'Password', Icons.lock_outline, secret: true), if (!login) _field(confirmation, 'Confirm password', Icons.lock_outline, secret: true), const SizedBox(height: 12), FilledButton(onPressed: busy ? null : submit, style: FilledButton.styleFrom(backgroundColor: ink, minimumSize: const Size.fromHeight(54)), child: Text(busy ? 'Please wait…' : login ? 'Sign in' : 'Create account')), if (login) TextButton(onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ForgotPasswordScreen(auth: widget.auth))), child: const Text('Forgot your password?')), TextButton(onPressed: () => setState(() => login = !login), child: Text(login ? 'Create a new account' : 'I already have an account'))])));
  Widget _field(TextEditingController controller, String label, IconData icon, {bool secret = false, bool email = false}) => Padding(padding: const EdgeInsets.only(bottom: 14), child: TextFormField(controller: controller, obscureText: secret, keyboardType: email ? TextInputType.emailAddress : null, validator: (value) => value == null || value.trim().isEmpty ? 'Enter your $label'.toLowerCase() : null, decoration: InputDecoration(labelText: label, prefixIcon: Icon(icon), border: const OutlineInputBorder())));
}

class ForgotPasswordScreen extends StatefulWidget { const ForgotPasswordScreen({required this.auth, super.key}); final AuthController auth; @override State<ForgotPasswordScreen> createState() => _ForgotPasswordState(); }
class _ForgotPasswordState extends State<ForgotPasswordScreen> { final email = TextEditingController(); bool sent = false, busy = false; @override void dispose(){email.dispose();super.dispose();} Future<void> send() async {setState(()=>busy=true); try {await widget.auth.forgotPassword(email.text.trim()); setState(()=>sent=true);} on ApiException catch(e){ScaffoldMessenger.of(context).showSnackBar(SnackBar(content:Text(e.message)));} finally{setState(()=>busy=false);}} @override Widget build(BuildContext context)=>Scaffold(appBar:AppBar(title:const Text('Reset password')),body:Padding(padding:const EdgeInsets.all(24),child: sent ? const Center(child:Text('If an account exists, a reset link has been sent.')) : Column(children:[const Text('Enter your email and we’ll send a reset link.',style:TextStyle(fontSize:22,fontWeight:FontWeight.w600)),const SizedBox(height:24),TextField(controller:email,keyboardType:TextInputType.emailAddress,decoration:const InputDecoration(labelText:'Email',border:OutlineInputBorder())),const SizedBox(height:16),FilledButton(onPressed:busy?null:send,child:Text(busy?'Sending…':'Send reset link'))]))); }

class HomeScreen extends StatefulWidget { const HomeScreen({required this.auth, super.key}); final AuthController auth; @override State<HomeScreen> createState()=>_HomeState(); }
class _HomeState extends State<HomeScreen> { int index=0; @override Widget build(BuildContext context){final pages=[Dashboard(auth:widget.auth),const FeaturePlaceholder(title:'Your library',subtitle:'Sermons, notes, and conversations will live here.'),ProfileScreen(auth:widget.auth)];return Scaffold(body:pages[index],bottomNavigationBar:NavigationBar(selectedIndex:index,onDestinationSelected:(value)=>setState(()=>index=value),destinations:const[NavigationDestination(icon:Icon(Icons.home_outlined),selectedIcon:Icon(Icons.home),label:'Home'),NavigationDestination(icon:Icon(Icons.menu_book_outlined),selectedIcon:Icon(Icons.menu_book),label:'Library'),NavigationDestination(icon:Icon(Icons.person_outline),selectedIcon:Icon(Icons.person),label:'Profile')]));}}

class Dashboard extends StatelessWidget { const Dashboard({required this.auth,super.key}); final AuthController auth; @override Widget build(BuildContext context)=>SafeArea(child:ListView(padding:const EdgeInsets.all(24),children:[Text('Good day, ${auth.user?.name.split(' ').first ?? 'friend'}.',style:const TextStyle(fontSize:32,fontWeight:FontWeight.w700)),const SizedBox(height:6),const Text('A little time with God can change the shape of a day.',style:TextStyle(fontSize:16)),const SizedBox(height:26),Container(padding:const EdgeInsets.all(22),decoration:BoxDecoration(color:ink,borderRadius:BorderRadius.circular(24)),child:const Column(crossAxisAlignment:CrossAxisAlignment.start,children:[Text('TODAY',style:TextStyle(color:lime,fontWeight:FontWeight.w800,letterSpacing:2)),SizedBox(height:12),Text('Start with a moment of reflection.',style:TextStyle(color:cream,fontSize:25,fontWeight:FontWeight.w700)),SizedBox(height:12),Text('Daily devotionals and Scripture tools are ready for the next integration pass.',style:TextStyle(color:Colors.white70,height:1.4))])),const SizedBox(height:22),const Text('Coming into focus',style:TextStyle(fontSize:20,fontWeight:FontWeight.w700)),const SizedBox(height:12),const FeaturePlaceholder(title:'Sermon study',subtitle:'Submit a sermon and receive notes, themes, and questions.'),const SizedBox(height:12),const FeaturePlaceholder(title:'Bible reader',subtitle:'WEB, KJV, and ASV are already available through the backend.') ])); }
}

class FeaturePlaceholder extends StatelessWidget { const FeaturePlaceholder({required this.title,required this.subtitle,super.key}); final String title,subtitle; @override Widget build(BuildContext context)=>Card(child:Padding(padding:const EdgeInsets.all(18),child:Row(children:[Container(width:44,height:44,decoration:BoxDecoration(color:lime,borderRadius:BorderRadius.circular(14)),child:const Icon(Icons.auto_awesome, color:ink)),const SizedBox(width:14),Expanded(child:Column(crossAxisAlignment:CrossAxisAlignment.start,children:[Text(title,style:const TextStyle(fontSize:17,fontWeight:FontWeight.w700)),const SizedBox(height:4),Text(subtitle,style:const TextStyle(color:Colors.black54,height:1.3))]))]))); }

class ProfileScreen extends StatelessWidget { const ProfileScreen({required this.auth,super.key}); final AuthController auth; @override Widget build(BuildContext context)=>SafeArea(child:ListView(padding:const EdgeInsets.all(24),children:[const Text('Profile',style:TextStyle(fontSize:32,fontWeight:FontWeight.w700)),const SizedBox(height:24),ListTile(contentPadding:EdgeInsets.zero,leading:CircleAvatar(backgroundColor:ink,child:Text((auth.user?.name.substring(0,1)??'L').toUpperCase(),style:const TextStyle(color:cream))),title:Text(auth.user?.name??''),subtitle:Text(auth.user?.email??'')),const SizedBox(height:14),const Card(child:ListTile(leading:Icon(Icons.stars_outlined),title:Text('Membership'),subtitle:Text('Free plan · subscription status is enforced by LUMA server'))),const SizedBox(height:12),Card(child:ListTile(leading:const Icon(Icons.feedback_outlined),title:const Text('Beta feedback'),subtitle:const Text('Share feedback in the next release')),),const SizedBox(height:22),OutlinedButton(onPressed:()=>auth.signOut(),child:const Text('Sign out'))])); }
