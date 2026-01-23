<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrap();

        // Log Logins
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            \App\Models\ActivityLog::create([
                'user_id' => $event->user->id,
                'description' => 'User logged in',
                'type' => 'success',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        // Log Logouts
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            if ($event->user) {
                \App\Models\ActivityLog::create([
                    'user_id' => $event->user->id,
                    'description' => 'User logged out',
                    'type' => 'info',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        });

        // Log Failed Logins
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class, function ($event) {
            \App\Models\ActivityLog::create([
                'description' => 'Failed login attempt for email: ' . ($event->credentials['email'] ?? 'unknown'),
                'type' => 'warning',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
        
        // Dynamic Mail Configuration
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('smtp_configurations')) {
                $defaultSmtp = \App\Models\SmtpConfiguration::where('is_default', true)->first();
                if ($defaultSmtp) {
                    $mailService = new \App\Services\MailService();
                    $mailService->setDynamicConfig($defaultSmtp);
                }
            }
        } catch (\Exception $e) {
            // Silently fail during migrations or if DB is not ready
        }
    }
}
