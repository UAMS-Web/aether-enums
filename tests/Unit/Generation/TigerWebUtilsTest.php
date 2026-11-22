<?php

declare(strict_types=1);

use UAMSWeb\Aether\Enums\Generation\TigerWebUtils;

/**
 * The TIGERweb requests and response handling (UAMS-Web/aether-enums#27), with the HTTP GET
 * replaced by a recorder so nothing reaches the network.
 *
 * @command  vendor/bin/pest tests/Unit/Generation/TigerWebUtilsTest.php
 */
afterEach(function (): void {
    TigerWebUtils::useHttpGet(null);
});

const TIGERWEB_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

const TIGERWEB_COMMON_FIELDS = 'NAME,BASENAME,STATE,GEOID,FUNCSTAT,MTFCC,OID,LSADC,AREALAND,AREAWATER,OBJECTID,CENTLAT,CENTLON,INTPTLAT,INTPTLON';

/**
 * Answer every TIGERweb request with the given body and record the requests.
 *
 * @param  list<array{url: string, headers: array<int|string, string>, timeout: int, userAgent: string}>  $requests
 */
function tigerWebAnswer(?string $body, array &$requests): void
{
    TigerWebUtils::useHttpGet(static function (string $url, array $headers, int $timeout, string $userAgent) use ($body, &$requests): ?string {
        $requests[] = ['url' => $url, 'headers' => $headers, 'timeout' => $timeout, 'userAgent' => $userAgent];

        return $body;
    });
}

it('requests the layer with the encoded fields, the geometry flag, the headers, the timeout and the user agent', function (): void {
    $requests = [];
    tigerWebAnswer('{"features":[]}', $requests);

    $without = TigerWebUtils::fetchMapServerRecords('ACS2025', 7, ['NAME', 'GEOID']);
    $with = TigerWebUtils::fetchMapServerRecords('State_County', 12, ['A B', 'C/D'], true);

    expect($without)->toBe([])
        ->and($with)->toBe([])
        ->and($requests[0]['url'])->toBe('https://tigerweb.geo.census.gov/arcgis/rest/services/TIGERweb/ACS2025/MapServer/7/query?where=1%3D1&outFields=NAME%2CGEOID&returnGeometry=false&f=json')
        ->and($requests[1]['url'])->toBe('https://tigerweb.geo.census.gov/arcgis/rest/services/TIGERweb/State_County/MapServer/12/query?where=1%3D1&outFields=A%20B%2CC%2FD&returnGeometry=true&f=json')
        ->and($requests[0]['headers'])->toBe([
            'Accept' => 'application/json,*/*',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Connection' => 'keep-alive',
        ])
        ->and($requests[0]['timeout'])->toBe(60)
        ->and($requests[0]['userAgent'])->toBe(TIGERWEB_USER_AGENT);
});

it('returns the attributes of each feature, skipping features without a usable attributes array', function (): void {
    $requests = [];
    tigerWebAnswer(json_encode(['features' => [
        ['attributes' => ['NAME' => 'Pulaski', 'GEOID' => '05119']],
        'not a feature',
        ['attributes' => 'not an array'],
        ['geometry' => []],
        ['attributes' => ['NAME' => 'Saline', 'GEOID' => '05125']],
    ]], JSON_THROW_ON_ERROR), $requests);

    expect(TigerWebUtils::fetchMapServerRecords('State_County', 1, ['NAME']))->toBe([
        ['NAME' => 'Pulaski', 'GEOID' => '05119'],
        ['NAME' => 'Saline', 'GEOID' => '05125'],
    ]);
});

it('returns null when the request fails, the body is not JSON, or the API reports an error', function (?string $body): void {
    $requests = [];
    tigerWebAnswer($body, $requests);

    expect(TigerWebUtils::fetchMapServerRecords('State_County', 1, ['NAME']))->toBeNull()
        ->and($requests)->toHaveCount(1);
})->with([
    'a failed request' => [null],
    'text that is not JSON' => ['not json'],
    'JSON that is not an object' => ['"text"'],
    'an error object' => ['{"error":{"code":400}}'],
    'an error object next to features' => ['{"error":"x","features":[{"attributes":{"NAME":"A"}}]}'],
]);

it('treats a missing or malformed features value as an empty result', function (string $body): void {
    $requests = [];
    tigerWebAnswer($body, $requests);

    expect(TigerWebUtils::fetchMapServerRecords('State_County', 1, ['NAME']))->toBe([]);
})->with([
    'no features' => ['{"other":1}'],
    'features that is not an array' => ['{"features":"none"}'],
]);

it('asks for the county fields on the State_County service', function (): void {
    $requests = [];
    tigerWebAnswer('{"features":[{"attributes":{"COUNTY":"119"}}]}', $requests);

    $records = TigerWebUtils::fetchCountyRecords(3);

    expect($records)->toBe([['COUNTY' => '119']])
        ->and($requests[0]['url'])->toBe('https://tigerweb.geo.census.gov/arcgis/rest/services/TIGERweb/State_County/MapServer/3/query?where=1%3D1&outFields='.rawurlencode('COUNTY,COUNTYNS,COUNTYCC,'.TIGERWEB_COMMON_FIELDS).'&returnGeometry=false&f=json');
});

it('asks for the state fields on the State_County service', function (): void {
    $requests = [];
    tigerWebAnswer('{"features":[{"attributes":{"STUSAB":"AR"}}]}', $requests);

    $records = TigerWebUtils::fetchStateRecords(0);

    expect($records)->toBe([['STUSAB' => 'AR']])
        ->and($requests[0]['url'])->toBe('https://tigerweb.geo.census.gov/arcgis/rest/services/TIGERweb/State_County/MapServer/0/query?where=1%3D1&outFields='.rawurlencode('STUSAB,STATENS,DIVISION,REGION,'.TIGERWEB_COMMON_FIELDS).'&returnGeometry=false&f=json');
});

it('passes a failed county or state request through as null', function (): void {
    $requests = [];
    tigerWebAnswer(null, $requests);

    expect(TigerWebUtils::fetchCountyRecords(1))->toBeNull()
        ->and(TigerWebUtils::fetchStateRecords(0))->toBeNull();
});
