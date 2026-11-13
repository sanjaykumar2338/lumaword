# LUMA mobile

Flutter foundation for the LUMA Android and iOS apps.

## Local setup

Install Flutter 3.50+ and run:

```sh
flutter pub get
flutter analyze
flutter test
flutter run --dart-define=LUMA_API_BASE_URL=https://lumaword.easytechinfo.net/api/v1
```

The API base URL is configurable at build time. Tokens are stored with
`flutter_secure_storage`; no Stripe or OpenAI credentials are shipped in the
client. The current app intentionally shows membership status without starting
a native purchase flow.

## Development identifiers

The platform shells should use a temporary development identifier such as
`com.example.lumaMobile` until the owner supplies the final Android application
ID and iOS bundle identifier. Do not ship that placeholder to either store.
