<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger as MonologLogger;

class PaApiHandler
{
    private array $apiConfig;
    private array $cacheConfig;
    private ?MonologLogger $logger;
    private Client $client;

    public function __construct(array $config, ?MonologLogger $logger)
    {
        $this->apiConfig = $config['pa_api'];
        $this->cacheConfig = $config['cache'];
        $this->logger = $logger;
        $this->client = new Client();
    }

    /**
     * 複数のASINの商品情報を、キャッシュを考慮して一括で取得する
     */
    public function getItems(array $asins): ?array
    {
        if (empty($asins)) {
            return [];
        }

        $cachedItems = [];
        $asinsToFetch = [];

        // 1. キャッシュを確認し、有効なものはキャッシュから取得
        if ($this->cacheConfig['enabled']) {
            if (!is_dir($this->cacheConfig['directory'])) {
                mkdir($this->cacheConfig['directory'], 0755, true);
            }
            foreach ($asins as $asin) {
                $cacheFile = $this->cacheConfig['directory'] . '/' . $asin . '.json';
                if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheConfig['ttl_seconds']) {
                    $cachedItems[$asin] = json_decode(file_get_contents($cacheFile), true);
                    $this->logger?->info("Cache hit for ASIN: {$asin}");
                } else {
                    $asinsToFetch[] = $asin;
                }
            }
        } else {
            $asinsToFetch = $asins;
        }

        if (empty($asinsToFetch)) {
            $this->logger?->info("All items were loaded from cache.");
            return $cachedItems;
        }

        // 2. 残りのASINをAPIから取得
        $this->logger?->info("Fetching from PA-API for ASINs: " . implode(', ', $asinsToFetch));

        $target = 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems';
        $payload = [
            'ItemIds' => $asinsToFetch,
            'PartnerTag' => $this->apiConfig['partner_tag'],
            'PartnerType' => 'Associates',
            'Resources' => [
                'Images.Primary.Large',
                'ItemInfo.Title',
                'Offers.Listings.Price'
            ]
        ];

        try {
            $response = $this->client->post("https://webservices.amazon.co.jp/paapi5/getitems", [
                'headers' => $this->generateHeaders($target, json_encode($payload)),
                'body' => json_encode($payload)
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $fetchedItems = [];
            if (isset($responseData['ItemsResult']['Items'])) {
                foreach ($responseData['ItemsResult']['Items'] as $item) {
                    $fetchedItems[$item['ASIN']] = $item;
                    // 3. 取得した情報をキャッシュに保存
                    if ($this->cacheConfig['enabled']) {
                        $cacheFile = $this->cacheConfig['directory'] . '/' . $item['ASIN'] . '.json';
                        file_put_contents($cacheFile, json_encode($item));
                        $this->logger?->info("Saved to cache: " . $item['ASIN']);
                    }
                }
            }

            // 4. キャッシュとAPI取得結果をマージして返す
            return array_merge($cachedItems, $fetchedItems);

        } catch (GuzzleException $e) {
            $this->logger?->error("PA-API request failed.", ['exception' => $e]);
            return null;
        }
    }

    private function generateHeaders(string $target, string $payload): array
    {
        $host = 'webservices.amazon.co.jp';
        $region = 'us-west-2';
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        
        $canonical_uri = '/paapi5/getitems';
        $canonical_querystring = '';
        $canonical_headers = "host:{$host}\nx-amz-date:{$amzDate}\nx-amz-target:{$target}\n";
        $signed_headers = 'host;x-amz-date;x-amz-target';
        $payload_hash = hash('sha256', $payload);
        $canonical_request = "POST\n{$canonical_uri}\n{$canonical_querystring}\n{$canonical_headers}\n{$signed_headers}\n{$payload_hash}";

        $algorithm = 'AWS4-HMAC-SHA256';
        $credential_scope = "{$dateStamp}/{$region}/paapi/aws4_request";
        $string_to_sign = "{$algorithm}\n{$amzDate}\n{$credential_scope}\n" . hash('sha256', $canonical_request);

        $signing_key = hash_hmac('sha256', 'aws4_request', hash_hmac('sha256', 'paapi', hash_hmac('sha256', $region, hash_hmac('sha256', $dateStamp, 'AWS4' . $this->apiConfig['secret_key'], true), true), true), true);
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);

        $authorization_header = "{$algorithm} Credential={$this->apiConfig['access_key']}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";

        return [
            'host' => $host,
            'x-amz-date' => $amzDate,
            'x-amz-target' => $target,
            'content-type' => 'application/json; charset=utf-8',
            'content-encoding' => 'amz-1.0',
            'Authorization' => $authorization_header
        ];
    }
}