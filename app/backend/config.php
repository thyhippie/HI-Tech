<?php
declare(strict_types=1);

const CONTACT_MAX_BODY_BYTES = 10000;
const CONTACT_MAX_NAME_LENGTH = 120;
const CONTACT_MAX_EMAIL_LENGTH = 254;
const CONTACT_MAX_PHONE_LENGTH = 32;
const CONTACT_MAX_MESSAGE_LENGTH = 5000;
const BUSINESS_EMAIL = 'info@hitechsavvys.co.za';
const BUSINESS_NAME = "HI TECH SAVVY'S Engineering";
const MAIL_FROM_ADDRESS = 'info@hitechsavvys.co.za';
const MAIL_FROM_NAME = "HI TECH SAVVY'S Engineering";

function environment_value(string $name): string
{
    $value = getenv($name);
    return $value === false ? '' : trim($value);
}

function contact_storage_file(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'contact-submissions.jsonl';
}
