<?php

namespace App\Providers;

use App\Events\AssignmentPublished;
use App\Events\GradeSubmitted;
use App\Listeners\SendAssignmentNotification;
use App\Listeners\SendGradeNotification;
use Illuminate\Support\Facades\Event;
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
        // Pendaftaran Event & Listener Modul Minggu 10
        Event::listen(
            AssignmentPublished::class,
            SendAssignmentNotification::class
        );

        Event::listen(
            GradeSubmitted::class,
            SendGradeNotification::class
        );
    }
}
