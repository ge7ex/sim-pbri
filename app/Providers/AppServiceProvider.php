<?php

namespace App\Providers;

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Policies\BookingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Booking::class, BookingPolicy::class);
    }
}
