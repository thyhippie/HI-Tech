<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

function send_quote_sms(string $name, string $phone): bool
{
    $accountSid = environment_value('TWILIO_ACCOUNT_SID');
    $authToken = environment_value('TWILIO_AUTH_TOKEN');
    $fromNumber = environment_value('TWILIO_FROM_NUMBER');
    if ($accountSid === '' || $authToken === '' || $fromNumber === '') {
        return false;
    }

    $recipient = preg_replace('/^0/', '+27', $phone);
    if (!is_string($recipient) || !preg_match('/^\+?[1-9][0-9]{7,14}$/', $recipient)) {
        return false;
    }

    $firstName = explode(' ', str_replace(["\r", "\n"], '', $name))[0];
    $body = "Hi " . $firstName . ", your HI TECH SAVVY'S quote request has been received and will be processed by our team. We will contact you shortly.";
    $endpoint = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($accountSid) . '/Messages.json';
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Authorization: Basic ' . base64_encode($accountSid . ':' . $authToken),
                'Content-Type: application/x-www-form-urlencoded',
            ],
            'content' => http_build_query([
                'To' => $recipient,
                'From' => $fromNumber,
                'Body' => $body,
            ]),
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($endpoint, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    return $response !== false && preg_match('/\s2\d{2}\s/', $statusLine) === 1;
}