<?php

namespace App\Providers;

use App\Contracts\DevotionalGenerator;
use App\Contracts\MediaTranscriber;
use App\Contracts\SermonAnalyzer;
use App\Contracts\SermonQuestionAnswerer;
use App\Contracts\WebPushGateway;
use App\Services\MinishlinkWebPushGateway;
use App\Services\OpenAiDevotionalGenerator;
use App\Services\OpenAiSermonQuestionAnswerer;
use App\Services\OpenAiSermonService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OpenAiSermonService::class);
        $this->app->bind(SermonAnalyzer::class, OpenAiSermonService::class);
        $this->app->bind(MediaTranscriber::class, OpenAiSermonService::class);
        $this->app->bind(SermonQuestionAnswerer::class, OpenAiSermonQuestionAnswerer::class);
        $this->app->bind(DevotionalGenerator::class, OpenAiDevotionalGenerator::class);
        $this->app->bind(WebPushGateway::class, MinishlinkWebPushGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

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
    }

    private function authenticationKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->string('email')->toString()).'|'.$request->ip()
        );
    }
}
