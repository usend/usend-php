<?php

// Prints the settings to paste into PHPMailer, Symfony Mailer or Laravel.
require __DIR__ . '/../vendor/autoload.php';

$usend = new Usend\Client();
print_r($usend->smtpConfig(587));
