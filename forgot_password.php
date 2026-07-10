<?php
require_once "config.php";

if (isset($_SESSION["user"])) {
    $dashboard = ($_SESSION["user"]["role"] ?? "") === "admin"
        ? "admin_dashboard.php"
        : "student_dashboard.php";

    redirect($dashboard);
}

$errors = [];
$reset_link = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid registered email address.";
    }

    if (empty($errors)) {
        $statement = $pdo->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $statement->execute([$email]);
        $user = $statement->fetch();

        /*
         * Always show the same general message to avoid revealing
         * whether an email address is registered.
         */
        if ($user) {
            $raw_token = bin2hex(random_bytes(32));
            $token_hash = hash("sha256", $raw_token);
            $expires_at = date("Y-m-d H:i:s", time() + 3600);

            $pdo->prepare(
                "UPDATE password_reset_tokens
                 SET used_at = NOW()
                 WHERE user_id = ?
                 AND used_at IS NULL"
            )->execute([(int) $user["id"]]);

            $insert = $pdo->prepare(
                "INSERT INTO password_reset_tokens
                (
                    user_id,
                    token_hash,
                    expires_at
                )
                VALUES (?, ?, ?)"
            );

            $insert->execute([
                (int) $user["id"],
                $token_hash,
                $expires_at
            ]);

            /*
             * Local demo mode:
             * Since XAMPP normally has no email server configured,
             * the reset link is displayed on screen.
             * For production, email this link instead.
             */
            $reset_link =
                "reset_password.php?token=" .
                urlencode($raw_token);
        }
    }
}

$page_title = "Forgot Password";
include "header.php";
?>

<style>
.auth-page {
    max-width: 560px;
    margin: 55px auto;
    padding: 0 18px;
}
.auth-card {
    background: #fff;
    border: 1px solid #DDE2D9;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 10px 30px rgba(40,63,36,.10);
}
.auth-card h1 {
    color: #283F24;
    margin-bottom: 8px;
}
.auth-card > p {
    color: #6B7280;
    line-height: 1.6;
    margin-bottom: 20px;
}
.error-list {
    background: #FCE7E5;
    color: #8E201A;
    border: 1px solid #E7AAA5;
    border-radius: 9px;
    padding: 14px 18px 14px 36px;
    margin-bottom: 18px;
}
.demo-link {
    background: #FFF8D4;
    border: 1px solid #E7CB67;
    color: #6C5200;
    border-radius: 10px;
    padding: 15px;
    margin-top: 18px;
    line-height: 1.6;
    word-break: break-all;
}
.auth-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
</style>

<div class="auth-page">

    <div class="auth-card">

        <h1>Forgot Password</h1>

        <p>
            Enter the email address connected to your ScholarTrack account.
        </p>

        <?php if (!empty($errors)): ?>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">

            <div class="form-group">
                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    value="<?= e($_POST["email"] ?? ""); ?>"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="auth-actions">
                <button type="submit" class="btn btn-primary">
                    Generate Reset Link
                </button>

                <a href="login.php" class="btn btn-secondary">
                    Back to Login
                </a>
            </div>

        </form>

        <?php if ($reset_link !== ""): ?>

            <div class="demo-link">
                <strong>Local demo reset link:</strong>
                <br><br>

                <a href="<?= e($reset_link); ?>">
                    <?= e($reset_link); ?>
                </a>

                <br><br>

                This link expires in one hour and can only be used once.
                In a live website, this link must be sent through email
                instead of being displayed here.
            </div>

        <?php elseif (
            $_SERVER["REQUEST_METHOD"] === "POST" &&
            empty($errors)
        ): ?>

            <div class="demo-link">
                If the email is registered, a password-reset request
                has been created.
            </div>

        <?php endif; ?>

    </div>

</div>

<?php include "footer.php"; ?>