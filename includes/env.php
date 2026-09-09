<?php
/**
 * Minimal .env loader — no Composer/vendor dependency exists in this
 * project, so this is a small dependency-free parser rather than pulling
 * in vlucas/phpdotenv for two variables.
 *
 * Only for secrets that must never touch the database, git, or frontend
 * code (currently: GOOGLE_PLACES_API_KEY, GOOGLE_PLACE_ID) — everything
 * else in this project continues to use ep_site_settings, which remains
 * the right place for non-secret/admin-editable configuration.
 */

if (!function_exists('ep_load_env')) {
    function ep_load_env(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $path = dirname(__DIR__) . '/.env';
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($value !== '' && $value[0] === '"' && substr($value, -1) === '"') {
                $value = substr($value, 1, -1);
            } elseif ($value !== '' && $value[0] === "'" && substr($value, -1) === "'") {
                $value = substr($value, 1, -1);
            }
            if ($key === '') {
                continue;
            }
            // getenv(false)-safe: only set if not already defined by the
            // real server environment, which always takes precedence.
            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }
        }
    }
}

if (!function_exists('ep_env')) {
    function ep_env(string $key, string $default = ''): string
    {
        ep_load_env();
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}
