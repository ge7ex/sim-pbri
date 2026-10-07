<?php

namespace App\Providers;

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Policies\BookingPolicy;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\Scenario\Policies\CoursePolicy;
use App\Modules\Scenario\Policies\ScenarioPolicy;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\SimResource\Policies\SimResourcePolicy;
use App\Modules\Simulator\Models\SimulatorType;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Policies\SimulatorTypePolicy;
use App\Modules\Simulator\Policies\SimulatorAssetPolicy;
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
        Gate::policy(SimulatorType::class, SimulatorTypePolicy::class);
        Gate::policy(SimulatorAsset::class, SimulatorAssetPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(SimResource::class, SimResourcePolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Scenario::class, ScenarioPolicy::class);
    }
}
