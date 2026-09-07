<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

function send_quote_emails(string $name, string $email, string $phone, string $message): array
{
    $safeName = str_replace(["\r", "\n"], '', $name);
    $safeEmail = str_replace(["\r", "\n"], '', $email);
    $safePhone = str_replace(["\r", "\n"], '', $phone);
    $subjectPrefix = BUSINESS_NAME . ' - ';
    $headers = implode("\r\n", [
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        'Reply-To: ' . $safeEmail,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ]);

    $businessBody = "New quote request\n\n"
        . "Name: " . $safeName . "\n"
        . "Email: " . $safeEmail . "\n"
        . "Phone: " . $safePhone . "\n"
        . "Requirements:\n" . $message . "\n";
    $customerBody = "Hello " . $safeName . ",\n\n"
        . "Thank you for requesting a quote from " . BUSINESS_NAME . ". "
        . "We have received your project requirements and our engineers will contact you shortly on " . $safePhone . ".\n\n"
        . "Your requirements:\n" . $message . "\n\n"
        . "Regards,\n" . BUSINESS_NAME;

    return [
        'business' => @mail(BUSINESS_EMAIL, $subjectPrefix . 'New quote request from ' . $safeName, $businessBody, $headers),
        'customer' => @mail($safeEmail, $subjectPrefix . 'Quote request received', $customerBody, $headers),
    ];
}