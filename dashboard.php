<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

requireLogin();

try {

    $stmt =
        $pdo->prepare(
            'SELECT
                id,
                facebook_id,
                name,
                email,
                profile_picture,
                created_at,
                updated_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );

    $stmt->execute([
        ':id' =>
            $_SESSION['user_id'],
    ]);

    $user =
        $stmt->fetch();


    if (!$user) {

        logoutUser();

        header(
            'Location: index.php?error=db_failed'
        );

        exit;
    }

} catch (PDOException $e) {

    error_log(
        'Facebook Login Demo dashboard DB error: '
        . $e->getMessage()
    );

    http_response_code(500);

    exit(
        'We could not load your account right now.'
    );
}


$pageTitle =
    'Dashboard - Facebook Login Demo';

$bodyClass =
    'dashboard-page';

$displayEmail =
    $user['email']
        ?: 'Not provided by Facebook';

$displayPicture =
    $user['profile_picture']
        ?: '';


require __DIR__ . '/includes/header.php';
?>

<section class="dashboard-layout">

    <div class="dashboard-card">

        <div class="dashboard-topbar">

            <div>

                <p class="eyebrow">
                    Authenticated successfully
                </p>

                <h1>
                    Welcome,
                    <?= htmlspecialchars(
                        $user['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>!
                </h1>

            </div>

            <a
                href="logout.php"
                class="logout-button"
                id="logout-button"
            >
                Log out
            </a>

        </div>


        <div class="profile-section">

            <?php if ($displayPicture !== ''): ?>

                <img
                    class="profile-picture"
                    src="<?= htmlspecialchars(
                        $displayPicture,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    alt="Facebook profile picture of <?= htmlspecialchars(
                        $user['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            <?php else: ?>

                <div
                    class="profile-fallback"
                    aria-hidden="true"
                >
                    <?= htmlspecialchars(
                        mb_strtoupper(
                            mb_substr(
                                $user['name'],
                                0,
                                1
                            )
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>


            <div>

                <h2>
                    <?= htmlspecialchars(
                        $user['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h2>

                <p class="muted">
                    Signed in with Facebook
                </p>

            </div>

        </div>


        <div class="details-grid">

            <div class="detail-card">

                <span class="detail-label">
                    Name
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $user['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>


            <div class="detail-card">

                <span class="detail-label">
                    Email
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $displayEmail,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>


            <div class="detail-card">

                <span class="detail-label">
                    Facebook ID
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $user['facebook_id'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>


            <div class="detail-card">

                <span class="detail-label">
                    Account created
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $user['created_at'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </div>

        </div>

    </div>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>