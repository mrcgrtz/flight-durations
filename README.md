# Flight Durations (PHP)

Get actual flight durations using [timezone information](https://openflights.org/data.html) provided by OpenFlights.org.

## Required parameters

* `from` = departure datetime, i.e. `2016-11-10T16:25`
* `to` = arrival datetime, i.e. `2016-11-10T20:40`
* `departureAirport` = 3-letter IATA code of departure airport, i.e. `AMS`
* `destinationAirport` =  3-letter IATA code of destinationairport, i.e. `MIA`

## Sample requests

Using [HTTPie](https://httpie.org):

```bash
$ http --body https://flightduration.dev/ from==2016-11-10T16:25 to==2016-11-10T20:40 departureAirport==AMS destinationAirport==MIA
{
    "duration": "P0DT10H15M",
    "from": "2016-11-10T16:25+01:00",
    "to": "2016-11-10T20:40-05:00"
}

$ http --body https://flightduration.dev/ from==2016-12-14T13:50 to==2016-12-15T06:50 departureAirport==ZRH destinationAirport==HKT
{
    "duration": "P0DT11H0M",
    "from": "2016-12-14T13:50+01:00",
    "to": "2016-12-15T06:50+07:00"
}
```
