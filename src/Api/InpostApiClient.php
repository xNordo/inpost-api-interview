<?php

namespace InpostApiInterview\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

readonly class InpostApiClient
{
    const string CREATE_SHIPMENT_URL = '/v1/organizations/%d/shipments';
    const string ORGANIZATIONS_URL = 'v1/organizations';

    const string DISPATCH_ORDERS_URL = '/v1/organizations/%d/dispatch_orders';

    private Client $client;

    public function __construct(string $apiUrl, string $apiToken)
    {
        $this->client = new Client([
            'base_uri' => $apiUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
            ]
        ]);
    }

    # Tworzy nową paczkę w API inpostu, zwraca id nowo utworzonej paczki
    public function createShipment(int $organisationId): int
    {
        # Odczyt przykładowych danych z pliku
        $data = json_decode(file_get_contents('SampleData/create_package.json'), true);

        $url = sprintf(self::CREATE_SHIPMENT_URL, $organisationId);

        $response = $this->makeRequest('POST', $url, ['json' => $data]);
        $contents = json_decode($response->getBody()->getContents(), true);

        return $contents['id'];
    }

    public function fetchOrganisations(): array
    {
        # Odpytanie API o organizacje powiązane z tokenem
        $response = $this->makeRequest('GET', self::ORGANIZATIONS_URL);

        $contents = json_decode($response->getBody()->getContents(), true);

        # Sprawdzenie czy jakiekolwiek organizacje zostały zwrócone
        $organisations = $contents['items'] ?? [];

        if (empty($organisations)) {
            throw new UnexpectedValueException('Błąd: API nie zwróciło żadnych organizacji powiązanych z podanym tokenem, przynajmniej 1 wymagana do utworzenia paczki.');
        }

        return $organisations;
    }

    # Wysyła do API zlecenie odbioru przesyłki
    public function dispatchOrder(int $shipmentId, int $organisationId): void
    {
        $data = json_decode(file_get_contents('SampleData/dispatch_order.json'), true);
        $data['shipments'] = [$shipmentId];

        var_dump($data);

        $this->makeRequest('POST', sprintf(self::DISPATCH_ORDERS_URL, $organisationId), ['json' => $data]);
    }

    private function makeRequest(string $method, string $url, array $options = []): ResponseInterface
    {
        try {
            $response = $this->client->request($method, $url, $options);
        } catch (RequestException $e) {
            $this->printApiError($e);
            exit();
        } catch (GuzzleException $e) {
            printLine('Błąd klienta: ' . $e->getMessage());
            exit();
        }

        return $response;
    }

    private function printApiError(RequestException $e): void
    {
        if ($e->hasResponse()) {
            printLine(sprintf('Błąd API: kod: %d, wiadomość: %s', $e->getCode(), $e->getResponse()->getBody()->getContents()));
        } else {
            printLine('Błąd API: pusta odpowiedź');
        }
    }
}