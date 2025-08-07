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
            $meta = Yaml::parse($matches[1]);
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
}```

**2. 全13本のシナリオファイル（`.md`）の更新:**
全てのシナリオファイルを開き、サマリーの区切り文字として使っていた`---`の行を、以下の文字列に**全て置き換え**てください。

`<!-- summary -->`

**例 (`family-essentials.md`):**
```markdown
---
... (メタデータ) ...
---
### 【この記事でわかること】
... (サマリー部分) ...

<!-- summary -->

## 【結論】この5点をまとめ買い！...
... (本文) ...