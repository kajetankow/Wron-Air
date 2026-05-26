<?php
if (!defined('APP_ACCESS')) exit('Brak dostępu');
function extractAirportCode(string $value): ?string {
    if (preg_match('/\[([A-Z]{3})\]$/', $value, $matches)) {
        return $matches[1];
    }
    return null;
}

function getAirportByCode(PDO $pdo, string $code): ?array {
    $stmt = $pdo->prepare("
        SELECT code, city, country, display_name
        FROM airports
        WHERE code = :code
        LIMIT 1
    ");
    $stmt->execute([':code' => $code]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function getAirportDisplayName(PDO $pdo, string $code): string {
    $airport = getAirportByCode($pdo, $code);
    return $airport['display_name'] ?? $code;
}

function formatDurationLabel(?int $minutes): string {
    if (!$minutes || $minutes <= 0) {
        return 'Brak info';
    }

    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;

    return sprintf('%dh %02dmin', $hours, $mins);
}

function formatPricePln($price): string {
    if (!is_numeric($price)) {
        return 'Brak info';
    }

    return number_format((float)$price, 2, ',', ' ') . ' PLN*';
}

function formatTimeValue(?string $time): string {
    if (!$time) {
        return '--:--';
    }

    return substr($time, 0, 5);
}

function parseUtcOffsetToMinutes(?string $utc): int {
    if (!$utc) {
        return 0;
    }

    if (!preg_match('/UTC\s*([+-])(\d{1,2})(?::(\d{2}))?/i', $utc, $matches)) {
        return 0;
    }

    $sign = $matches[1] === '-' ? -1 : 1;
    $hours = (int)$matches[2];
    $minutes = isset($matches[3]) ? (int)$matches[3] : 0;

    return $sign * ($hours * 60 + $minutes);
}

function timeToMinutes(?string $time): int {
    if (!$time) {
        return 0;
    }

    [$hours, $minutes] = array_pad(explode(':', $time), 2, 0);

    return ((int)$hours * 60) + (int)$minutes;
}

function localTimeToUtcMinutes(?string $time, ?string $utc): int {
    $localMinutes = timeToMinutes($time);
    $offsetMinutes = parseUtcOffsetToMinutes($utc);

    return $localMinutes - $offsetMinutes;
}

function calculateLayoverMinutes(array $previousFlight, array $nextFlight): int {
    $arrivalUtc = localTimeToUtcMinutes($previousFlight['arrival_time'] ?? null, $previousFlight['arrival_utc'] ?? null);
    $nextDepartureUtc = localTimeToUtcMinutes($nextFlight['departure_time'] ?? null, $nextFlight['departure_utc'] ?? null);

    $diff = $nextDepartureUtc - $arrivalUtc;

    while ($diff < 40) {
        $diff += 1440;
    }

    return $diff;
}

function getAllActiveFlights(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT
            id,
            flight_name,
            origin_code,
            destination_code,
            departure_time,
            departure_utc,
            arrival_time,
            arrival_utc,
            duration_minutes,
            operator_name,
            price_economy,
            price_premium_economy,
            price_business
        FROM flights
        WHERE active = 1
        ORDER BY departure_time ASC
    ");

    return $stmt->fetchAll();
}

function buildFlightGraphByOrigin(array $flights): array {
    $graph = [];

    foreach ($flights as $flight) {
        $graph[$flight['origin_code']][] = $flight;
    }

    return $graph;
}

function buildItineraryFromPath(array $path): array {
    $first = $path[0];
    $last = $path[count($path) - 1];

    $operators = [];
    $flightNames = [];
    $transferCodes = [];
    $duration = 0;
    $priceEconomy = 0.0;
    $pricePremium = 0.0;
    $priceBusiness = 0.0;
    $premiumAvailable = true;
    $businessAvailable = true;
    $layoverTotal = 0;

    foreach ($path as $index => $flight) {
        $duration += (int)$flight['duration_minutes'];
        $priceEconomy += (float)$flight['price_economy'];

        $operators[] = $flight['operator_name'];
        $flightNames[] = $flight['flight_name'];

        if ($index > 0) {
            $transferCodes[] = $flight['origin_code'];
            $layoverTotal += calculateLayoverMinutes($path[$index - 1], $flight);
        }

        if ($flight['price_premium_economy'] === null) {
            $premiumAvailable = false;
        } else {
            $pricePremium += (float)$flight['price_premium_economy'];
        }

        if ($flight['price_business'] === null) {
            $businessAvailable = false;
        } else {
            $priceBusiness += (float)$flight['price_business'];
        }
    }

    $operators = array_values(array_unique($operators));
    $flightNames = array_values(array_unique($flightNames));

    $stops = max(0, count($path) - 1);
    if ($stops === 0) {
        $transferLabel = 'BEZPOŚREDNI';
    } elseif ($stops === 1) {
        $transferLabel = '1 PRZESIADKA';
    } else {
        $transferLabel = $stops . ' PRZESIADKI';
    }

    return [
        'id' => implode('-', array_column($path, 'id')),
        'flight_type' => $stops === 0 ? 'direct' : 'transfer',
        'stops' => $stops,
        'segments' => $path,
        'origin_code' => $first['origin_code'],
        'destination_code' => $last['destination_code'],
        'departure_time' => $first['departure_time'],
        'departure_utc' => $first['departure_utc'],
        'arrival_time' => $last['arrival_time'],
        'arrival_utc' => $last['arrival_utc'],
        'total_duration' => $duration + $layoverTotal,
        'flight_name' => implode(' + ', $flightNames),
        'operator_name' => implode(', ', $operators),
        'price_economy' => $priceEconomy,
        'price_premium_economy' => $premiumAvailable ? $pricePremium : null,
        'price_business' => $businessAvailable ? $priceBusiness : null,
        'total_price' => $priceEconomy,
        'transfer_codes' => $transferCodes,
        'transfer_label' => $transferLabel
    ];
}

function searchFlightPaths(
    array $graph,
    string $currentCode,
    string $targetCode,
    array $currentPath = [],
    array $visitedAirports = [],
    int $maxStops = 4,
    int $maxResults = 50,
    array &$results = []
): void {
    if (count($results) >= $maxResults) {
        return;
    }

    if (!isset($graph[$currentCode])) {
        return;
    }

    foreach ($graph[$currentCode] as $flight) {
        $nextCode = $flight['destination_code'];

        if (in_array($nextCode, $visitedAirports, true)) {
            continue;
        }

        $newPath = [...$currentPath, $flight];
        $legs = count($newPath);
        $stops = $legs - 1;

        if ($stops > $maxStops) {
            continue;
        }

        if (!empty($currentPath)) {
            $previousFlight = $currentPath[count($currentPath) - 1];
            $layover = calculateLayoverMinutes($previousFlight, $flight);

            if ($layover < 40 || $layover > 720) {
                continue;
            }
        }

        if ($nextCode === $targetCode) {
            $results[] = buildItineraryFromPath($newPath);
            if (count($results) >= $maxResults) {
                return;
            }
            continue;
        }

        searchFlightPaths(
            $graph,
            $nextCode,
            $targetCode,
            $newPath,
            [...$visitedAirports, $nextCode],
            $maxStops,
            $maxResults,
            $results
        );
    }
}

function findFlightOptions(PDO $pdo, string $fromCode, string $toCode, int $maxStops = 4, int $maxResults = 50): array {
    $allFlights = getAllActiveFlights($pdo);
    $graph = buildFlightGraphByOrigin($allFlights);
    $results = [];

    searchFlightPaths(
        $graph,
        $fromCode,
        $toCode,
        [],
        [$fromCode],
        $maxStops,
        $maxResults,
        $results
    );

    usort($results, function ($a, $b) {
        $priceComparison = ($a['total_price'] ?? 999999) <=> ($b['total_price'] ?? 999999);

        if ($priceComparison !== 0) {
            return $priceComparison;
        }

        return ($a['total_duration'] ?? 999999) <=> ($b['total_duration'] ?? 999999);
    });

    return $results;
}

function getClassPrices(array $flight): array {
    return [
        'economy' => $flight['price_economy'] ?? null,
        'premium' => $flight['price_premium_economy'] ?? null,
        'business' => $flight['price_business'] ?? null
    ];
}