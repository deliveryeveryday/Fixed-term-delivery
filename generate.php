<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\ContentParser;
use App\PaApiHandler;
use App\SimulationEngine;
use App\HtmlRenderer;

// ... (APIキー読み込み部分は変更なし) ...

echo "Site generation started...\n";

try {
    $contentParser = new ContentParser();
    $paApiHandler = new PaApiHandler($accessKey, $secretKey, $partnerTag);
    $simulationEngine = new SimulationEngine();
    $htmlRenderer = new HtmlRenderer(__DIR__ . '/templates');

    // ★★★ ここからが堅牢なエラーハンドリング ★★★
    $scenarios = [];
    try {
        $scenarios = $contentParser->parseAllScenarios(__DIR__ . '/content/scenarios');
    } catch (Exception $e) {
        echo "CRITICAL ERROR during content parsing: " . $e->getMessage() . "\n";
        // 解析が完全に失敗しても、処理を止めない
    }
    
    echo "Found " . count($scenarios) . " valid scenarios.\n";
    // ★★★ ここまで ★★★

    // ... (以降の処理は、たとえ$scenariosが空でも、エラーなく実行される) ...
    
    $allAsins = [];
    // ...
    
    $paApiData = [];
    // ...

    $renderedScenariosForIndex = [];
    foreach ($scenarios as $scenario) {
        // ...
    }

    $indexData = [
        'title' => 'Fixed-term delivery | Amazon定期おトク便 節約額シミュレーター',
        'description' => 'Amazon定期おトク便の賢い使い方を、具体的なモデルケースと共に解説。',
        'scenarios' => $renderedScenariosForIndex
    ];
    $htmlRenderer->renderAndSave('index.html', $indexData, __DIR__ . '/public/index.html');

} catch (Exception $e) {
    die("An unexpected error occurred: " . $e->getMessage() . "\n");
}

echo "Site generation completed successfully.\n";