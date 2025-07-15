<?php

namespace InpostApiInterview;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use UnexpectedValueException;

readonly class InpostApiClient
{
    const string CREATE_SHIPMENT_URL = '/v1/organizations/%d/shipments';
    const string ORGANIZATIONS_URL = 'v1/organizations';

    const string DISPATCH_ORDERS_URL = '/v1/organizations/%d/dispatch_orders';


    const string SHIPMENT_STATUS_CONFIRMED = 'confirmed';

    private Client $client;

    public function __construct(string $apiUrl, string $apiToken)
    {
        $this->client = new Client([
            'base_uri' => $apiUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
            ],
            'handler' => $this->prepareHandlerStack()
        ]);
    }

    public function fetchOrganisations(): array
    {
        # Odpytanie API o organizacje powiązane z tokenem
        $response = $this->makeRequest('GET', self::ORGANIZATIONS_URL);

        $contents = json_decode($response->getBody()->getContents(), true);

        # Sprawdzenie czy jakiekolwiek organizacje zostały zwrócone
        $organisations = $contents['items'] ?? [];

        if (empty($organisations)) {
            throw new UnexpectedValueException('API nie zwróciło żadnych organizacji powiązanych z podanym tokenem, przynajmniej 1 wymagana do utworzenia paczki.');
        }

        return $organisations;
    }

    # Tworzy nową paczkę w API inpostu, zwraca id nowo utworzonej paczki
    public function createShipment(int $organisationId): int
    {
        # Odczyt przykładowych danych z pliku
        $data = json_decode(file_get_contents('SampleData/create_package.json'), true);

        $url = sprintf(self::CREATE_SHIPMENT_URL, $organisationId);

        $response = $this->makeRequest('POST', $url, ['json' => $data]);
        $contents = json_decode($response->getBody()->getContents(), true);

        $shipmentId = $contents['id'] ?? null;

        if (empty($shipmentId)) {
            throw new UnexpectedValueException('API nie zwróciło ID utworzyonej przesyłki.');
        }

        ConsoleHelper::printMessage('Utworzono przesyłkę z id: ' . $shipmentId);

        return $shipmentId;
    }

    # Metoda poolinguje API o status przesyłki
    public function waitForShipmentConfirm(int $shipmentId, int $organisationId): void
    {
        $timeWait = 5;
        $multiplier = 2;
        $maxTries = 5;
        $counter = 0;

        while (false === $this->isShipmentStatusConfirmed($shipmentId, $organisationId)) {
            ConsoleHelper::printMessage(sprintf('Następne odpytanie o status przesyłki za: %d sekund', $timeWait));
            sleep($timeWait);
            $timeWait *= $multiplier;
            $counter++;

            if ($counter >= $maxTries) {
                throw new RuntimeException('Osiągnięto limit prób odpytania API o status paczki.');
            }
        }
    }

    # Sprawdzenie czy status przesyłki to 'confirmed'
    public function isShipmentStatusConfirmed(int $shipmentId, int $organisationId): bool
    {
        ConsoleHelper::printMessage('Sprawdzanie czy przesyłka ma nadany status confirmed...');

        $url = sprintf('/v1/organizations/%d/shipments', $organisationId);
        $response = $this->makeRequest('GET', $url, ['json' => ['id' => $shipmentId]]);
        $contents = json_decode($response->getBody()->getContents(), true);
        $shipment = $contents['items'][0] ?? [];

        if (empty($shipment)) {
            throw new UnexpectedValueException('API nie zwróciło nowo utworzonej przesyłki.');
        }

        if (empty($shipment['status'])) {
            throw new UnexpectedValueException('Przesyłka nie posiada statusu.');
        }

        if ($shipment['status'] === self::SHIPMENT_STATUS_CONFIRMED) {
            ConsoleHelper::printMessage('Potwierdzono, że przesyłka ma status confirmed.');
            return true;
        } else {
            ConsoleHelper::printMessage('Przesyłka jeszcze nie ma statusu confirmed, aktualny status: ' . $shipment['status']);
            return false;
        }
    }

    # Wysyła do API zlecenie odbioru przesyłki
    public function dispatchOrder(int $shipmentId, int $organisationId): void
    {
        $data = json_decode(file_get_contents('SampleData/dispatch_order.json'), true);
        $data['shipments'] = [$shipmentId];

        $this->makeRequest('POST', sprintf(self::DISPATCH_ORDERS_URL, $organisationId), ['json' => $data]);

        # Błędne statusy są wyłapywane w makeRequest więc jeśli skrypt doszedł tutaj to znaczy, że operacja się powiodła
        ConsoleHelper::printMessage('Zlecono odbiór przesyłki.');
    }

    private function makeRequest(string $method, string $url, array $options = []): ResponseInterface
    {
        try {
            $response = $this->client->request($method, $url, $options);
        } catch (RequestException $e) {
            ConsoleHelper::printApiError($e);
            exit();
        } catch (GuzzleException $e) {
            ConsoleHelper::printMessage('Błąd klienta: ' . $e->getMessage());
            exit();
        }

        # Fix logowanie przesuwa wskaźnik strumienia, więc musi zostać cofnięty
        $response->getBody()->rewind();

        return $response;
    }

    # Tworzy handler stack do logowania przez guzzle
    private function prepareHandlerStack(): HandlerStack
    {
        $loggerProvider = new LoggerProvider();
        $messageFormat = 'Logging the request: {method} {uri} HTTP/{version} {req_body},
Logging the response: RESPONSE: {code} – {res_body}';

        $guzzleMiddleware = Middleware::log(
            $loggerProvider->provideForApiConsumer(),
            new MessageFormatter($messageFormat)
        );

        $handlerStack = HandlerStack::create();
        $handlerStack->push($guzzleMiddleware);
        return $handlerStack;
    }
}