<?php

declare(strict_types=1);

// Compare a separately exported Workspaces document to the routes exercised by the HTTP tests.
use CommuniQate\Workspaces\Tests\EndpointCases;
use CommuniQate\Workspaces\Webhooks\WebhookEvent;

require_once __DIR__.'/../tests/EndpointCases.php';
require_once __DIR__.'/../src/Webhooks/WebhookEvent.php';

if ($argc !== 2 || !is_file($argv[1])) {
    fwrite(STDERR, "Usage: php tools/check-api.php /path/to/openapi.json\n");
    exit(2);
}
$document = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$expected = [];
foreach ($document['paths'] as $path => $operations) {
    foreach ($operations as $verb => $operation) {
        if (in_array($verb, ['get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace'], true)) {
            $expected[] = strtoupper($verb).' '.$path.' '.$operation['operationId'];
        }
    }
}
$actual = [];
foreach (EndpointCases::all() as $operation => [, , $verb, $path]) {
    $actual[] = "$verb $path $operation";
}
foreach (array_keys($document['webhooks']) as $name) {
    $expected[] = "webhook $name";
}
foreach (WebhookEvent::cases() as $event) {
    $actual[] = "webhook $event->value";
}
$missing = array_diff($expected, $actual);
$obsolete = array_diff($actual, $expected);
foreach ($missing as $item) {
    fwrite(STDERR, "Missing in SDK: $item\n");
}
foreach ($obsolete as $item) {
    fwrite(STDERR, "Absent from API: $item\n");
}
if ($missing || $obsolete) {
    exit(1);
}
printf("Matched %d operations and %d webhook events.\n", count(EndpointCases::all()), count(WebhookEvent::cases()));
