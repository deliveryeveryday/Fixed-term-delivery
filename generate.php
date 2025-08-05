<?php

// 実行時間の計測開始
$startTime = microtime(true);

// 1. 初期設定とオートローダーの読み込み
require_once __DIR__ . '/vendor/autoload.php';

use App\ContentParser;
use App\PaApiHandler;
use App\SimulationEngine;
use App\HtmlRenderer;

// --- 環境変数から認証情報を取得 ---
// GitHub ActionsのSecretsに設定した値がここに渡される
$accessKey = $_SERVER['PAAPI_ACCESS_KEY'] ?? '';
$secretKey = $_SERVER['PAAPI_SECRET_KEY'] ?? '';
$partnerTag = $_SERVER['PAAPI_ASSOCIATE_TAG'] ?? '';

if (empty($accessKey) || empty($secretKey) || empty($partnerTag)) {
    die("Error: PA-API credentials are not set. Please check your environment variables or GitHub Secrets.\n");
}

// 2. 各コンポーネントのインスタンス化
$contentParser = new ContentParser();
$paApiHandler = new PaApiHandler($accessKey, $secretKey, $partnerTag);
$simulationEngine = new SimulationEngine();
$htmlRenderer = new HtmlRenderer(__DIR__ . '/templates');

echo "Site generation started...\n";

try {
    // 3. 全シナリオを解析し、必要なASINリストを作成
    $scenarios = $contentParser->parseAllScenarios(__DIR__ . '/content/scenarios');
    $allAsins = [];
    foreach ($scenarios as $scenario) {
        foreach ($scenario['meta']['products'] as $product) {
            $allAsins[] = $product['asin'];
        }
    }
    $uniqueAsins = array_unique($allAsins);

    echo "Found " . count($scenarios) . " scenarios with " . count($uniqueAsins) . " unique products.\n";

    // 4. PA-APIから全商品情報を一括取得
    echo "Fetching product data from PA-API...\n";
    $paApiData = $paApiHandler->getItems($uniqueAsins);
    if ($paApiData === null) {
        throw new Exception("Failed to fetch data from PA-API.");
    }
    echo "Successfully fetched data for " . count($paApiData) . " products.\n";

    // 5. 各シナリオのシミュレーションを実行し、HTMLを生成
    $renderedScenariosForIndex = [];
    foreach ($scenarios as $scenario) {
        echo "Processing scenario: " . ($scenario['meta']['title'] ?? 'No Title') . "\n";
        
        $simulationResult = $simulationEngine->simulate($scenario['meta']['products'], $paApiData);
        
        // テンプレートに渡すデータを準備
        $pageData = [
            'title' => $scenario['meta']['title'] ?? 'シナリオ',
            'description' => $scenario['meta']['description'] ?? '',
            'scenario' => $scenario,
            'simulation' => $simulationResult,
            // 'json_ld' => ... 将来的に構造化データをここに追加
        ];

        // 個別シナリオページを生成
        $outputFile = __DIR__ . '/public/' . ($scenario['meta']['slug'] ?? 'scenario-' . uniqid()) . '.html';
        $htmlRenderer->renderAndSave('scenario.html', $pageData, $outputFile);

        // トップページ用にデータを格納
        $renderedScenariosForIndex[] = [
            'title' => $scenario['meta']['title'],
            'description' => $scenario['meta']['description'],
            'url' => './' . basename($outputFile),
            'yearly_savings_str' => formatPrice($simulationResult['yearly_savings'])
        ];
    }

    // 6. トップページを生成
    echo "Generating index page...\n";
    $indexData = [
        'title' => 'トップページ',
        'description' => 'Fixed-term deliveryのトップページです。',
        'scenarios' => $renderedScenariosForIndex
    ];
    $htmlRenderer->renderAndSave('index.html', $indexData, __DIR__ . '/public/index.html');

} catch (Exception $e) {
    die("An error occurred during site generation: " . $e->getMessage() . "\n");
}

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);
echo "Site generation completed successfully in {$executionTime} seconds.\n";