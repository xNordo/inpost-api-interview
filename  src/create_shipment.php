<?php

declare(strict_types=1);

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://api.nordo.gov.uk/'
]);
