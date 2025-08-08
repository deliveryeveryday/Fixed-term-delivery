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
        $loader = new FilesystemLoader($templatesPath);
        $this->twig = new Environment($loader);
        $this->addCustomFunctions();
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
            error_log("HTML Rendering Error: " . $e->getMessage());
        }
    }

    private function addCustomFunctions(): void
    {
        require_once __DIR__ . '/template_helpers.php';

        $this->twig->addFunction(new TwigFunction('picture_tag', function ($product, $context = []) {
            return createPictureTag($product, $context);
        }));

        $this->twig->addFunction(new TwigFunction('format_price', function ($number) {
            return formatPrice($number);
        }));
    }
}