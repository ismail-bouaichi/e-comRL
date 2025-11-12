<?php

namespace App\Services;

use App\Models\ShippingZone;

class ShippingCalculationService
{
    /**
     * Calculate shipping cost based on city and country
     */
    public function calculateShippingCost(string $city, string $country): float
    {
        $shippingZone = ShippingZone::where('city', $city)
            ->where('country', $country)
            ->first();

        if ($shippingZone) {
            return $shippingZone->cost;
        }

        // Default shipping cost if zone not found
        return $this->getDefaultShippingCost();
    }

    /**
     * Get default shipping cost from config or database
     */
    private function getDefaultShippingCost(): float
    {
        return config('shipping.default_cost', 10.00);
    }

    /**
     * Calculate shipping cost based on weight (future implementation)
     */
    public function calculateByWeight(float $weight, string $city, string $country): float
    {
        $baseShippingCost = $this->calculateShippingCost($city, $country);
        
        // Add weight-based calculation
        // Example: $1 per kg over 5kg
        if ($weight > 5) {
            $extraWeight = $weight - 5;
            $baseShippingCost += $extraWeight * 1.00;
        }

        return $baseShippingCost;
    }

    /**
     * Check if free shipping is available for the order
     */
    public function isFreeShippingEligible(float $orderTotal): bool
    {
        $freeShippingThreshold = config('shipping.free_shipping_threshold', 100.00);
        
        return $orderTotal >= $freeShippingThreshold;
    }

    /**
     * Get all available shipping zones
     */
    public function getAvailableZones()
    {
        return ShippingZone::all();
    }

    /**
     * Get shipping options for a given location
     */
    public function getShippingOptions(string $city, string $country): array
    {
        $standardCost = $this->calculateShippingCost($city, $country);

        return [
            'standard' => [
                'name' => 'Standard Delivery',
                'cost' => $standardCost,
                'estimated_days' => '3-5 business days',
            ],
            'express' => [
                'name' => 'Express Delivery',
                'cost' => $standardCost * 1.5,
                'estimated_days' => '1-2 business days',
            ],
            'overnight' => [
                'name' => 'Overnight Delivery',
                'cost' => $standardCost * 2,
                'estimated_days' => 'Next business day',
            ],
        ];
    }
}
