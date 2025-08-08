<?php
namespace Tests;

use App\SimulationEngine;
use PHPUnit\Framework\TestCase;

class SimulationEngineTest extends TestCase
{
    public function testSimulationWithBulkDiscount()
    {
        $config = ['discount_normal' => 0.10, 'discount_bulk' => 0.15];
        $engine = new SimulationEngine($config);

        // ダミーデータを作成
        $scenarioProducts = [['asin' => 'A'], ['asin' => 'B'], ['asin' => 'C']];
        $paApiData = [
            'A' => ['Offers' => ['Listings' => [['Price' => ['Amount' => 1000]]]]],
            'B' => ['Offers' => ['Listings' => [['Price' => ['Amount' => 2000]]]]],
            'C' => ['Offers' => ['Listings' => [['Price' => ['Amount' => 3000]]]]],
        ];

        $result = $engine->simulate($scenarioProducts, $paApiData);

        // 検証: 合計金額が、15%割引された正しい値になっているか？
        $expectedTotal = (1000 + 2000 + 3000) * (1 - 0.15);
        $this->assertEquals($expectedTotal, $result['total_discounted_price']);
    }

    // ゼロ除算エラーの再発を防ぐテスト
    public function testSimulationWithNoPriceData()
    {
        // ... 価格が空の場合のテスト ...
    }
}