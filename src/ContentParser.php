<?php
// ... (namespace, use は変更なし) ...
class ContentParser
{
    // ... (__construct は変更なし) ...
    public function parse(string $filePath): ?array
    {
        if (!file_exists($filePath)) { return null; }
        $content = file_get_contents($filePath);
        
        if (preg_match('/^---\s*$(.*)^---\s*$(.*)/ms', $content, $matches)) {
            $meta = Yaml::parse(trim($matches[1]));
            $bodyContent = trim($matches[2]);

            // ★★★ 新しい区切り文字に変更 ★★★
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
    // ... (parseAllScenarios は変更なし) ...
}