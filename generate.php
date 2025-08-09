<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\ContentParser;
use App\PaApiHandler;
use App\SimulationEngine;
use App\HtmlRenderer;

$accessKey = getenv('PAAPI_ACCESS_KEY') ?? '';
$secretKey = getenv('PAAPI_SECRET_KEY') ?? '';
$partnerTag = getenv('PAAPI_ASSOCIATE_TAG') ?? '';

if (empty($accessKey) || empty($secretKey) || empty($partnerTag)) {
    echo "Warning: PA-API credentials are not set. Site will be generated without product data.\n";
}

$contentParser = new ContentParser();
$paApiHandler = new PaApiHandler($accessKey, $secretKey, $partnerTag);
$simulationEngine = new SimulationEngine();
$htmlRenderer = new HtmlRenderer(__DIR__ . '/templates');

echo "Site generation started...\n";

try {
    $scenarios = $contentParser->parseAllScenarios(__DIR__ . '/content/scenarios');
    $allAsins = [];
    foreach ($scenarios as $scenario) {
        if (isset($scenario['meta']['products'])) {
            foreach ($scenario['meta']['products'] as $product) {
                $allAsins[] = $product['asin'];
            }
        }
    }
    $uniqueAsins = array_unique($allAsins);
    
    $paApiData = [];
    if (!empty($accessKey) && !empty($uniqueAsins)) {
        $paApiData = $paApiHandler->getItems($uniqueAsins) ?? [];
    }

    $renderedScenariosForIndex = [];
    foreach ($scenarios as $scenario) {
        $simulationResult = $simulationEngine->simulate($scenario['meta']['products'], $paApiData);
        $pageData = [
            'title' => ($scenario['meta']['title'] ?? 'シナリオ') . ' | Fixed-term delivery',
            'description' => $scenario['meta']['description'] ?? '',
            'scenario' => $scenario,
            'simulation' => $simulationResult
        ];
        $outputFile = __DIR__ . '/public/' . ($scenario['meta']['slug'] ?? uniqid()) . '.html';
        $htmlRenderer->renderAndSave('scenario.html', $pageData, $outputFile);
        $renderedScenariosForIndex[] = [
            'title' => $scenario['meta']['title'],
            'url' => './' . basename($outputFile),
            'yearly_savings_str' => '￥' . number_format($simulationResult['yearly_savings'])
        ];
    }

    $indexData = [
        'title' => 'Fixed-term delivery | Amazon定期おトク便 節約額シミュレーター',
        'description' => 'Amazon定期おトク便の賢い使い方を、具体的なモデルケースと共に解説。',
        'scenarios' => $renderedScenariosForIndex
    ];
    $htmlRenderer->renderAndSave('index.html', $indexData, __DIR__ . '/public/index.html');

} catch (Exception $e) {
    die("An error occurred: " . $e->getMessage() . "\n");
}

echo "Site generation completed successfully.\n";