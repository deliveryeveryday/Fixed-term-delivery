<?php

namespace App;

class SimulationEngine
{
    private const DISCOUNT_NORMAL = 0.10;
    private const DISCOUNT_BULK = 0.15;

    public function simulate(array $scenarioProducts, array $paApiData): array
    {
        $itemCount = count($scenarioProducts);
        $isBulkDiscount = $itemCount >= 3;
        $discountRate = $isBulkDiscount ? self::DISCOUNT_BULK : self::DISCOUNT_NORMAL;

        $simulatedProducts = [];
        $totalNormalPrice = 0;
        $totalDiscountedPrice = 0;

        foreach ($scenarioProducts as $p) {
            $asin = $p['asin'];
            $price = $paApiData[$asin]['Offers']['Listings'][0]['Price']['Amount'] ?? null;
            
            $title = $paApiData[$asin]['ItemInfo']['Title']['DisplayValue'] ?? ('商品 ' . $asin);
            $discountedPrice = ($price !== null) ? floor($price * (1 - $discountRate)) : 0;

            $simulatedProducts[] = [
                'asin' => $asin,
                'title' => $title,
                'url' => $paApiData[$asin]['DetailPageURL'] ?? '#',
                'image_url' => $paApiData[$asin]['Images']['Primary']['Large']['URL'] ?? '',
                'image_width' => $paApiData[$asin]['Images']['Primary']['Large']['Width'] ?? null,
                'image_height' => $paApiData[$asin]['Images']['Primary']['Large']['Height'] ?? null,
                'price' => (float)$price,
                'discounted_price' => (float)$discountedPrice,
            ];

            if ($price !== null) {
                $totalNormalPrice += $price;
                $totalDiscountedPrice += $discountedPrice;
            }
        }

        $monthlySavings = $totalNormalPrice - $totalDiscountedPrice;

        return [
            'products' => $simulatedProducts,
            'total_normal_price' => $totalNormalPrice,
            'total_discounted_price' => $totalDiscountedPrice,
            'monthly_savings' => $monthlySavings,
            'yearly_savings' => $monthlySavings * 12
        ];
    }
}