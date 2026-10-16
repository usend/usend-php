<?php

// USEND_API_KEY=us_... php examples/send.php
require __DIR__ . '/../vendor/autoload.php';

$usend = new Usend\Client();

$email = $usend->emails->send(
    ['from' => 'Acme <hello@acme.com>', 'to' => 'ann@example.com', 'subject' => 'Hello', 'html' => '<p>Hello from usend</p>'],
    'example-1',
);
echo $email['id'], "\n";
