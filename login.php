<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Generate a random OAuth state token.
// It protects against CSRF attacks.
$_SESSION['oauth_state'] = bin2hex(
    random_bytes(32)
);

$params = [

    'client_id' =>
        FACEBOOK_APP_ID,

    'redirect_uri' =>
        FACEBOOK_REDIRECT_URI,

    'state' =>
        $_SESSION['oauth_state'],

    'response_type' =>
        'code',

    'scope' =>
        'public_profile,email',
];

$authorizationUrl =
    FACEBOOK_AUTH_URL
    . '?'
    . http_build_query(
        $params,
        '',
        '&',
        PHP_QUERY_RFC3986
    );

header(
    'Location: ' . $authorizationUrl
);

exit;