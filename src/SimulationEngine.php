<?php

namespace App;

class SimulationEngine
{
    // 割引率ルール。将来Amazonの仕様が変わったら、ここを書き換える。
    private const DISCOUNT_NORMAL = 0.10; // 3点未満の場合の割引率
    private const DISCOUNT_BULK = 0.15;   // 3点以上のおまとめ割引率

    /**
     * 1つのシナリオについて、完全なシミュレーション結果を計算する
     * @param array $scenarioProducts シナリオで定義された商品リスト (ASINなど)
     * @param array $paApiData PA-APIから取得した、ASINをキーとする商品データ
     * @return array 計算されたシミュレーション結果
     */
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
            if (!isset($paApiData[$asin])) {
                continue; // PA-APIから情報を取得できなかった商品はスキップ
            }

            $productData = $paApiData[$asin];
            $price = $productData['Offers']['Listings'][0]['Price']['Amount'] ?? null;

            if ($price === null) {
                continue; // 価格情報がない商品はスキップ
            }

            $discountedPrice = floor($price * (1 - $discountRate));

            $simulatedProducts[] = [
                'asin' => $asin,
                'title' => $productData['ItemInfo']['Title']['DisplayValue'] ?? 'タイトル不明',
                'url' => $productData['DetailPageURL'] ?? '#',
                'image_url' => $productData['Images']['Primary']['Large']['URL'] ?? '',
                'image_width' => $productData['Images']['Primary']['Large']['Width'] ?? null,
                'image_height' => $productData['Images']['Primary']['Large']['Height'] ?? null,
                'price' => (float)$price,
                'discounted_price' => (float)$discountedPrice,
                'discount_percentage' => round(($discountedPrice / $price) * 100)
            ];

            $totalNormalPrice += $price;
            $totalDiscountedPrice += $discountedPrice;
        }

        $monthlySavings = $totalNormalPrice - $totalDiscountedPrice;

        return [
            'products' => $simulatedProducts,
            'total_normal_price' => $totalNormalPrice,
            'total_discounted_price' => $totalDiscountedPrice,
            'monthly_savings' => $monthlySavings,
            'yearly_savings' => $monthlySavings * 12,
            'item_count' => count($simulatedProducts),
            'discount_rate_applied' => $discountRate,
            'discount_rate_percent' => round((1 - ($totalDiscountedPrice / $totalNormalPrice)) * 100)
        ];
    }
}