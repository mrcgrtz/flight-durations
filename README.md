# Flight Duration (PHP)

[![PHP Composer](https://github.com/mrcgrtz/flight-duration/actions/workflows/php.yml/badge.svg)](https://github.com/mrcgrtz/flight-duration/actions/workflows/php.yml)
[![MIT license](https://img.shields.io/github/license/mrcgrtz/flight-duration)](https://github.com/mrcgrtz/flight-duration/blob/main/LICENSE.md)

Calculate actual flight durations between airports using timezone data from [OpenFlights.org](https://openflights.org/data.php).

## Features

- Calculate flight durations considering different timezones
- Support for over 12,000 airports worldwide
- Simple REST API with JSON responses
- OpenAPI documentation

## Installation

### Using Composer

```shell
composer require marcgoertz/flight-duration
```

### Manual Installation

1. Clone the repository:

    ```shell
    git clone https://github.com/mrcgrtz/flight-duration.git
    cd flight-duration
    ```

2. Install dependencies:

    ```shell
    composer install
    ```

## Usage

### Starting the Server

```shell
# Using PHP's built-in server
composer start

# Using ddev
ddev start
```

### API Parameters

| Parameter | Description | Format | Example |
|-----------|-------------|--------|---------|
| `from` | Departure datetime | YYYY-MM-DDTHH:MM | `2023-05-01T10:30` |
| `to` | Arrival datetime | YYYY-MM-DDTHH:MM | `2023-05-01T14:45` |
| `departureAirport` | IATA code of departure airport | 3 uppercase letters | `FRA` |
| `destinationAirport` | IATA code of destination airport | 3 uppercase letters | `JFK` |

### Example Requests

Using [curl](https://curl.se/):

```shell
curl "http://localhost:8000/?from=2023-05-01T10:30&to=2023-05-01T14:45&departureAirport=FRA&destinationAirport=JFK"
```

Using [Curlie](https://github.com/rs/curlie):

```shell
curlie http://localhost:8000/ from==2023-05-01T10:30 to==2023-05-01T14:45 departureAirport==FRA destinationAirport==JFK
```

### Example Response

```json
{
  "from": "2023-05-01T10:30+02:00",
  "to": "2023-05-01T14:45-04:00",
  "duration": "P0DT8H15M"
}
```

## Library Usage

You can also use `FlightDuration` as a library in your PHP project:

```php
use Marcgoertz\FlightDuration;

$flightDuration = new FlightDuration();
$duration = $flightDuration->getDuration(
    '2023-05-01T10:30',      // Departure time
    '2023-05-01T14:45',      // Arrival time
    'FRA',                   // Departure airport
    'JFK'                    // Destination airport
);

print_r($duration);
```

## Response Format

The API returns a JSON object with three properties:

- `from`: ISO 8601 formatted departure time with timezone
- `to`: ISO 8601 formatted arrival time with timezone
- `duration`: ISO 8601 duration format (PnYnMnDTnHnMnS)

## Development

### Requirements

- PHP 8.0 or higher
- Composer

## Credits

The airport data is provided by [OpenFlights.org](https://openflights.org/data.php).

## License

MIT © Marc Görtz
