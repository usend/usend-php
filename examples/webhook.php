<?php

// Point a usend webhook at this script. USEND_WEBHOOK_SECRET=whsec_...
require __DIR__ . '/../vendor/autoload.php';

try {
    $event = Usend\Webhook::verify(file_get_contents('php://input'), getallheaders(), getenv('USEND_WEBHOOK_SECRET'));
} catch (Usend\UsendException $e) {
    http_response_code(400);
    exit;
}

error_log('usend event: ' . $event['type']);
http_response_code(204);
