<?php

namespace App;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class HtmlRenderer
{
    private Environment $twig;

    public function __construct(string $templatesPath)
    {
        // 1. テンプレートファイルが置かれているディレクトリを指定
        $loader = new FilesystemLoader($templatesPath);
        
        // 2. Twig環境を初期化
        $this->twig = new Environment($loader, [
            // 'cache' => './cache', // 本番環境ではキャッシュを有効にすると高速化
        ]);

        // 3. template_helpers.php で定義したカスタム関数をTwigに登録
        $this->addCustomFunctions();
    }

    /**
     * データとテンプレートを結合してHTMLを生成し、指定されたパスに保存する
     * @param string $templateFile 使用するテンプレートファイル名
     * @param array $data テンプレートに渡すデータ
     * @param string $outputFilePath 保存先のHTMLファイルパス
     */
    public function renderAndSave(string $templateFile, array $data, string $outputFilePath): void
    {
        try {
            $html = $this->twig->render($templateFile, $data);
            
            // 出力先ディレクトリがなければ作成
            $outputDir = dirname($outputFilePath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            file_put_contents($outputFilePath, $html);

        } catch (\Exception $e) {
            // エラーロギング
            error_log("HTML Rendering Error: " . $e->getMessage());
        }
    }

    /**
     * template_helpers.phpで定義した関数をTwigテンプレート内で使えるように登録する
     */
    private function addCustomFunctions(): void
    {
        // template_helpers.phpを読み込む
        require_once __DIR__ . '/template_helpers.php';

        // generateAltText関数を 'alt_text' という名前で登録
        $this->twig->addFunction(new TwigFunction('alt_text', function ($product, $context = []) {
            return generateAltText($product, $context);
        }));

        // createPictureTag関数を 'picture_tag' という名前で登録
        $this->twig->addFunction(new TwigFunction('picture_tag', function ($product, $context = []) {
            return createPictureTag($product, $context);
        }));

        // formatPrice関数を 'format_price' という名前で登録
        $this->twig->addFunction(new TwigFunction('format_price', function ($number) {
            return formatPrice($number);
        }));
    }
}