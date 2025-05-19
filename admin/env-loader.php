<?php

if (!function_exists('nova_ai_load_env_file')) {
    function nova_ai_load_env_file($path = null)
    {
        $envFile = $path ?? dirname(__DIR__) . '/.env';

        if (!file_exists($envFile)) {
            error_log("[nova-ai] .env file not found at $envFile");
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (!array_key_exists($key, $_ENV)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    nova_ai_load_env_file(); // Standardaufruf
}
