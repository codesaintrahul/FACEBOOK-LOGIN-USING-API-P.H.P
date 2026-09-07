<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Facebook Login Demo';
$bodyClass = 'auth-page';

$errorMessages = [

    'oauth_denied' =>
        'Facebook login was cancelled or denied. You can try again.',

    'missing_code' =>
        'Facebook did not return a login code. Please try again.',

    'oauth_failed' =>
        'Facebook login could not be completed. Please try again later.',

    'token_failed' =>
        'We could not complete the secure Facebook login exchange. Please try again.',

    'api_failed' =>
        'Facebook profile information could not be retrieved. Please try again.',

    'missing_user_id' =>
        'Facebook did not provide a user ID, so login could not continue.',

    'db_failed' =>
        'Your Facebook account was verified, but we could not save the account locally.',

    'invalid_state' =>
        'The login session expired or was invalid. Please start again.',
];

$errorCode = $_GET['error'] ?? null;

$errorMessage = is_string($errorCode)
    ? ($errorMessages[$errorCode] ?? null)
    : null;

require __DIR__ . '/includes/header.php';
?>

<section class="auth-layout">

    <div class="auth-card" aria-labelledby="page-heading">

        <div class="brand-mark" aria-hidden="true">
            f
        </div>

        <p class="eyebrow">
            Meta OAuth Demo
        </p>

        <h1 id="page-heading">
            Facebook Login Demo
        </h1>

        <p class="lead">
            Sign in securely with your Facebook account.
            Your password is handled only by Facebook/Meta.
        </p>

        <?php if ($errorMessage !== null): ?>

            <div
                class="alert alert-error"
                role="alert"
            >
                <?= htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        <?php endif; ?>

        <a
            class="facebook-button"
            href="login.php"
            id="facebook-login-button"
        >
            <span
                class="facebook-icon"
                aria-hidden="true"
            >
                f
            </span>

            <span class="button-label">
                Continue with Facebook
            </span>
        </a>

        <p class="security-note">
            We never ask for or store your Facebook password.
        </p>

    </div>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>