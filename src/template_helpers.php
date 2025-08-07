<?php

/**
 * 【FR-20対応】AIO/SEOに最適化された高品質なaltテキストを生成する
 *
 * @param array $product シミュレーション結果に含まれる商品情報配列
 * @param array $context この商品が表示される文脈情報
 * @param string $shopName サイト名
 * @return string 最適化されたaltテキスト
 */
function generateAltText(array $product, array $context = [], string $shopName = 'Fixed-term delivery'): string
{
    // フォールバック用の基本タイトル
    $baseTitle = $product['title'] ?? '注目商品';

    // 1. 文脈に応じた付加価値キーワードを決定
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

    // 2. 各要素を組み立て
    $altText = $prefix . $baseTitle;

    // 3. カテゴリ情報を追加 (シナリオのメタ情報などから取得することを想定)
    if (!empty($context['category'])) {
        $altText .= ' (カテゴリ: ' . htmlspecialchars($context['category']) . ')';
    }

    // 4. 提供元情報を追加
    $altText .= ' - ' . $shopName;

    // 5. 長すぎる場合に省略
    if (mb_strlen($altText) > 120) {
        $altText = mb_substr($altText, 0, 119) . '…';
    }

    return htmlspecialchars($altText);
}

/**
 * 画像最適化を考慮した<picture>タグを生成する
 *
 * @param array $product シミュレーション結果に含まれる商品情報配列
 * @param array $context altテキスト生成用の文脈情報
 * @return string HTMLの<picture>タグ文字列
 */
function createPictureTag(array $product, array $context = []): string
{
    $alt = generateAltText($product, $context);
    $imageUrl = $product['image_url'] ?? '';
    $width = $product['image_width'] ?? '';
    $height = $product['image_height'] ?? '';

    if (empty($imageUrl)) {
        return ''; // 画像URLがない場合は何も返さない
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

/**
 * 数値を日本円の通貨形式にフォーマットする
 *
 * @param float|int $number フォーマットする数値
 * @return string 通貨形式の文字列 (例: "￥1,280")
 */
function formatPrice(float|int $number): string
{
    return '￥' . number_format($number);
}