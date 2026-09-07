<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Redirect the user back to index.php with a safe error code.
 */
function redirectWithError(string $code): never
{
    header(
        'Location: index.php?error=' . rawurlencode($code)
    );

    exit;
}

/**
 * Facebook user IDs are numeric strings.
 */
function isValidFacebookId(string $facebookId): bool
{
    return preg_match('/^[0-9]+$/', $facebookId) === 1;
}

/**
 * Perform a GET request to a Facebook endpoint.
 */
function facebookGet(string $url): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_FOLLOWLOCATION => false,

        CURLOPT_CONNECTTIMEOUT => 10,

        CURLOPT_TIMEOUT => 20,

        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($ch);

    $httpCode =
        (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {

        error_log(
            'Facebook Login Demo cURL error: '
            . $curlError
        );

        return [
            'ok' => false,
            'data' => [],
            'http_code' => $httpCode,
        ];
    }

    $data = json_decode(
        $response,
        true
    );

    if (!is_array($data)) {

        error_log(
            'Facebook Login Demo invalid JSON response from Facebook. HTTP '
            . $httpCode
        );

        return [
            'ok' => false,
            'data' => [],
            'http_code' => $httpCode,
        ];
    }

    return [

        'ok' =>
            $httpCode >= 200
            && $httpCode < 300
            && !isset($data['error']),

        'data' =>
            $data,

        'http_code' =>
            $httpCode,
    ];
}


// ---------------------------------------------------------------
// OAuth error handling
// ---------------------------------------------------------------

if (isset($_GET['error'])) {

    error_log(
        'Facebook Login Demo OAuth error code received.'
    );

    redirectWithError('oauth_denied');
}


// ---------------------------------------------------------------
// Read callback parameters
// ---------------------------------------------------------------

$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';


// ---------------------------------------------------------------
// Validate OAuth state
// ---------------------------------------------------------------

$expectedState =
    $_SESSION['oauth_state'] ?? '';

unset($_SESSION['oauth_state']);

if (
    !is_string($state)
    || !is_string($expectedState)
    || $expectedState === ''
    || !hash_equals(
        $expectedState,
        $state
    )
) {
    redirectWithError('invalid_state');
}


// ---------------------------------------------------------------
// Validate authorization code
// ---------------------------------------------------------------

if (
    !is_string($code)
    || $code === ''
) {
    redirectWithError('missing_code');
}


// ---------------------------------------------------------------
// Make sure real credentials exist
// ---------------------------------------------------------------

if (
    !is_string(FACEBOOK_APP_ID)
    || FACEBOOK_APP_ID === 'FACEBOOK_APP_ID'
    || !is_string(FACEBOOK_APP_SECRET)
    || FACEBOOK_APP_SECRET === 'FACEBOOK_APP_SECRET'
) {

    error_log(
        'Facebook Login Demo: Meta credentials are still placeholders.'
    );

    redirectWithError('oauth_failed');
}


// ---------------------------------------------------------------
// Step 1:
// Exchange authorization code for access token.
// ---------------------------------------------------------------

$tokenParams = [

    'client_id' =>
        FACEBOOK_APP_ID,

    'client_secret' =>
        FACEBOOK_APP_SECRET,

    'redirect_uri' =>
        FACEBOOK_REDIRECT_URI,

    'code' =>
        $code,
];

$tokenUrl =
    FACEBOOK_TOKEN_URL
    . '?'
    . http_build_query(
        $tokenParams,
        '',
        '&',
        PHP_QUERY_RFC3986
    );

$tokenResponse =
    facebookGet($tokenUrl);

if (
    !$tokenResponse['ok']
    || empty(
        $tokenResponse['data']['access_token']
    )
) {

    // Never log or display the token.
    error_log(
        'Facebook Login Demo: access-token exchange failed. HTTP '
        . $tokenResponse['http_code']
    );

    redirectWithError('token_failed');
}

$accessToken =
    $tokenResponse['data']['access_token'];

if (
    !is_string($accessToken)
    || $accessToken === ''
) {
    redirectWithError('token_failed');
}


// ---------------------------------------------------------------
// Step 2:
// Get the logged-in Facebook user's profile.
// ---------------------------------------------------------------

$userParams = [

    'fields' =>
        'id,name,email,picture.type(large)',

    'access_token' =>
        $accessToken,
];

