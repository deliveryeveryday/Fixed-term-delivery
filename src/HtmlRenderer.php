<?php

namespace App;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use Twig\TwigFilter;

class HtmlRenderer
{
    private Environment $twig;

    public function __construct(string $templatesPath)
    {
        $loader = new FilesystemLoader($templatesPath);
        $this->twig = new Environment($loader);
        
        $this->addCustomFunctionsAndFilters();
    }

    public function renderAndSave(string $templateFile, array $data, string $outputFilePath): void
    {
        try {
            $html = $this->twig->render($templateFile, $data);
            $outputDir = dirname($outputFilePath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            file_put_contents($outputFilePath, $html);
        } catch (\Exception $e) {
            error_log("HTML Rendering Error in template '{$templateFile}': " . $e->getMessage());
        }
    }

    /**
     * テンプレート内で使用するカスタム関数とフィルタを登録する
     */
    private function addCustomFunctionsAndFilters(): void
    {
        // --- カスタム関数: picture_tag ---
        $this->twig->addFunction(new TwigFunction('picture_tag', function ($product, $context = []) {
            // ALTテキスト生成ロジック
            $baseTitle = $product['title'] ?? '注目商品';
            $prefix = '【シミュレーション対象】';
            $altText = htmlspecialchars($prefix . $baseTitle . ' - Fixed-term delivery');

            $imageUrl = $product['image_url'] ?? '';
            if (empty($imageUrl)) return '';

            $webpUrl = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $imageUrl);
            $width = $product['image_width'] ?? '';
            $height = $product['image_height'] ?? '';

            $html = '<picture>';
            $html .= '<source srcset="' . htmlspecialchars($webpUrl) . '" type="image/webp">';
            $html .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . $altText . '" loading="lazy"';
            if ($width) $html .= ' width="' . $width . '"';
            if ($height) $html .= ' height="' . $height . '"';
            $html .= '>';
            $html .= '</picture>';
            return $html;
        }));

        // --- カスタムフィルタ: format_price ---
        $this->twig->addFilter(new TwigFilter('format_price', function ($number) {
            if (!is_numeric($number)) {
                return '';
            }
            return '￥' . number_format($number);
        }));
    }
}