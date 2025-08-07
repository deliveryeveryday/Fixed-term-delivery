<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PaApiHandler
{
    private string $accessKey;
    private string $secretKey;
    private string $partnerTag;
    private string $host;
    private string $region;
    private Client $client;

    public function __construct(string $accessKey, string $secretKey, string $partnerTag)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->partnerTag = $partnerTag;
        $this->host = 'webservices.amazon.co.jp';
        $this->region = 'us-west-2';
        $this->client = new Client();
    }

    public function getItems(array $asins): ?array
    {
        if (empty($asins)) {
            return [];
        }

        $target = 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems';
        $payload = [
            'ItemIds' => $asins,
            'PartnerTag' => $this->partnerTag,
            'PartnerType' => 'Associates',
            'Resources' => [
                'Images.Primary.Large',
                'ItemInfo.Title',
                'Offers.Listings.Price'
            ]
        ];

        try {
            $response = $this->client->post("https://{$this->host}/paapi5/getitems", [
                'headers' => $this->generateHeaders($target, json_encode($payload)),
                'body' => json_encode($payload)
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $items = [];
            if (isset($responseData['ItemsResult']['Items'])) {
                foreach ($responseData['ItemsResult']['Items'] as $item) {
                    $items[$item['ASIN']] = $item;
                }
            }
            return $items;

        } catch (GuzzleException $e) {
            error_log("PA-API Error: " . $e->getMessage());
            return null;
        }
    }

    private function generateHeaders(string $target, string $payload): array
    {
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        
        $canonical_uri = '/paapi5/getitems';
        $canonical_querystring = '';
        $canonical_headers = "host:{$this->host}\nx-amz-date:{$amzDate}\nx-amz-target:{$target}\n";
        $signed_headers = 'host;x-amz-date;x-amz-target';
        $payload_hash = hash('sha256', $payload);
        $canonical_request = "POST\n{$canonical_uri}\n{$canonical_querystring}\n{$canonical_headers}\n{$signed_headers}\n{$payload_hash}";

        $algorithm = 'AWS4-HMAC-SHA256';
        $credential_scope = "{$dateStamp}/{$this->region}/paapi/aws4_request";
        $string_to_sign = "{$algorithm}\n{$amzDate}\n{$credential_scope}\n" . hash('sha256', $canonical_request);

        $signing_key = hash_hmac('sha256', 'aws4_request', hash_hmac('sha256', 'paapi', hash_hmac('sha256', $this->region, hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true), true), true), true);
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);

        $authorization_header = "{$algorithm} Credential={$this->accessKey}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";

        return [
            'host' => $this->host,
            'x-amz-date' => $amzDate,
            'x-amz-target' => $target,
            'content-type' => 'application/json; charset=utf-8',
            'content-encoding' => 'amz-1.0',
            'Authorization' => $authorization_header
        ];
    }
}