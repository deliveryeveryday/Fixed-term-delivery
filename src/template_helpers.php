<?php

function generateAltText(array $product, array $context = [], string $shopName = 'Fixed-term delivery'): string
{
    $baseTitle = $product['title'] ?? '注目商品';
    $prefix = '';
    switch ($context['type'] ?? 'default') {
        case 'scenario':
            $prefix = '【シミュレーション対象】';
            break;
        case 'ranking':
            $rank = $context['rank'] ?? '';
            $prefix = "【人気No.{$rank}】";
            break;
        default:
            $prefix = '【おすすめ】';
            break;
    }
    $altText = $prefix . $baseTitle;
    if (!empty($context['category'])) {
        $altText .= ' (カテゴリ: ' . htmlspecialchars($context['category']) . ')';
    }
    $altText .= ' - ' . $shopName;
    if (mb_strlen($altText) > 120) {
        $altText = mb_substr($altText, 0, 119) . '…';
    }
    return htmlspecialchars($altText);
}

function createPictureTag(array $product, array $context = []): string
{
    $alt = generateAltText($product, $context);
    $imageUrl = $product['image_url'] ?? '';
    $width = $product['image_width'] ?? '';
    $height = $product['image_height'] ?? '';

    if (empty($imageUrl)) {
        return '';
    }

    $webpUrl = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $imageUrl);

    $html = '<picture>';
    $html .= '<source srcset="' . htmlspecialchars($webpUrl) . '" type="image/webp">';
    $html .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . $alt . '" loading="lazy"';
    if ($width) $html .= ' width="' . $width . '"';
    if ($height) $html .= ' height="' . $height . '"';
    $html .= '>';
    $html .= '</picture>';
    return $html;
}

function formatPrice(float|int $number): string
{
    return '￥' . number_format($number);
}