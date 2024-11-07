<?php
/**
 * Provides an API for flight durations.
 *
 * @package   flight-durations
 * @link      https://github.com/mrcgrtz/flight-durations/
 * @author    Marc Görtz (https://marcgoertz.de/)
 * @license   MIT License
 * @copyright Copyright (c) 2016-2019, Marc Görtz
 * @version   2.0.0
 */
namespace Marcgoertz\FlightDuration;

require(dirname(__FILE__) . '/../vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use DateTime;
use DateTimeZone;

class FlightDuration
{
	private $request = null;
	private $from = null;
	private $to = null;
	private $departureAirport = null;
	private $destinationAirport = null;
	private $timezones = [];

	function __construct()
	{
		// Get request parameters
		$this->request = Request::createFromGlobals();
		$this->from = $this->request->query->get('from');
		$this->to = $this->request->query->get('to');
		$this->departureAirport = $this->request->query->get('departureAirport');
		$this->destinationAirport = $this->request->query->get('destinationAirport');

		// Get timezones from CSV file
		$flightData = array_map('str_getcsv', file(dirname(__FILE__) . '/../data/openflights/airports.dat'));
		foreach ($flightData as $data) {
			if ($data[11] !== '\N') {
				$this->timezones[$data[4]] = $data[11];
			}
		}
	}


	/**
	 * Get timezone name for a specific airport.
	 * @param  string $airport IATA airport code
	 * @return string          Timezone name
	 */
	private function getTimezoneForAirport(string $airport)
	{
		$timezone = new DateTimeZone($this->timezones[$airport]);
		return $timezone->getName();
	}

	/**
	 * Get duration offset between two airports.
	 * @param  string $from               Departure date
	 * @param  string $to                 Destination datetime
	 * @param  string $departureAirport   IATA code for departure airport
	 * @param  string $destinationAirport IATA code for destination airport
	 * @return array                      Duration and local times
	 */
	private function getDuration(string $from, string $to, string $departureAirport, string $destinationAirport)
	{
		// Set local times ...
		$timezone = $this->getTimezoneForAirport($departureAirport);
		$fromLocal = new DateTime($from . ' ' . $timezone);
		$timezone = $this->getTimezoneForAirport($destinationAirport);
		$toLocal = new DateTime($to . ' ' . $timezone);

		// ... and calculate difference
		$diff = $toLocal->diff($fromLocal, true);

		return [
			'from' => $fromLocal->format("Y-m-d\TH:iP"),
			'to' => $toLocal->format("Y-m-d\TH:iP"),
			'duration' => $diff->format("P%dDT%hH%iM"),
		];
	}

	public function init()
	{
		$data = null;
		if (is_string($this->from) && is_string($this->to) && is_string($this->departureAirport) && is_string($this->destinationAirport)) {
			$data = $this->getDuration($this->from, $this->to, $this->departureAirport, $this->destinationAirport);
		}

		$response = new JsonResponse(
			$data,
			$data ? JsonResponse::HTTP_OK : JsonResponse::HTTP_BAD_REQUEST,
			[
				'content-type' => 'application/json',
			]
		);
		$response->setCharset('utf-8');
		$response->prepare($this->request);
		$response->send();
	}
}

$durations = new FlightDuration();
$durations->init();
