# Crypto currency ticker scraper

This is just a simple script to store all market summary information from Bittrex in Elasticsearch.

All you need to do is:

- Fork/Clone/Download the repo.
- Run `composer install`
- Have an Elasticsearch instance running somewhere
- Configure the url of your Elasticsearch machine/cluster in the script
- Call the script using `php fetch.php` (or whatever you'll call the script)

```php
<?php
declare(strict_types=1);

use ErikBooij\CryptoScraper\Scraper;

include 'vendor/autoload.php';

$scraper = new Scraper('127.0.0.1:9200', new DateTime());
$scraper->run();
```

Elasticsearch document

```json
{
   "took": 1,
   "timed_out": false,
   "_shards": {
      "total": 5,
      "successful": 5,
      "failed": 0
   },
   "hits": {
      "total": 26100,
      "max_score": 1,
      "hits": [
         {
            "_index": "crypto_currency_ticker",
            "_type": "crypto_data_point",
            "_id": "AV0ZGDEHgWyaLAwznh9T",
            "_score": 1,
            "_source": {
               "currency": "BTC-1ST",
               "datetime": "2017-07-06T20:10+0200",
               "ask": 0.00046177,
               "bid": 0.00044515,
               "last": 0.00046177,
               "high": 0.0004856,
               "low": 0.00044296,
               "volume": 70685.80937659,
               "baseVolume": 32.72161026,
               "buyOrders": 128,
               "sellOrders": 1033,
               "previousDay": 0.00047917
            }
         }
      ]
   }
}
```

Is this useful? For the majority of people probably not, in certain specific cases, yes absolutely.
