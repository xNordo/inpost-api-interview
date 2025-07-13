# InPost API Shipment Creator

Skrypt PHP do tworzenia przesyłek za pomocą API InPost.

````markdown
## Wymagania

- PHP 8.3
- Composer

## Instalacja

1. Zainstaluj zależności:
   ```bash
   composer install
````

2. Skonfiguruj plik **`.env`** z przynajmniej jednym tokenem API:

```
API_TOKEN=your_production_api_token
SANDBOX_API_TOKEN=your_sandbox_api_token
```

## Uruchamianie skryptu

### Lokalne uruchomienie:

Zainstaluj zależności:

```bash
composer install
```

Uruchom skrypt:

```bash
php src/create_shipments.php
```

Aby użyć środowiska produkcyjnego, dodaj opcję **`--prod`**:

```bash
php src/create_shipments.php --prod
```

### Uruchomienie w Dockerze:

1. Uruchom Docker Compose:

   ```bash
   docker compose up -d
   ```

2. Wejdź do kontenera:

   ```bash
   docker compose exec -it php bash
   ```

3. Uruchom skrypt:

   ```bash
   php src/create_shipments.php
   ```

