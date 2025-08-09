<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\ContentParser;
// (他のuse文は、このシンプルなバージョンでは不要)

echo "Site generation started...\n";

try {
    $contentParser = new ContentParser();
    $scenarios = $contentParser->parseAllScenarios(__DIR__ . '/content/scenarios');

    // ★★★ 最も重要なエラーハンドリング ★★★
    if (empty($scenarios)) {
        throw new Exception("No valid scenario files found in 'content/scenarios'. Halting build.");
    }
    echo "Found " . count($scenarios) . " scenarios.\n";
    
    // publicフォルダを準備
    $publicDir = __DIR__ . '/public';
    if (!is_dir($publicDir)) { mkdir($publicDir, 0755, true); }

    // ★★★ 最もシンプルなindex.htmlを必ず生成する ★★★
    $indexContent = "<h1>Fixed-term delivery</h1><p>" . count($scenarios) . "件のシナリオが見つかりました。</p>";
    file_put_contents($publicDir . '/index.html', $indexContent);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1); // エラーコード1で終了し、ビルドを失敗させる
}

echo "Site generation completed successfully.\n";