$userUrl =
    FACEBOOK_GRAPH_URL
    . '/me?'
    . http_build_query(
        $userParams,
        '',
        '&',
        PHP_QUERY_RFC3986
    );

$userResponse =
    facebookGet($userUrl);

if (!$userResponse['ok']) {

    error_log(
        'Facebook Login Demo: Graph API profile request failed. HTTP '
        . $userResponse['http_code']
    );

    redirectWithError('api_failed');
}

$user =
    $userResponse['data'];


// ---------------------------------------------------------------
// Extract profile data
// ---------------------------------------------------------------

$facebookId =
    isset($user['id'])
        ? (string) $user['id']
        : '';

$name =
    isset($user['name'])
        ? trim((string) $user['name'])
        : '';

$email =
    isset($user['email'])
        && is_string($user['email'])
        ? trim($user['email'])
        : null;

$profilePicture = null;

if (
    isset($user['picture']['data']['url'])
    && is_string(
        $user['picture']['data']['url']
    )
) {
    $profilePicture =
        $user['picture']['data']['url'];
}


// ---------------------------------------------------------------
// Validate required information
// ---------------------------------------------------------------

if (
    $facebookId === ''
    || !isValidFacebookId($facebookId)
) {
    redirectWithError('missing_user_id');
}

if ($name === '') {

    error_log(
        'Facebook Login Demo: Facebook profile has no usable name.'
    );

    redirectWithError('api_failed');
}


// ---------------------------------------------------------------
// Email validation
// ---------------------------------------------------------------
//
// Facebook may not return an email for every account.
// Therefore, the database column is nullable.
//
// If Facebook does return an invalid value, we ignore it
// rather than trusting it.
// ---------------------------------------------------------------

if (
    $email !== null
    && !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {
    $email = null;
}


// ---------------------------------------------------------------
// Step 3:
// Insert new user or update existing user.
// ---------------------------------------------------------------

try {

    $selectStmt =
        $pdo->prepare(
            'SELECT id
             FROM users
             WHERE facebook_id = :facebook_id
             LIMIT 1'
        );

    $selectStmt->execute([
        ':facebook_id' =>
            $facebookId,
    ]);

    $existingUser =
        $selectStmt->fetch();


    // -----------------------------------------------------------
    // Existing user
    // -----------------------------------------------------------

    if ($existingUser) {

        $userId =
            (int) $existingUser['id'];

        $updateStmt =
            $pdo->prepare(
                'UPDATE users
                 SET name = :name,
                     email = :email,
                     profile_picture = :profile_picture,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );

        $updateStmt->execute([

            ':name' =>
                $name,

            ':email' =>
                $email,

            ':profile_picture' =>
                $profilePicture,

            ':id' =>
                $userId,
        ]);


    // -----------------------------------------------------------
    // New user
    // -----------------------------------------------------------

    } else {

        $insertStmt =
            $pdo->prepare(
                'INSERT INTO users
                    (
                        facebook_id,
                        name,
                        email,
                        profile_picture
                    )
                 VALUES
                    (
                        :facebook_id,
                        :name,
                        :email,
                        :profile_picture
                    )'
            );

        $insertStmt->execute([

            ':facebook_id' =>
                $facebookId,

            ':name' =>
                $name,

            ':email' =>
                $email,

            ':profile_picture' =>
                $profilePicture,
        ]);

        $userId =
            (int) $pdo->lastInsertId();
    }

} catch (PDOException $e) {

    error_log(
        'Facebook Login Demo DB write error: '
        . $e->getMessage()
    );

    redirectWithError('db_failed');
}


// ---------------------------------------------------------------
// Prevent session fixation.
// ---------------------------------------------------------------

session_regenerate_id(true);


// ---------------------------------------------------------------
// Save local session.
// ---------------------------------------------------------------

$_SESSION['user_id'] =
    $userId;

$_SESSION['facebook_id'] =
    $facebookId;

$_SESSION['user_name'] =
    $name;

$_SESSION['user_email'] =
    $email;


// ---------------------------------------------------------------
// We intentionally do NOT save the Facebook access token.
// ---------------------------------------------------------------

unset($accessToken);


// ---------------------------------------------------------------
// Send user to dashboard.
// ---------------------------------------------------------------

header(
    'Location: dashboard.php'
);

exit;