<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'ContactSubmissionStore.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'Mailer.php';
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'SmsNotifier.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'message' => 'Only POST requests are accepted.']);
    exit;
}

$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > CONTACT_MAX_BODY_BYTES) {
    http_response_code(413);
    echo json_encode(['success' => false, 'message' => 'The request is too large.']);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$rawBody = file_get_contents('php://input');
$input = stripos($contentType, 'application/json') !== false ? json_decode($rawBody, true) : $_POST;

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit;
}

if (stripos($contentType, 'application/json') !== false && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON request data.']);
    exit;
}

$name = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));
$message = trim((string)($input['message'] ?? ''));

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[0-9+(). -]{7,32}$/', $phone) || $message === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please provide your name, a valid email address and a valid phone number.']);
    exit;
}

if (strlen($name) > CONTACT_MAX_NAME_LENGTH || strlen($email) > CONTACT_MAX_EMAIL_LENGTH || strlen($phone) > CONTACT_MAX_PHONE_LENGTH || strlen($message) > CONTACT_MAX_MESSAGE_LENGTH) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'One or more fields are too long.']);
    exit;
}

$submission = [
    'submitted_at' => gmdate('c'),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
];

if (!save_contact_submission($submission)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'The server could not save your message.']);
    exit;
}

$emailResults = send_quote_emails($name, $email, $phone, $message);
$smsSent = send_quote_sms($name, $phone);
if (!$emailResults['business'] || !$emailResults['customer'] || !$smsSent) {
    http_response_code(200);
    $delivery = [];
    if (!$emailResults['business'] || !$emailResults['customer']) {
        $delivery[] = 'confirmation email delivery is not configured yet';
    }
    if (!$smsSent) {
        $delivery[] = 'SMS confirmation is not configured yet';
    }
    echo json_encode([
        'success' => true,
        'message' => 'Your quote has been received and will be processed by our team. ' . implode(' and ', $delivery) . '.',
    ]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Thanks ' . explode(' ', $name)[0] . '! Your quote has been received and will be processed by our team. Confirmation emails and SMS were sent.']);
