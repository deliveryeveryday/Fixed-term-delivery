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
     * 指定されたMarkdownファイルを解析する
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
            $body = $this->parsedown->text(trim($matches[2]));

            return [
                'meta' => $meta,
                'content' => $body
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