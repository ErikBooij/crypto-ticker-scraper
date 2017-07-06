<?php
declare(strict_types=1);

use ErikBooij\CryptoScraper\Scraper;

include 'vendor/autoload.php';

$scraper = new Scraper('127.0.0.1:9200', new DateTime());
$scraper->run();
