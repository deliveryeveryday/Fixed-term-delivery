<?php

namespace App;

use Symfony\Component\Yaml\Yaml;
use Parsedown;

class ContentParser
{
    private Parsedown $parsedown;
    private string $scenariosBasePath;

    public function __construct(string $scenariosBasePath)
    {
        $this->parsedown = new Parsedown();
        $this->scenariosBasePath = $scenariosBasePath;
    }

    /**
     * 指定されたslugのシナリオを解析する
     * @param string $slug シナリオのディレクトリ名
     * @return array|null 解析されたシナリオデータ、またはエラー時にnull
     */
    public function parseScenarioBySlug(string $slug): ?array
    {
        $scenarioPath = $this->scenariosBasePath . '/' . $slug;

        // 必須ファイルの存在チェック
        $metaFile = $scenarioPath . '/meta.yml';
        $mainFile = $scenarioPath . '/main.md';

        if (!is_dir($scenarioPath) || !file_exists($metaFile) || !file_exists($mainFile)) {
            error_log("Skipping invalid scenario (missing required files): {$slug}");
            return null;
        }

        try {
            // 各ファイルを解析
            $meta = Yaml::parseFile($metaFile);
            $mainHtml = $this->parsedown->text(file_get_contents($mainFile));

            // オプションのファイルを解析
            $summaryFile = $scenarioPath . '/summary.md';
            $summaryHtml = file_exists($summaryFile) ? $this->parsedown->text(file_get_contents($summaryFile)) : '';

            $faqFile = $scenarioPath . '/faq.md';
            $faqHtml = file_exists($faqFile) ? $this->parsedown->text(file_get_contents($faqFile)) : '';

            return [
                'meta' => $meta,
                'summary_html' => $summaryHtml,
                'main_html' => $mainHtml,
                'faq_html' => $faqHtml,
            ];
        } catch (\Exception $e) {
            error_log("Error parsing scenario '{$slug}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * 全ての有効なシナリオを解析する
     * @return array 解析された全シナリオデータの配列
     */
    public function parseAllScenarios(): array
    {
        $scenarios = [];
        $slugs = array_filter(scandir($this->scenariosBasePath), function ($item) {
            return !in_array($item, ['.', '..']) && is_dir($this->scenariosBasePath . '/' . $item);
        });

        foreach ($slugs as $slug) {
            $parsedData = $this->parseScenarioBySlug($slug);
            if ($parsedData) {
                $scenarios[] = $parsedData;
            }
        }
        return $scenarios;
    }
}