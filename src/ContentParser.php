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

    public function parse(string $filePath): ?array
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        
        if (preg_match('/^---\s*$(.*)^---\s*$(.*)/ms', $content, $matches)) {
            $meta = Yaml::parse(trim($matches[1])); 
            $bodyContent = trim($matches[2]);

            // シンプルなバージョンに戻し、サマリー分割機能を一旦削除
            $mainContentHtml = $this->parsedown->text($bodyContent);

            return [
                'meta' => $meta,
                'content_html' => $mainContentHtml // キー名をシンプルに
            ];
        }

        return null;
    }

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