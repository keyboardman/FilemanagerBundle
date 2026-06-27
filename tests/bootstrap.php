<?php

require dirname(__DIR__).'/vendor/autoload.php';

loadS3ContentEnvFile(getenv('S3_CONTENT_ENV_FILE') ?: dirname(__DIR__).'/.env.test.local');

function loadS3ContentEnvFile(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ('' === $line || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\"'");

        if ('' === $name || !str_starts_with($name, 'S3_CONTENT_')) {
            continue;
        }

        if (false === getenv($name)) {
            putenv($name.'='.$value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
