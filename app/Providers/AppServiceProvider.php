<?php

namespace App\Providers;

use App\Contracts\BibleStudyGenerator;
use App\Contracts\DevotionalGenerator;
use App\Contracts\MediaTranscriber;
use App\Contracts\SermonAnalyzer;
use App\Contracts\SermonQuestionAnswerer;
use App\Contracts\WebPushGateway;
use App\Models\User;
use App\Services\MinishlinkWebPushGateway;
use App\Services\OpenAiBibleStudyGenerator;
use App\Services\OpenAiDevotionalGenerator;
use App\Services\OpenAiSermonQuestionAnswerer;
use App\Services\OpenAiSermonService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Cashier::ignoreRoutes();
        $this->app->singleton(OpenAiSermonService::class);
        $this->app->bind(SermonAnalyzer::class, OpenAiSermonService::class);
        $this->app->bind(MediaTranscriber::class, OpenAiSermonService::class);
        $this->app->bind(SermonQuestionAnswerer::class, OpenAiSermonQuestionAnswerer::class);
        $this->app->bind(DevotionalGenerator::class, OpenAiDevotionalGenerator::class);
        $this->app->bind(BibleStudyGenerator::class, OpenAiBibleStudyGenerator::class);
        $this->app->bind(WebPushGateway::class, MinishlinkWebPushGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(User::class);
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::define('view-admin', fn (User $user): bool => (bool) $user->is_admin);

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($this->authenticationKey($request)));

        RateLimiter::for('registration', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($request->ip()));

        RateLimiter::for('password-reset-link', fn (Request $request): Limit => Limit::perMinute(3)
            ->by($this->authenticationKey($request)));

        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($this->authenticationKey($request)));

        RateLimiter::for('sermon-submission', fn (Request $request): Limit => Limit::perMinute(10)
            ->by(($request->user()?->id ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('sermon-processing', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(($request->user()?->id ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('youtube-connection', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(($request->user()?->id ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('youtube-transcripts', fn (): Limit => Limit::perMinute(
            (int) config('sermons.limits.youtube_requests_per_minute'),
        )->by('youtube-data-api'));

        RateLimiter::for('sermon-questions', fn (Request $request): Limit => Limit::perDay(
            (int) config('sermons.qa.daily_questions_per_user'),
        )->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('devotional-generation', fn (Request $request): Limit => Limit::perDay(
            (int) config('devotionals.daily_generation_per_user'),
        )->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('bible-study-generation', fn (Request $request): Limit => Limit::perDay(
            (int) config('bible_studies.daily_generation_per_user'),
        )->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('test-push-notification', function (Request $request): Limit {
            return Limit::perMinutes(10, 3)
                ->by((string) ($request->user()?->id ?? $request->ip()))
                ->response(function (Request $request, array $headers): JsonResponse {
                    $retryAfter = max(1, (int) ($headers['Retry-After'] ?? 1));
                    $retryMinutes = (int) ceil($retryAfter / 60);
                    $retryPeriod = $retryAfter >= 60
                        ? sprintf('%d minute%s', $retryMinutes, $retryMinutes === 1 ? '' : 's')
                        : sprintf('%d second%s', $retryAfter, $retryAfter === 1 ? '' : 's');

                    return response()->json([
                        'message' => "Too many test notifications. Please try again in {$retryPeriod}.",
                        'retry_after' => $retryAfter,
                    ], 429, $headers);
                });
        });

        RateLimiter::for('billing', fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('beta-feedback', fn (Request $request): Limit => Limit::perMinutes(10, 5)
            ->by((string) ($request->user()?->id ?? $request->ip())));
    }

    private function authenticationKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->string('email')->toString()).'|'.$request->ip()
        );
    }
}
