<?php
// set JSON content type
header('content-type: application/json;charset=utf-8');

// check for required request parameters
if (
	empty($_GET['from']) or
	empty($_GET['to']) or
	empty($_GET['departureAirport']) or
	empty($_GET['destinationAirport'])
) {
	header('HTTP/1.0 400 Bad request', true, 400);
	exit();
} else {
	$from = $_GET['from'];
	$to = $_GET['to'];
	$departureAirport = $_GET['departureAirport'];
	$destinationAirport = $_GET['destinationAirport'];
}

// @TODO: check for valid datetime strings
// @TODO: check for valid IATA codes

// read flightdata
$flightData = array_map('str_getcsv', file(dirname(__FILE__) . '/../vendor/openflights/airports.dat'));
$tzData = array();
foreach ($flightData as $data) {
	if ($data[11] !== '\N') {
		$tzData[$data[4]] = $data[11];
	}
}

// set local times ...
date_default_timezone_set('Europe/Berlin');
$timezone = new DateTimeZone($tzData[$departureAirport]);
$fromLocal = new DateTime($from . ' ' . $timezone->getName());
$timezone = new DateTimeZone($tzData[$destinationAirport]);
$toLocal = new DateTime($to . ' ' . $timezone->getName());

// ... and calculate duration
$diff = $toLocal->diff($fromLocal, true);
$duration = $diff->format("P%dDT%hH%iM");

echo '{"from":"' . $fromLocal->format("Y-m-d\TH:iP") . '","to":"' . $toLocal->format("Y-m-d\TH:iP") . '","duration":"' . $duration . '"}';
