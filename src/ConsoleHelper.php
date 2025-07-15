<?php

namespace InpostApiInterview;

use GuzzleHttp\Exception\RequestException;
use UnexpectedValueException;

class ConsoleHelper
{
    # Funkcja pomocnicza, pomaga uniknąć wypisywania PHP_EOL w wielu miejscach
    public static function printMessage(string $message): void
    {
        echo $message . PHP_EOL;
    }

    public static function printApiError(RequestException $e): void
    {
        if ($e->hasResponse()) {
            $responseBody = $e->getResponse()->getBody();
            self::printMessage(sprintf('Błąd API: kod: %d, wiadomość: %s', $e->getCode(), $responseBody->getContents()));

            # Przywraca wskaźnik strumienia na początek, aby umożliwić ponowny odczyt danych
            $responseBody->rewind();
        } else {
            self::printMessage('Pusta odpowiedź z API');
        }
    }

    public static function promptUserForOrganization(array $organisations): int
    {
        self::printMessage('Lista organizacji powiązanych z podanym tokenem:');
        $organisationIds = [];

        foreach ($organisations as $organisation) {
            # Sprawdzenie zgodności danych organizacji z API, w razie błędu pomijamy ten rekord
            try {
                if (false === isset($organisation['id'])) {
                    throw new UnexpectedValueException('Zła struktura odpowiedzi z API, brak ID organizacji.');
                }
                if (false === isset($organisation['name'])) {
                    throw new UnexpectedValueException('Zła struktura odpowiedzi z API, brak nazwy organizacji.');
                }
            } catch (UnexpectedValueException $e) {
                self::printMessage($e->getMessage());
                continue;
            }

            # Zapisanie id w celu późniejszej walidacji
            $organisationIds[] = $organisation['id'];

            # Output dla użytkownika żeby wiedział jakie są dostępne opcje
            self::printMessage(sprintf('> id: %s, nazwa: %s', $organisation['id'], $organisation['name']));
        }

        # Odpytanie użytkownika o ID organizacji, w razie błędnego inputu wyświelany jest błąd i odpytanie
        do {
            $choice = readline('Podaj ID organizacji z której ma być nadana paczka: ');

            if (false === in_array($choice, $organisationIds)) {
                self::printMessage('Brak organizacji z podanym ID.');
            }
        } while (false === in_array($choice, $organisationIds));


        return (int)$choice;
    }
}