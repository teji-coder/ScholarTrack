<?php
require_once "config.php";

if (isset($_SESSION["user"])) {
    $dashboard = ($_SESSION["user"]["role"] ?? "") === "admin"
        ? "admin_dashboard.php"
        : "student_dashboard.php";

    redirect($dashboard);
}

$raw_token = trim($_GET["token"] ?? $_POST["token"] ?? "");
$errors = [];
$token_record = null;

if ($raw_token !== "") {
    $token_hash = hash("sha256", $raw_token);

    $statement = $pdo->prepare(
        "SELECT
            password_reset_tokens.id,
            password_reset_tokens.user_id,
            password_reset_tokens.expires_at,
            password_reset_tokens.used_at
         FROM password_reset_tokens
         WHERE password_reset_tokens.token_hash = ?
         LIMIT 1"
    );

    $statement->execute([$token_hash]);
    $token_record = $statement->fetch();
}

$token_valid =
    $token_record &&
    empty($token_record["used_at"]) &&
    strtotime($token_record["expires_at"]) >= time();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (!$token_valid) {
        $errors[] = "This reset link is invalid, expired, or already used.";
    }

    if (strlen($new_password) < 8) {
        $errors[] = "New password must contain at least 8 characters.";
    }

    if (!preg_match("/[A-Z]/", $new_password)) {
        $errors[] = "New password must contain at least one uppercase letter.";
    }

    if (!preg_match("/[a-z]/", $new_password)) {
        $errors[] = "New password must contain at least one lowercase letter.";
    }

    if (!preg_match("/[0-9]/", $new_password)) {
        $errors[] = "New password must contain at least one number.";
    }

    if ($new_password !== $confirm_password) {
        $errors[] = "New password and confirmation do not match.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $hashed_password =
                password_hash($new_password, PASSWORD_DEFAULT);

            $update_user = $pdo->prepare(
                "UPDATE users
                 SET password = ?
                 WHERE id = ?"
            );

            $update_user->execute([
                $hashed_password,
                (int) $token_record["user_id"]
            ]);

            $use_token = $pdo->prepare(
                "UPDATE password_reset_tokens
                 SET used_at = NOW()
                 WHERE id = ?"
            );

            $use_token->execute([
                (int) $token_record["id"]
            ]);

            $pdo->commit();

            flash(
                "Password reset successfully. You may now log in.",
                "success"
            );

            redirect("login.php");

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                "Unable to reset the password. Please try again.";
        }
    }
}

$page_title = "Reset Password";
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
.invalid-link {
    background: #FCE7E5;
    color: #8E201A;
    border: 1px solid #E7AAA5;
    border-radius: 9px;
    padding: 15px;
    line-height: 1.6;
}
.auth-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
</style>

<div class="auth-page">

    <div class="auth-card">

        <h1>Reset Password</h1>

        <p>
            Create a new password for your ScholarTrack account.
        </p>

        <?php if (!empty($errors)): ?>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!$token_valid): ?>

            <div class="invalid-link">
                This reset link is invalid, expired, or already used.
            </div>

            <br>

            <a href="forgot_password.php" class="btn btn-primary">
                Request Another Reset Link
            </a>

        <?php else: ?>

            <form method="POST" action="reset_password.php">

                <input
                    type="hidden"
                    name="token"
                    value="<?= e($raw_token); ?>"
                >

                <div class="form-group">
                    <label for="new_password">New Password</label>

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        class="form-control"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="auth-actions">
                    <button type="submit" class="btn btn-primary">
                        Reset Password
                    </button>

                    <a href="login.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>

        <?php endif; ?>

    </div>

</div>

<?php include "footer.php"; ?>