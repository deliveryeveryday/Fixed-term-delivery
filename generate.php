<?php
$startTime = microtime(true);

require_once __DIR__ . '/vendor/autoload.php';

use App\ContentParser;
use App\PaApiHandler;
use App\SimulationEngine;
use App\HtmlRenderer;
use App\Logger;
// ★ Parsedownとtemplate_helpers.phpへの依存を完全に削除

$config = require __DIR__ . '/config.php';
$logger = Logger::getInstance($config['logging']);
$logger?->info("Site generation started.");

function buildJsonLdItemList(array $products): array {
    $itemListElement = [];
    $position = 1;
    foreach ($products as $product) {
        $itemListElement[] = [
            '@type' => 'ListItem', 'position' => $position++,
            'item' => [
                '@type' => 'Product', 'name' => $product['title'], 'productID' => $product['asin'],
                'identifier' => ['@type' => 'PropertyValue', 'propertyID' => 'asin', 'value' => $product['asin']]
            ]
        ];
    }
    return ['@context' => 'https://schema.org/', '@type' => 'ItemList', 'itemListElement' => $itemListElement];
}

try {
    $contentParser = new ContentParser(__DIR__ . '/content/scenarios');
    $paApiHandler = new PaApiHandler($config, $logger);
    $simulationEngine = new SimulationEngine($config['simulation']);
    $htmlRenderer = new HtmlRenderer(__DIR__ . '/templates');

    $authorProfileData = $contentParser->parse(__DIR__ . '/content/partials/author-profile.md'); // ★ ContentParserで解析
    $pillarContentData = $contentParser->parse(__DIR__ . '/content/partials/pillar-content.md'); // ★ ContentParserで解析

    $scenarios = $contentParser->parseAllScenarios();
    $scenariosBySlug = [];
    foreach ($scenarios as $scenario) {
        if (isset($scenario['meta']['slug'])) {
            $scenariosBySlug[$scenario['meta']['slug']] = $scenario;
        }
    }
    
    $allAsins = [];
    foreach ($scenarios as $scenario) {
        if (isset($scenario['meta']['products']) && is_array($scenario['meta']['products'])) {
            foreach ($scenario['meta']['products'] as $product) { $allAsins[] = $product['asin']; }
        }
    }
    $uniqueAsins = array_unique($allAsins);
    $logger?->info("Found " . count($scenarios) . " scenarios.", ['unique_products' => count($uniqueAsins)]);
    
    $paApiData = [];
    $apiConfig = $config['pa_api'];
    if (!empty($apiConfig['access_key']) && !empty($apiConfig['secret_key']) && !empty($apiConfig['partner_tag']) && !empty($uniqueAsins)) {
        $paApiData = $paApiHandler->getItems($uniqueAsins) ?? [];
        $logger?->info("Successfully fetched data for " . count($paApiData) . " products.");
    } else {
        $logger?->warning("PA-API credentials not set or no products to fetch. Skipping API call.");
    }
    
    $renderedScenariosForIndex = [];
    foreach ($scenarios as $scenario) {
        $logger?->info("Processing scenario: " . ($scenario['meta']['title'] ?? 'No Title'));
        
        $simulationResult = $simulationEngine->simulate($scenario['meta']['products'], $paApiData);
        $jsonLd = buildJsonLdItemList($simulationResult['products']);
        
        $relatedScenariosData = [];
        if (!empty($scenario['meta']['related_scenarios'])) {
            foreach ($scenario['meta']['related_scenarios'] as $relatedSlug) {
                if (isset($scenariosBySlug[$relatedSlug])) {
                    $relatedScenariosData[] = ['title' => $scenariosBySlug[$relatedSlug]['meta']['title'], 'url' => './' . $scenariosBySlug[$relatedSlug]['meta']['slug'] . '.html'];
                }
            }
        }

        $pageData = [
            'title' => ($scenario['meta']['title'] ?? 'シナリオ') . ' | Fixed-term delivery',
            'description' => $scenario['meta']['description'] ?? '',
            'scenario' => $scenario,
            'simulation' => $simulationResult,
            'json_ld' => $jsonLd,
            'author_profile' => $authorProfileData,
            'related_scenarios' => $relatedScenariosData
        ];
        
        $outputFile = __DIR__ . '/public/' . ($scenario['meta']['slug'] ?? uniqid()) . '.html';
        $htmlRenderer->renderAndSave('scenario.html', $pageData, $outputFile);

        $renderedScenariosForIndex[] = [
            'title' => $scenario['meta']['title'],
            'description' => $scenario['meta']['description'],
            'url' => './' . basename($outputFile),
            'yearly_savings' => $simulationResult['yearly_savings'],
            'summary_html' => $scenario['summary_html']
        ];
    }
    
    $indexData = [
        'title' => '【年間2.4万円節約】Amazon定期おトク便 完全攻略ガイド｜Fixed-term delivery',
        'description' => 'Amazon定期おトク便の賢い使い方を徹底解説。おまとめ割引で最大15%OFFにする方法や、子育て・健康・ペットなどライフスタイル別の節約シミュレーションで、あなたの家計をサポートします。',
        'scenarios' => $renderedScenariosForIndex,
        'pillar_content' => $pillarContentData
    ];
    $htmlRenderer->renderAndSave('index.html', $indexData, __DIR__ . '/public/index.html');

} catch (Exception $e) {
    $logger?->error("An unexpected error occurred during site generation.", ['exception' => $e]);
    die("An error occurred. Check the log file for details.");
}

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);
$logger?->info("Site generation completed successfully in {$executionTime} seconds.");

echo "Site generation completed successfully in {$executionTime} seconds.\n";