<?php

// Router script for `php -S` used by tests/run.php. Not part of the SDK.
//
// /domains/flaky       503 first, then 200
// /domains/ratelimited 429 (Retry-After: 0) first, then 200
// /domains/error       422 with an API error body
// /domains/boom        always 500
// anything else        200, echoing the request as JSON

$state = getenv('USEND_MOCK_STATE') ?: sys_get_temp_dir() . '/usend_mock_state.json';
$counts = is_file($state) ? (json_decode((string) file_get_contents($state), true) ?: []) : [];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$counts[$path] = ($counts[$path] ?? 0) + 1;
file_put_contents($state, json_encode($counts));

header('Content-Type: application/json');
$n = $counts[$path];

if ($path === '/domains/flaky' && $n === 1) {
    http_response_code(503);
    echo json_encode(['statusCode' => 503, 'name' => 'application_error', 'message' => 'try again']);
    return;
}
if ($path === '/domains/ratelimited' && $n === 1) {
    http_response_code(429);
    header('Retry-After: 0');
    echo json_encode(['statusCode' => 429, 'name' => 'rate_limit_exceeded', 'message' => 'slow down']);
    return;
}
if ($path === '/domains/error') {
    http_response_code(422);
    echo json_encode(['statusCode' => 422, 'name' => 'validation_error', 'message' => 'bad input']);
    return;
}
if ($path === '/emails/boom/cancel') {
    http_response_code(500);
    echo json_encode(['statusCode' => 500, 'name' => 'application_error', 'message' => 'boom']);
    return;
}
if ($path === '/emails' && ($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? '') === 'flaky-key' && $n === 1) {
    http_response_code(503);
    echo json_encode(['statusCode' => 503, 'name' => 'application_error', 'message' => 'try again']);
    return;
}
if ($path === '/domains/boom') {
    http_response_code(500);
    echo json_encode(['statusCode' => 500, 'name' => 'application_error', 'message' => 'boom']);
    return;
}

$headers = [];
foreach ($_SERVER as $k => $v) {
    if (str_starts_with($k, 'HTTP_')) {
        $headers[strtolower(str_replace('_', '-', substr($k, 5)))] = $v;
    }
}
echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'headers' => $headers,
    'body' => file_get_contents('php://input'),
    'hits' => $n,
]);
