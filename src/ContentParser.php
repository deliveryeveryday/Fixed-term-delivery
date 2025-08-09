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
     * 指定されたディレクトリ内の全てのシナリオファイルを解析する
     * @param string $directoryPath ディレクトリへのパス
     * @return array 解析されたシナリオデータの配列
     */
    public function parseAllScenarios(string $directoryPath): array
    {
        $scenarios = [];
        $files = glob($directoryPath . '/*.md');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (preg_match('/^---\s*$(.*)^---\s*$(.*)/ms', $content, $matches)) {
                try {
                    $meta = Yaml::parse(trim($matches[1]));
                    $bodyContent = trim($matches[2]);

                    $summaryHtml = '';
                    $mainContentHtml = '';
                    $separator = '<!-- summary -->';
                    $parts = explode($separator, $bodyContent, 2);

                    if (count($parts) === 2) {
                        $summaryHtml = $this->parsedown->text(trim($parts[0]));
                        $mainContentHtml = $this->parsedown->text(trim($parts[1]));
                    } else {
                        $mainContentHtml = $this->parsedown->text($bodyContent);
                    }

                    $scenarios[] = [
                        'meta' => $meta,
                        'summary_html' => $summaryHtml,
                        'main_content_html' => $mainContentHtml
                    ];
                } catch (\Exception $e) {
                    error_log("Error parsing YAML in file {$file}: " . $e->getMessage());
                    continue; // エラーがあっても処理を続行
                }
            }
        }
        return $scenarios;
    }
}