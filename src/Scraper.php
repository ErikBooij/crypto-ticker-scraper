<?php
declare(strict_types=1);

namespace ErikBooij\CryptoScraper;

use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Client as HttpClient;

class Scraper {
    const INDEX_NAME = 'crypto_currency_ticker';
    const DOCUMENT_TYPE = 'crypto_data_point';
    const MAPPING_FILE = 'mapping.json';

    /** @var ElasticsearchClient */
    private $elasticsearchClient;

    /** @var HttpClient */
    private $httpClient;

    /**
     * @param string    $host
     * @param \DateTime $timestamp
     */
    public function __construct(string $host, \DateTime $timestamp = null)
    {
        $this->timestamp = $timestamp ?? new \DateTime();
        $this->elasticsearchClient = ClientBuilder::create()
            ->setHosts([$host])
            ->build();
        $this->httpClient = new HttpClient();
    }

    /**
     * @return bool
     */
    public function run(): bool
    {
        if ($this->prepareIndex() === false) {
            echo "Index could not be created. No index available to write to. Exiting.\n";

            return false;
        }

        $currencies = $this->fetchTickerData();

        if (count($currencies) === 0) {
            return false;
        }

        $bulkData = $this->createBulkInsert($currencies);

        $this->elasticsearchClient->bulk([
            'body' => $bulkData
        ]);

        return true;
    }

    /**
     * @param $currencies
     *
     * @return array
     */
    private function createBulkInsert($currencies): array
    {
        $update = [];

        foreach ($currencies as $currency) {
            $update[] = [
                'index' => [
                    '_index' => static::INDEX_NAME,
                    '_type' => static::DOCUMENT_TYPE
                ]
            ];

            $update[] = [
                'currency' => $currency['MarketName'],
                'datetime' => $this->timestamp->format('Y-m-d\TH:iO'),
                'ask' => $currency['Ask'],
                'bid' => $currency['Bid'],
                'last' => $currency['Last'],
                'high' => $currency['High'],
                'low' => $currency['Low'],
                'volume' => $currency['Volume'],
                'baseVolume' => $currency['BaseVolume'],
                'buyOrders' => $currency['OpenBuyOrders'],
                'sellOrders' => $currency['OpenSellOrders'],
                'previousDay' => $currency['PrevDay']
            ];
        }

        return $update;
    }

    /**
     * @return array
     */
    private function fetchTickerData(): array
    {
        $res = $this->httpClient->get('https://bittrex.com/api/v1.1/public/getmarketsummaries');

        if ($res->getStatusCode() !== 200) {
            return [];
        }

        $data = json_decode($res->getBody()->getContents(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return array_filter($data['result'], function (array $currencyData): bool {
            return $this->integrityCheck($currencyData);
        });
    }

    /**
     * @param array $currencyData
     *
     * @return bool
     */
    private function integrityCheck(array $currencyData): bool
    {
        $requiredFields = [
            'MarketName',
            'High',
            'Low',
            'Last',
            'Bid',
            'Ask',
            'Volume',
            'BaseVolume',
            'OpenBuyOrders',
            'OpenSellOrders',
            'PrevDay'
        ];

        foreach ($requiredFields as $requiredField) {
            if (!array_key_exists($requiredField, $currencyData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    private function prepareIndex(): bool
    {
        $indexExists = $this->elasticsearchClient->indices()->exists(['index' => static::INDEX_NAME]);

        if ($indexExists) return true;

        $mapping = file_get_contents(static::MAPPING_FILE);
        $mapping = json_decode($mapping, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            printf('Error parsing mapping file [%s], index was not created%s', static::MAPPING_FILE, PHP_EOL);

            return false;
        }

        $result = $this->elasticsearchClient->indices()->create([
            'index' => static::INDEX_NAME,
            'body' => $mapping
        ]);

        return $result['acknowledged'] === true;
    }
}
