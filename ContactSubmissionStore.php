<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CONTACT / QUOTE REQUEST HANDLER
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

function json_response(
    bool $success,
    string $message,
    int $statusCode = 200
): void {

    http_response_code($statusCode);

    echo json_encode(
        [
            'success' => $success,
            'message' => $message
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| SAVE CONTACT / QUOTE SUBMISSION
|--------------------------------------------------------------------------
*/

function save_contact_submission(array $submission): bool
{
    $storageFile = contact_storage_file();
    $storageDirectory = dirname($storageFile);

    if (
        !is_dir($storageDirectory) &&
        !mkdir($storageDirectory, 0750, true) &&
        !is_dir($storageDirectory)
    ) {
        return false;
    }

    $line = json_encode(
        $submission,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    if ($line === false) {
        return false;
    }

    return file_put_contents(
        $storageFile,
        $line . PHP_EOL,
        FILE_APPEND | LOCK_EX
    ) !== false;
}


/*
|--------------------------------------------------------------------------
| SEND EMAILS
|--------------------------------------------------------------------------
*/

function send_quote_emails(
    string $name,
    string $email,
    string $phone,
    string $message
): array {

    /*
    | Prevent email header injection
    */

    $safeName = str_replace(
        ["\r", "\n"],
        '',
        $name
    );

    $safeEmail = str_replace(
        ["\r", "\n"],
        '',
        $email
    );

    $safePhone = str_replace(
        ["\r", "\n"],
        '',
        $phone
    );


    /*
    |--------------------------------------------------------------------------
    | EMAIL HEADERS
    |--------------------------------------------------------------------------
    */

    $headers = implode("\r\n", [
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        'Reply-To: ' . $safeEmail,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8'
    ]);


    /*
    |--------------------------------------------------------------------------
    | BUSINESS EMAIL
    |--------------------------------------------------------------------------
    */

    $businessBody =
        "NEW QUOTE REQUEST\n" .
        "==================\n\n" .

        "Name: " . $safeName . "\n" .
        "Email: " . $safeEmail . "\n" .
        "Phone: " . $safePhone . "\n\n" .

        "PROJECT REQUIREMENTS\n" .
        "--------------------\n" .
        $message . "\n";


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER CONFIRMATION
    |--------------------------------------------------------------------------
    */

    $customerBody =
        "Hello " . $safeName . ",\n\n" .

        "Thank you for requesting a quote from " .
        BUSINESS_NAME . ".\n\n" .

        "We have successfully received your quote request. " .
        "Our engineering team will review your requirements " .
        "and contact you shortly on " . $safePhone . ".\n\n" .

        "YOUR PROJECT REQUIREMENTS\n" .
        "-------------------------\n" .
        $message . "\n\n" .

        "Regards,\n" .
        BUSINESS_NAME;


    /*
    |--------------------------------------------------------------------------
    | SEND BUSINESS EMAIL
    |--------------------------------------------------------------------------
    */

    $businessMail = @mail(
        BUSINESS_EMAIL,
        BUSINESS_NAME . ' - New Quote Request',
        $businessBody,
        $headers
    );


    /*
    |--------------------------------------------------------------------------
    | SEND CUSTOMER EMAIL
    |--------------------------------------------------------------------------
    */

    $customerMail = @mail(
        $safeEmail,
        BUSINESS_NAME . ' - Quote Request Received',
        $customerBody,
        $headers
    );


    return [
        'business' => $businessMail,
        'customer' => $customerMail
    ];
}


/*
|--------------------------------------------------------------------------
| ONLY ACCEPT POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    json_response(
        false,
        'Invalid request method.',
        405
    );
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');


/*
|--------------------------------------------------------------------------
| VALIDATE NAME
|--------------------------------------------------------------------------
*/

if ($name === '') {

    json_response(
        false,
        'Please enter your name.',
        400
    );
}

if (mb_strlen($name) < 2) {

    json_response(
        false,
        'Please enter a valid name.',
        400
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE EMAIL
|--------------------------------------------------------------------------
*/

if ($email === '') {

    json_response(
        false,
        'Please enter your email address.',
        400
    );
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    json_response(
        false,
        'Please enter a valid email address.',
        400
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE PHONE
|--------------------------------------------------------------------------
*/

if ($phone === '') {

    json_response(
        false,
        'Please enter your phone number.',
        400
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE PROJECT REQUIREMENTS
|--------------------------------------------------------------------------
*/

if ($message === '') {

    json_response(
        false,
        'Please describe your project requirements.',
        400
    );
}

if (mb_strlen($message) < 10) {

    json_response(
        false,
        'Please provide more information about your project.',
        400
    );
}


/*
|--------------------------------------------------------------------------
| CREATE SUBMISSION
|--------------------------------------------------------------------------
*/

$submission = [
    'type' => 'quote_request',
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
    'submitted_at' => date('Y-m-d H:i:s'),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];


/*
|--------------------------------------------------------------------------
| SAVE SUBMISSION
|--------------------------------------------------------------------------
*/

try {

    $saved = save_contact_submission($submission);

} catch (Throwable $e) {

    error_log(
        'Quote storage error: ' . $e->getMessage()
    );

    $saved = false;
}


/*
|--------------------------------------------------------------------------
| SEND EMAILS
|--------------------------------------------------------------------------
*/

try {

    $emails = send_quote_emails(
        $name,
        $email,
        $phone,
        $message
    );

} catch (Throwable $e) {

    error_log(
        'Quote email error: ' . $e->getMessage()
    );

    $emails = [
        'business' => false,
        'customer' => false
    ];
}


/*
|--------------------------------------------------------------------------
| CHECK RESULT
|--------------------------------------------------------------------------
*/

if (!$saved && !$emails['business']) {

    json_response(
        false,
        'We could not process your quote request. Please try again later.',
        500
    );
}


/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

json_response(
    true,
    'Thank you! Your quote request has been received. Our engineering team will contact you shortly.'
);