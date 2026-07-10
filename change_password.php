<?php
require_once "config.php";

if (!isset($_SESSION["user"])) {
    flash("Please log in first.", "error");
    redirect("login.php");
}

$user_id = (int) $_SESSION["user"]["id"];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($current_password === "") {
        $errors[] = "Current password is required.";
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
        $statement = $pdo->prepare(
            "SELECT password
             FROM users
             WHERE id = ?
             LIMIT 1"
        );

        $statement->execute([$user_id]);
        $user = $statement->fetch();

        if (!$user || !password_verify($current_password, $user["password"])) {
            $errors[] = "Current password is incorrect.";
        } elseif (password_verify($new_password, $user["password"])) {
            $errors[] = "New password must be different from your current password.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update = $pdo->prepare(
                "UPDATE users
                 SET password = ?
                 WHERE id = ?"
            );

            $update->execute([
                $hashed_password,
                $user_id
            ]);

            flash("Password changed successfully.", "success");

            $dashboard = ($_SESSION["user"]["role"] ?? "") === "admin"
                ? "admin_dashboard.php"
                : "student_dashboard.php";

            redirect($dashboard);
        }
    }
}

$page_title = "Change Password";
include "header.php";
?>

<style>
.password-page {
    max-width: 620px;
    margin: 0 auto;
}
.password-hero {
    background: linear-gradient(135deg, #283F24, #467235);
    color: #fff;
    border-radius: 18px;
    padding: 28px;
    margin-bottom: 22px;
}
.password-hero h1 {
    color: #FFBF00;
    margin-bottom: 8px;
}
.password-hero p {
    color: rgba(255,255,255,.84);
    line-height: 1.6;
}
.error-list {
    background: #FCE7E5;
    color: #8E201A;
    border: 1px solid #E7AAA5;
    border-radius: 9px;
    padding: 14px 18px 14px 36px;
    margin-bottom: 18px;
}
.password-note {
    background: #FFF8D4;
    border: 1px solid #E7CB67;
    color: #6C5200;
    border-radius: 9px;
    padding: 13px;
    margin-bottom: 18px;
    line-height: 1.6;
}
.form-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
</style>

<div class="password-page">

    <div class="password-hero">
        <h1>Change Password</h1>
        <p>
            Use a strong password that you do not use on other websites.
        </p>
    </div>

    <div class="card">

        <?php if (!empty($errors)): ?>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="password-note">
            Password must contain at least 8 characters, including
            an uppercase letter, a lowercase letter, and a number.
        </div>

        <form method="POST" action="change_password.php">

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    class="form-control"
                    autocomplete="current-password"
                    required
                >
            </div>

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
                <label for="confirm_password">Confirm New Password</label>
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

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Save New Password
                </button>

                <a
                    href="<?= ($_SESSION["user"]["role"] ?? "") === "admin"
                        ? "admin_dashboard.php"
                        : "student_dashboard.php"; ?>"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>
            </div>

        </form>

    </div>

</div>

<?php include "footer.php"; ?>