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
$accessKey = $_SERVER['PAAPI_ACCESS_KEY'] ?? '';
$secretKey = $_SERVER['PAAPI_SECRET_KEY'] ?? '';
$partnerTag = $_SERVER['PAAPI_ASSOCIATE_TAG'] ?? '';

if (empty($accessKey) || empty($secretKey) || empty($partnerTag)) {
    die("Error: PA-API credentials are not set. Please check your environment variables or GitHub Secrets.\n");
}

/**
 * 商品リストからItemListスキーマのJSON-LDを生成するヘルパー関数
 */
function buildJsonLdItemList(array $products): array
{
    $itemListElement = [];
    $position = 1;
    foreach ($products as $product) {
        $itemListElement[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'item' => [
                '@type' => 'Product',
                'name' => $product['title'],
                'productID' => $product['asin'],
                'identifier' => [
                    '@type' => 'PropertyValue',
                    'propertyID' => 'asin',
                    'value' => $product['asin']
                ]
            ]
        ];
    }
    return ['@context' => 'https://schema.org/', '@type' => 'ItemList', 'itemListElement' => $itemListElement];
}


// 2. 各コンポーネントのインスタンス化
$contentParser = new ContentParser();
$paApiHandler = new PaApiHandler($accessKey, $secretKey, $partnerTag);
$simulationEngine = new SimulationEngine();
$htmlRenderer = new HtmlRenderer(__DIR__ . '/templates');

echo "Site generation started...\n";

try {
    // 3. 共通パーツ（著者プロフィール、ピラーコンテンツ）を読み込む
    $authorProfile = $contentParser->parse(__DIR__ . '/content/partials/author-profile.md');
    $pillarContent = $contentParser->parse(__DIR__ . '/content/partials/pillar-content.md');

    // 4. 全シナリオを解析し、slugをキーにしたマップを作成
    $scenarios = $contentParser->parseAllScenarios(__DIR__ . '/content/scenarios');
    $scenariosBySlug = [];
    foreach ($scenarios as $scenario) {
        if (isset($scenario['meta']['slug'])) {
            $scenariosBySlug[$scenario['meta']['slug']] = $scenario;
        }
    }

    // 5. 必要なASINリストを作成
    $allAsins = [];
    foreach ($scenarios as $scenario) {
        if (isset($scenario['meta']['products']) && is_array($scenario['meta']['products'])) {
            foreach ($scenario['meta']['products'] as $product) {
                $allAsins[] = $product['asin'];
            }
        }
    }
    $uniqueAsins = array_unique($allAsins);
    echo "Found " . count($scenarios) . " scenarios with " . count($uniqueAsins) . " unique products.\n";

    // 6. PA-APIから全商品情報を一括取得
    echo "Fetching product data from PA-API...\n";
    $paApiData = $paApiHandler->getItems($uniqueAsins);
    if ($paApiData === null) {
        throw new Exception("Failed to fetch data from PA-API.");
    }
    echo "Successfully fetched data for " . count($paApiData) . " products.\n";

    // 7. 各シナリオのシミュレーションを実行し、HTMLを生成
    $renderedScenariosForIndex = [];
    foreach ($scenarios as $scenario) {
        echo "Processing scenario: " . ($scenario['meta']['title'] ?? 'No Title') . "\n";
        
        $simulationResult = $simulationEngine->simulate($scenario['meta']['products'], $paApiData);
        $jsonLd = buildJsonLdItemList($simulationResult['products']);

        // 関連シナリオのデータを構築
        $relatedScenariosData = [];
        if (!empty($scenario['meta']['related_scenarios'])) {
            foreach ($scenario['meta']['related_scenarios'] as $relatedSlug) {
                if (isset($scenariosBySlug[$relatedSlug])) {
                    $relatedScenariosData[] = [
                        'title' => $scenariosBySlug[$relatedSlug]['meta']['title'],
                        'url' => './' . $scenariosBySlug[$relatedSlug]['meta']['slug'] . '.html'
                    ];
                }
            }
        }

        // テンプレートに渡すデータを準備
        $pageData = [
            'title' => $scenario['meta']['title'] . ' | Fixed-term delivery',
            'description' => $scenario['meta']['description'],
            'scenario' => $scenario,
            'simulation' => $simulationResult,
            'json_ld' => $jsonLd,
            'author_profile' => $authorProfile,
            'related_scenarios' => $relatedScenariosData
        ];

        // 個別シナリオページを生成
        $outputFile = __DIR__ . '/public/' . $scenario['meta']['slug'] . '.html';
        $htmlRenderer->renderAndSave('scenario.html', $pageData, $outputFile);

        // トップページ用にデータを格納
        $renderedScenariosForIndex[] = [
            'title' => $scenario['meta']['title'],
            'description' => $scenario['meta']['description'],
            'url' => './' . basename($outputFile),
            'yearly_savings_str' => formatPrice($simulationResult['yearly_savings']),
            'summary_html' => $scenario['summary_html']
        ];
    }

    // 8. トップページ（ピラーページ）を生成
    echo "Generating pillar page (index.html)...\n";
    $indexData = [
        'title' => '【年間2.4万円節約】Amazon定期おトク便 完全攻略ガイド｜Fixed-term delivery',
        'description' => 'Amazon定期おトク便の賢い使い方を徹底解説。おまとめ割引で最大15%OFFにする方法や、子育て・健康・ペットなどライフスタイル別の節約シミュレーションで、あなたの家計をサポートします。',
        'scenarios' => $renderedScenariosForIndex,
        'pillar_content' => $pillarContent
    ];
    $htmlRenderer->renderAndSave('index.html', $indexData, __DIR__ . '/public/index.html');

} catch (Exception $e) {
    die("An error occurred during site generation: " . $e->getMessage() . "\n");
}

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);
echo "Site generation completed successfully in {$executionTime} seconds.\n";