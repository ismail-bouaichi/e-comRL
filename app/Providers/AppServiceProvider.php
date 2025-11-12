<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\OrderProcessingService;
use App\Services\DeliveryWorkerAssignmentService;
use App\Services\NotificationService;
use App\Services\PricingService;
use App\Services\ShippingCalculationService;
use App\Actions\Product\CalculateProductDiscountAction;
use App\Actions\Payment\CreateStripeCheckoutAction;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Actions as singletons (shared across all services)
        $this->app->singleton(CalculateProductDiscountAction::class);
        
        // Register Services as singletons
        // Laravel will auto-resolve constructor dependencies
        $this->app->singleton(DeliveryWorkerAssignmentService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(PricingService::class);
        $this->app->singleton(ShippingCalculationService::class);
        $this->app->singleton(CreateStripeCheckoutAction::class);
        $this->app->singleton(OrderProcessingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
