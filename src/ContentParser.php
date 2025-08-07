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
            // ★★★ これが唯一の、そして全ての原因だったバグ修正です ★★★
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

            return [
                'meta' => $meta,
                'summary_html' => $summaryHtml,
                'main_content_html' => $mainContentHtml
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