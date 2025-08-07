<?php

namespace App;

use Symfony\Component\Yaml\Yaml;
use Parsedown;

class ContentParser
{
    private Parsedown $parsedown;

    public function __construct()
    {
        $this->parsedown = new Parsedown();
    }

    /**
     * 指定されたMarkdownファイルを解析し、メタ、サマリー、メインコンテンツに分割する
     * @param string $filePath ファイルへのパス
     * @return array|null メタデータとHTMLコンテンツを含む配列、またはエラー時にnull
     */
    public function parse(string $filePath): ?array
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        
        // YAML Front Matterを解析
        if (preg_match('/^---\s*$(.*)^---\s*$(.*)/ms', $content, $matches)) {
            $meta = Yaml::parse($matches[1]);
            $bodyContent = trim($matches[2]);

            // 本文をサマリーとメインコンテンツに分割
            $summaryHtml = '';
            $mainContentHtml = '';
            $parts = explode('---', $bodyContent, 2);

            if (count($parts) === 2) {
                // 区切り文字がある場合
                $summaryHtml = $this->parsedown->text(trim($parts[0]));
                $mainContentHtml = $this->parsedown->text(trim($parts[1]));
            } else {
                // 区切り文字がない場合
                $mainContentHtml = $this->parsedown->text($bodyContent);
            }

            return [
                'meta' => $meta,
                'summary_html' => $summaryHtml,
                'main_content_html' => $mainContentHtml
            ];
        }

        return null; // Front Matterがない場合は無効なファイルとみなす
    }

    /**
     * 指定されたディレクトリ内の全てのシナリオファイルを解析する
     * @param string $directoryPath ディレクトリへのパス
     * @return array 解析されたシナリオデータの配列
     */
    public function parseAllScenarios(string $directoryPath): array
    {
        $scenarios = [];
        $files = glob($directoryPath . '/*.md');

        foreach ($files as $file) {
            $parsedData = $this->parse($file);
            if ($parsedData) {
                $scenarios[] = $parsedData;
            }
        }
        return $scenarios;
    }
}