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
            $price = null; // デフォルトはnull

            // PA-APIから価格情報を取得できれば、それを使う
            if (isset($paApiData[$asin])) {
                $productData = $paApiData[$asin];
                $price = $productData['Offers']['Listings'][0]['Price']['Amount'] ?? null;
            }

            // ★★★ APIデータがなくても、空のプレースホルダを作るように変更 ★★★
            $title = $paApiData[$asin]['ItemInfo']['Title']['DisplayValue'] ?? ('商品 ' . $asin);
            
            $discountedPrice = ($price !== null) ? floor($price * (1 - $discountRate)) : 0;
            $discountPercentage = ($price > 0) ? round(($discountedPrice / $price) * 100) : 100;

            $simulatedProducts[] = [
                'asin' => $asin,
                'title' => $title,
                'url' => $paApiData[$asin]['DetailPageURL'] ?? '#',
                'image_url' => $paApiData[$asin]['Images']['Primary']['Large']['URL'] ?? '',
                'image_width' => $paApiData[$asin]['Images']['Primary']['Large']['Width'] ?? null,
                'image_height' => $paApiData[$asin]['Images']['Primary']['Large']['Height'] ?? null,
                'price' => (float)$price,
                'discounted_price' => (float)$discountedPrice,
                'discount_percentage' => $discountPercentage
            ];

            if ($price !== null) {
                $totalNormalPrice += $price;
                $totalDiscountedPrice += $discountedPrice;
            }
        }

        $monthlySavings = $totalNormalPrice - $totalDiscountedPrice;

        // ★★★ ゼロ除算を防止する安全装置 ★★★
        $finalDiscountRatePercent = ($totalNormalPrice > 0)
            ? round((1 - ($totalDiscountedPrice / $totalNormalPrice)) * 100)
            : 0;

        return [
            'products' => $simulatedProducts,
            'total_normal_price' => $totalNormalPrice,
            'total_discounted_price' => $totalDiscountedPrice,
            'monthly_savings' => $monthlySavings,
            'yearly_savings' => $monthlySavings * 12,
            'item_count' => count($simulatedProducts),
            'discount_rate_applied' => $discountRate,
            'discount_rate_percent' => $finalDiscountRatePercent
        ];
    }
}
