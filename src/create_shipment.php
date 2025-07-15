<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use InpostApiInterview\ConsoleHelper;
use InpostApiInterview\InpostApiClient;


$options = getopt('', ['prod']);
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

# Inicjalizacja envów
if (isset($options['prod'])) {
    $apiUrl = $_ENV['API_URL'];
    $apiToken = $_ENV['API_TOKEN'];
} else {
    $apiUrl = $_ENV['SANDBOX_API_URL'];
    $apiToken = $_ENV['SANDBOX_API_TOKEN'];
}

# Walidacja danych z .env
if (empty($apiUrl) || empty($apiToken)) {
    ConsoleHelper::printMessage('Błąd: brakujące dane w pliku .env');
    exit('Zatrzymano skrypt' . PHP_EOL);
}


try {
    $apiClient = new InpostApiClient($apiUrl, $apiToken);

    # Wybranie z jakiej organizacji ma zostać nadana przesyłka, w API może byc kilka organizacji powiązanych z jednym tokenem
    $organisations = $apiClient->fetchOrganisations();
    $organisationId = ConsoleHelper::promptUserForOrganization($organisations);

    # Stworzenie przesyłki
    $shipmentId = $apiClient->createShipment($organisationId);

    # Odczekanie na zmianę statusu przesyłki w API, bez statusu confirmed nie można zlecić odbioru
    $apiClient->waitForShipmentConfirm($shipmentId, $organisationId);

    # Zlecenie odbioru
    $apiClient->dispatchOrder($shipmentId, $organisationId);
} catch (Throwable $e) {
    ConsoleHelper::printMessage('Wystąpił nieoczekiwany błąd: ' . $e->getMessage());
    exit();
}
