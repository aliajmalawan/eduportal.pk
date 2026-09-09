<?php 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('MysqliDb.php');

if (!isset($m) || !($m instanceof MysqliDb)) {
    try {
        // MysqliDb connects lazily: the constructor only stores settings,
        // and the real connection is made inside connect() when mysqli()
        // is first called — which is why the @ belongs on that call, not
        // on the constructor.
        //
        // The @ is deliberate. A failed connection makes mysqli emit a PHP
        // *warning* (the host name and the absolute path to MysqliDb.php)
        // before it ever throws, and a warning cannot be caught — with
        // display_errors on it prints straight into the public response,
        // ahead of the clean 503 message below. Nothing is lost by
        // suppressing it: the exception is caught and logged in full.
        $m = new MysqliDb('localhost', 'smsknowc_edupotalwebsite_user', '', 'smsknowc_edupotalwebsite');
        $db = @$m->mysqli();
        if ($db->connect_errno) {
            throw new RuntimeException('MySQL connect error ' . $db->connect_errno . ': ' . $db->connect_error);
        }
        mysqli_set_charset($db, 'utf8mb4');
    } catch (Throwable $e) {
        // Full diagnostics go to the server error log only. The public
        // response deliberately names no file, database engine or
        // credential location — the previous message pointed visitors
        // straight at includes/callMe.php as the place credentials live.
        error_log('[EduPortal] Database connection failed: ' . $e->getMessage()
            . ' (see includes/callMe.php for connection settings)');
        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Retry-After: 120');
        exit('Service temporarily unavailable. Please try again in a few minutes.');
    }
}

if (!defined('DOMAIN')) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === 'eduportal.pk' || $host === 'www.eduportal.pk') {
        // Production
        define('DOMAIN', 'https://eduportal.pk/');
        define('ADMIN', 'https://eduportal.pk/cms/');
    } else {
        // Local/dev: build the base URL from the actual host + sub-folder
        // (e.g. http://localhost/eduportal/) so links never leak to production.
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $siteRoot = str_replace('\\', '/', rtrim(dirname(__DIR__), '/\\'));
        $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
        $basePath = ($docRoot !== '' && str_starts_with($siteRoot, $docRoot))
            ? substr($siteRoot, strlen($docRoot))
            : '';
        define('DOMAIN', $scheme . '://' . $host . $basePath . '/');
        define('ADMIN', $scheme . '://' . $host . $basePath . '/cms/');
    }
}

?>