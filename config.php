<?php
declare(strict_types=1);

/*
 * ================================================================
 * Facebook Login Demo - Main Configuration
 * ================================================================
 *
 * IMPORTANT:
 * 1. Replace FACEBOOK_APP_ID with your real Meta App ID.
 * 2. Replace FACEBOOK_APP_SECRET with your real Meta App Secret.
 * 3. Never put the App Secret in HTML, CSS, JavaScript, or Git.
 * 4. The default local callback URL must match Meta exactly.
 */

// -------------------------
// Database configuration
// -------------------------
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'facebook_login');
define('DB_USER', 'root');
define('DB_PASS', ''); // WAMP commonly uses an empty root password by default.

// -------------------------
// Meta / Facebook settings
// -------------------------
define('FACEBOOK_APP_ID', 'FACEBOOK_APP_ID');
define('FACEBOOK_APP_SECRET', 'FACEBOOK_APP_SECRET');

define(
    'FACEBOOK_REDIRECT_URI',
    'http://localhost/facebook-login/callback.php'
);

define('FACEBOOK_GRAPH_VERSION', 'v25.0');

define(
    'FACEBOOK_AUTH_URL',
    'https://www.facebook.com/' . FACEBOOK_GRAPH_VERSION . '/dialog/oauth'
);

define(
    'FACEBOOK_TOKEN_URL',
    'https://graph.facebook.com/' . FACEBOOK_GRAPH_VERSION . '/oauth/access_token'
);

define(
    'FACEBOOK_GRAPH_URL',
    'https://graph.facebook.com/' . FACEBOOK_GRAPH_VERSION
);

// -------------------------
// Session cookie hardening
// -------------------------
// localhost development uses HTTP, so secure=false is intentional.
// Change to true when your production site uses HTTPS.

ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

session_start();

// -------------------------
// PDO database connection
// -------------------------
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {

    // Technical information goes to the server log.
    // Do not expose DB details to normal users.
    error_log(
        'Facebook Login Demo DB error: ' . $e->getMessage()
    );

    http_response_code(500);

    exit(
        'A temporary server error occurred. Please make sure MySQL is running and the database is configured.'
    );
}