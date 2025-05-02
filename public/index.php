<?php

use Marcgoertz\FlightDuration;
use OpenApi\Attributes as OA;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

/**
 * @OA\Info(
 *     title="Flight Duration API",
 *     description="An API for calculating flight duration between airports",
 *     @OA\Contact(name="Marc Görtz", url="https://marcgoertz.de/")
 * )
 * @OA\Server(url="http://localhost:8000", description="Development server")
 */
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();

/**
 * @OA\Get(
 *     path="/",
 *     summary="Calculate flight duration between two airports",
 *     operationId="getFlightDuration",
 *     tags={"Travel"},
 *     @OA\Parameter(
 *         name="from",
 *         in="query",
 *         description="Departure datetime (format: YYYY-MM-DDTHH:MM)",
 *         required=true,
 *         @OA\Schema(type="string", format="date-time", example="2023-05-01T10:30")
 *     ),
 *     @OA\Parameter(
 *         name="to",
 *         in="query",
 *         description="Arrival datetime (format: YYYY-MM-DDTHH:MM)",
 *         required=true,
 *         @OA\Schema(type="string", format="date-time", example="2023-05-01T14:45")
 *     ),
 *     @OA\Parameter(
 *         name="departureAirport",
 *         in="query",
 *         description="IATA code for departure airport",
 *         required=true,
 *         @OA\Schema(type="string", pattern="^[A-Z]{3}$", example="FRA")
 *     ),
 *     @OA\Parameter(
 *         name="destinationAirport",
 *         in="query",
 *         description="IATA code for destination airport",
 *         required=true,
 *         @OA\Schema(type="string", pattern="^[A-Z]{3}$", example="JFK")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Flight duration information",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="from", type="string", format="date-time", example="2023-05-01T10:30+02:00"),
 *             @OA\Property(property="to", type="string", format="date-time", example="2023-05-01T14:45-04:00"),
 *             @OA\Property(property="duration", type="string", format="duration", example="P0DT8H15M")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input parameters",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="type", type="string", example="/error/invalid-argument"),
 *             @OA\Property(property="title", type="string", example="Invalid Argument"),
 *             @OA\Property(property="detail", type="string", example="Invalid departure airport code. Expected: 3 uppercase letters")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="type", type="string", example="/error/server"),
 *             @OA\Property(property="title", type="string", example="Server Error"),
 *             @OA\Property(property="detail", type="string", example="An unexpected error occurred")
 *         )
 *     )
 * )
 */
$app->get('/', function (Request $request, Response $response) {
	$queryParams = $request->getQueryParams();
	$from = $queryParams['from'] ?? null;
	$to = $queryParams['to'] ?? null;
	$departureAirport = $queryParams['departureAirport'] ?? null;
	$destinationAirport = $queryParams['destinationAirport'] ?? null;

	try {
		$flightDuration = new FlightDuration();
		$data = $flightDuration->getDuration(
			$from,
			$to,
			$departureAirport,
			$destinationAirport
		);

		$response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

		return $response
			->withHeader('Content-Type', 'application/json')
			->withStatus(200);
	} catch (InvalidArgumentException $e) {
		$errorData = [
			'type' => '/error/invalid-argument',
			'title' => 'Invalid Argument',
			'detail' => $e->getMessage(),
		];

		$response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));

		return $response
			->withHeader('Content-Type', 'application/json')
			->withStatus(400);
	} catch (RuntimeException $e) {
		$errorData = [
			'type' => '/error/data',
			'title' => 'Data Error',
			'detail' => $e->getMessage(),
		];

		$response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));

		return $response
			->withHeader('Content-Type', 'application/json')
			->withStatus(500);
	} catch (Throwable $e) {
		$errorData = [
			'type' => '/error/server',
			'title' => 'Server Error',
			'detail' => 'An unexpected error occurred',
		];

		$response->getBody()->write(json_encode($errorData, JSON_THROW_ON_ERROR));

		return $response
			->withHeader('Content-Type', 'application/json')
			->withStatus(500);
	}
});

/**
 * @OA\Options(
 *     path="/{routes}",
 *     summary="CORS preflight request",
 *     @OA\Parameter(
 *         name="routes",
 *         in="path",
 *         description="Route path",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="Successful CORS preflight"
 *     )
 * )
 */
$app->options('/{routes:.+}', function (Request $request, Response $response) {
	return $response;
});

// Add CORS support
$app->add(function (Request $request, RequestHandler $handler) {
	$response = $handler->handle($request);
	return $response
		->withHeader('Access-Control-Allow-Origin', '*')
		->withHeader('Access-Control-Allow-Headers', 'Content-Type')
		->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
});

$app->run();
