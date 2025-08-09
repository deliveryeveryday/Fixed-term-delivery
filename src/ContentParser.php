<?php
namespace App;
use Symfony\Component\Yaml\Yaml;
use Parsedown;
class ContentParser {
    private Parsedown $parsedown;
    public function __construct() { $this->parsedown = new Parsedown(); }
    public function parseAllScenarios(string $directoryPath): array {
        $scenarios = [];
        $files = glob($directoryPath . '/*.md');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (preg_match('/^---\s*$(.*)^---\s*$(.*)/ms', $content, $matches)) {
                // ここでエラーが発生しても、try-catchで握りつぶさず、上位に投げることが重要
                $meta = Yaml::parse(trim($matches[1]));
                $scenarios[] = ['meta' => $meta];
            }
        }
        return $scenarios;
    }
}