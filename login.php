<?php
require_once "config.php";

/*
|--------------------------------------------------------------------------
| Redirect Logged-In Users
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION["user"])) {
    if (($_SESSION["user"]["role"] ?? "") === "admin") {
        redirect("admin_dashboard.php");
    }

    redirect("student_dashboard.php");
}

$error = "";
$email = "";

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $statement = $pdo->prepare(
            "SELECT
                id,
                fullname,
                email,
                password,
                role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $statement->execute([$email]);
        $user = $statement->fetch();

        if (
            $user &&
            password_verify(
                $password,
                $user["password"]
            )
        ) {
            session_regenerate_id(true);

            $_SESSION["user"] = [
                "id" => (int) $user["id"],
                "fullname" => $user["fullname"],
                "email" => $user["email"],
                "role" => $user["role"]
            ];

            flash(
                "Welcome back, " .
                $user["fullname"] .
                "!",
                "success"
            );

            if ($user["role"] === "admin") {
                redirect("admin_dashboard.php");
            }

            redirect("student_dashboard.php");
        } else {
            $error = "Invalid email or password.";
        }
    }
}

$page_title = "Login";

include "header.php";
?>

<style>
    .login-section {
        min-height: calc(100vh - 88px);
        padding: 55px 20px;
        background:
            linear-gradient(
                rgba(255, 247, 141, 0.82),
                rgba(255, 255, 255, 0.96)
            );
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-container {
        width: 100%;
        max-width: 920px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        background: #FFFFFF;
        border-radius: 18px;
        overflow: hidden;
        box-shadow:
            0 15px 40px
            rgba(40, 63, 36, 0.16);
    }

    .login-banner {
        background:
            linear-gradient(
                135deg,
                #1F351C,
                #283F24
            );
        color: #FFFFFF;
        padding: 55px 45px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .login-banner h1 {
        color: #FFBF00;
        font-size: 40px;
        margin-bottom: 18px;
    }

    .login-banner p {
        color:
            rgba(255, 255, 255, 0.85);
        line-height: 1.7;
        margin-bottom: 25px;
    }

    .login-feature {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 14px;
        font-size: 15px;
        line-height: 1.5;
    }

    .login-feature svg {
        width: 18px;
        height: 18px;
        fill: #FFBF00;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .login-form-area {
        padding: 48px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .login-form-area h2 {
        color: #283F24;
        margin-bottom: 8px;
    }

    .login-subtitle {
        color: #6B7280;
        margin-bottom: 25px;
        line-height: 1.6;
    }

    .login-error {
        background: #FCE7E5;
        color: #8E201A;
        border: 1px solid #E7AAA5;
        border-radius: 8px;
        padding: 13px 16px;
        margin-bottom: 20px;
    }

    .login-form-area .btn {
        width: 100%;
        padding: 13px;
    }

    .login-bottom {
        text-align: center;
        margin-top: 20px;
        color: #6B7280;
    }

    .login-bottom a {
        color: #467235;
        font-weight: bold;
    }

    .password-wrapper {
        position: relative;
    }

    .password-wrapper .form-control {
        padding-right: 75px;
    }

    .toggle-password {
        position: absolute;
        top: 50%;
        right: 12px;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #467235;
        font-weight: bold;
        cursor: pointer;
    }

    .forgot-row {
        display: flex;
        justify-content: flex-end;
        margin: 8px 0 16px;
    }

    .forgot-row a {
        color: #467235;
        font-size: 13px;
        font-weight: bold;
    }

    .forgot-row a:hover {
        text-decoration: underline;
    }

    @media (max-width: 760px) {
        .login-container {
            grid-template-columns: 1fr;
        }

        .login-banner {
            padding: 35px 28px;
        }

        .login-form-area {
            padding: 35px 28px;
        }

        .login-banner h1 {
            font-size: 32px;
        }
    }
</style>

<section class="login-section">

    <div class="login-container">

        <div class="login-banner">

            <h1>Welcome Back</h1>

            <p>
                Log in to access your ScholarTrack account,
                manage scholarship applications, and monitor
                application updates.
            </p>

            <?php
            $features = [
                "View matched scholarships",
                "Submit and track applications",
                "Receive status notifications",
                "Manage scholarships as administrator"
            ];
            ?>

            <?php foreach ($features as $feature): ?>

                <div class="login-feature">

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M9.2 16.2 4.8 11.8l-1.4 1.4 5.8 5.8L21 7.2l-1.4-1.4-10.4 10.4Z"/>
                    </svg>

                    <span>
                        <?= e($feature); ?>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="login-form-area">

            <h2>Account Login</h2>

            <p class="login-subtitle">
                Enter your registered account details.
            </p>

            <?php if ($error !== ""): ?>

                <div class="login-error">
                    <?= e($error); ?>
                </div>

            <?php endif; ?>

            <form
                method="POST"
                action="login.php"
                autocomplete="on"
            >

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="<?= e($email); ?>"
                        placeholder="Enter your email address"
                        autocomplete="email"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword()"
                            aria-label="Show or hide password"
                        >
                            Show
                        </button>

                    </div>

                </div>

                <div class="forgot-row">

                    <a href="forgot_password.php">
                        Forgot Password?
                    </a>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Login
                </button>

            </form>

            <p class="login-bottom">
                No student account yet?
                <a href="register.php">
                    Register here
                </a>
            </p>

        </div>

    </div>

</section>

<script>
    function togglePassword() {
        const passwordInput =
            document.getElementById("password");

        const button =
            document.querySelector(".toggle-password");

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            button.textContent = "Hide";
        } else {
            passwordInput.type = "password";
            button.textContent = "Show";
        }
    }
</script>

<?php include "footer.php"; ?>