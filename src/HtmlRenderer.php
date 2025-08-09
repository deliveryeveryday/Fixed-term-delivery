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
            // 'cache' => __DIR__ . '/../cache/twig', // 本番環境ではキャッシュを有効にすると高速化
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
            // エラーをより具体的にログに出力するか、上位にスローする
            // ここでは簡潔さのためにエラーログに出力
            error_log("HTML Rendering Error in template '{$templateFile}': " . $e->getMessage());
            // 実行を継続するか、あるいはここで例外を再スローするかは要件による
            // throw $e; 
        }
    }

    /**
     * template_helpers.phpで定義した関数をTwigテンプレート内で使えるように登録する
     */
    private function addCustomFunctions(): void
    {
        // template_helpers.phpが存在するか確認してから読み込む
        $helpersFile = __DIR__ . '/template_helpers.php';
        if (file_exists($helpersFile)) {
            require_once $helpersFile;

            // 各関数が存在するか確認してからTwigに登録
            if (function_exists('createPictureTag')) {
                $this->twig->addFunction(new TwigFunction('picture_tag', function ($product, $context = []) {
                    return createPictureTag($product, $context);
                }));
            }

            if (function_exists('formatPrice')) {
                $this->twig->addFunction(new TwigFunction('format_price', function ($number) {
                    return formatPrice($number);
                }));
            }
        }
    }
}