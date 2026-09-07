<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

function save_contact_submission(array $submission): bool
{
    $storageFile = contact_storage_file();
    $storageDirectory = dirname($storageFile);

    if (!is_dir($storageDirectory) && !mkdir($storageDirectory, 0750, true) && !is_dir($storageDirectory)) {
        return false;
    }

    $line = json_encode($submission, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($line === false) {
        return false;
    }

    return file_put_contents($storageFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
}
