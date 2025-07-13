<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use InpostApiInterview\Api\InpostApiClient;
use Dotenv\Dotenv;


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
    printLine('Błąd: brakujące dane w pliku .env');
    exit('Zatrzymano skrypt' . PHP_EOL);
}

$apiClient = new InpostApiClient($apiUrl, $apiToken);

# Wybranie z jakiej organizacji ma zostać nadana przesyłka, w API może byc kilka organizacji powiązanych z jednym tokenem
$organisations = $apiClient->fetchOrganisations();
$organisationId = promptUserForOrganization($organisations);

# Stworzenie przesyłki
$shipmentId = $apiClient->createShipment($organisationId);

# Zlecenie odbioru
$apiClient->dispatchOrder($shipmentId, $organisationId);

function promptUserForOrganization(array $organisations): int
{
    printLine('Lista organizacji powiązanych z podanym tokenem:');
    $organisationIds = [];

    foreach ($organisations as $organisation) {
        # Sprawdzenie zgodności danych organizacji z API, w razie błędu pomijamy ten rekord
        try {
            if (false === isset($organisation['id'])) {
                throw new UnexpectedValueException('Błąd: Zła struktura odpowiedzi z API, brak ID organizacji.');
            }
            if (false === isset($organisation['name'])) {
                throw new UnexpectedValueException('Błąd: Zła struktura odpowiedzi z API, brak nazwy organizacji.');
            }
        } catch (UnexpectedValueException $e) {
            printLine($e->getMessage());
            continue;
        }
        
        # Zapisanie id w celu późniejszej walidacji
        $organisationIds[] = $organisation['id'];
        
        # Output dla użytkownika żeby wiedział jakie są dostępne opcje
        printLine($organisation['id'] . ' ' . $organisation['name']);
    }

    # Odpytanie użytkownika o ID organizacji, w razie błędnego inputu wyświelany jest błąd i odpytanie
    do {
        $choice = readline('Podaj ID organizacji z której ma być nadana paczka: ');

        if (false === in_array($choice, $organisationIds)) {
            printLine('Brak organizacji z podanym ID.');
        }
    } while (false === in_array($choice, $organisationIds));


    return (int) $choice;
}




# Funkcja pomocnicza, pomaga uniknąć wypisywania PHP_EOL w wielu miejscach
function printLine($message): void
{
    echo $message . PHP_EOL;
}
