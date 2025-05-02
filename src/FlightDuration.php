<?php

/**
 * Provides an API for flight durations.
 *
 * @package   flight-duration
 * @link      https://github.com/mrcgrtz/flight-duration/
 * @author    Marc Görtz (https://marcgoertz.de/)
 * @license   MIT License
 * @copyright Copyright (c) 2016-2025, Marc Görtz
 */

namespace Marcgoertz;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;

class FlightDuration
{
	/**
	 * Path to the airports data file.
	 */
	private const AIRPORTS_DATA = '../var/data/airports.dat';

	/**
	 * Array of airport IATA codes mapped to their timezone.
	 *
	 * @var array<string,string|null>
	 */
	private array $timezones = [];

	/**
	 * Initialize the class by loading airport timezone data.
	 *
	 * @throws RuntimeException If the airports data file cannot be read.
	 */
	public function __construct()
	{
		$filePath = \dirname(__FILE__) . '/' . self::AIRPORTS_DATA;

		if (!file_exists($filePath)) {
			throw new RuntimeException("Airports data file not found at: {$filePath}");
		}

		$flightData = file($filePath);
		if ($flightData === false) {
			throw new RuntimeException('Could not read airports data file.');
		}

		$flightData = array_map(function ($line) {
			return str_getcsv($line, ',', '"', '\\');
		}, $flightData);
		foreach ($flightData as $data) {
			// Ensure we have enough data columns
			if (\count($data) >= 12 && $data[11] !== '\N' && !empty($data[4])) {
				$this->timezones[$data[4]] = $data[11];
			}
		}

		if (empty($this->timezones)) {
			throw new RuntimeException('No valid timezone data found in airports file.');
		}
	}

	/**
	 * Get duration offset between two airports.
	 *
	 * @param  mixed $from               Departure datetime (format: YYYY-MM-DD HH:MM)
	 * @param  mixed $to                 Destination datetime (format: YYYY-MM-DD HH:MM)
	 * @param  mixed $departureAirport   IATA code for departure airport
	 * @param  mixed $destinationAirport IATA code for destination airport
	 * @return array{from:string,to:string,duration:string} Duration and local times
	 * @throws InvalidArgumentException If airport codes are invalid or not found
	 */
	public function getDuration(mixed $from, mixed $to, mixed $departureAirport, mixed $destinationAirport): array
	{
		// Validate input
		if (empty($from) || empty($to) || empty($departureAirport) || empty($destinationAirport)) {
			throw new InvalidArgumentException('All parameters are required.');
		}
		if (!is_string($from) || !is_string($to) || !is_string($departureAirport) || !is_string($destinationAirport)) {
			throw new InvalidArgumentException('All parameters must be strings.');
		}
		if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $from)) {
			throw new InvalidArgumentException('Invalid departure datetime format. Expected: YYYY-MM-DDTHH:MM');
		}
		if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $to)) {
			throw new InvalidArgumentException('Invalid destination datetime format. Expected: YYYY-MM-DDTHH:MM');
		}
		if (!preg_match('/^[A-Z]{3}$/', $departureAirport)) {
			throw new InvalidArgumentException('Invalid departure airport code. Expected: 3 uppercase letters');
		}
		if (!preg_match('/^[A-Z]{3}$/', $destinationAirport)) {
			throw new InvalidArgumentException('Invalid destination airport code. Expected: 3 uppercase letters');
		}
		if (!isset($this->timezones[$departureAirport])) {
			throw new InvalidArgumentException("Unknown departure airport code: {$departureAirport}");
		}
		if (!isset($this->timezones[$destinationAirport])) {
			throw new InvalidArgumentException("Unknown destination airport code: {$destinationAirport}");
		}

		// Set local times
		$departureTimezone = $this->getTimezoneForAirport($departureAirport);
		$fromLocal = new DateTime($from, new DateTimeZone($departureTimezone));

		$destinationTimezone = $this->getTimezoneForAirport($destinationAirport);
		$toLocal = new DateTime($to, new DateTimeZone($destinationTimezone));

		// Calculate difference
		$diff = $toLocal->diff($fromLocal);

		return [
			'from' => $fromLocal->format('Y-m-d\\TH:iP'),
			'to' => $toLocal->format('Y-m-d\\TH:iP'),
			'duration' => $diff->format('P%dDT%hH%iM'),
		];
	}

	/**
	 * Get timezone name for a specific airport.
	 *
	 * @param  string $airport IATA airport code
	 * @return string          Timezone name
	 * @throws InvalidArgumentException If the airport code is not found
	 */
	private function getTimezoneForAirport(string $airport): string
	{
		if (!isset($this->timezones[$airport])) {
			throw new InvalidArgumentException("Unknown airport code: {$airport}");
		}

		return $this->timezones[$airport];
	}
}
