<?php
require_once "config.php";

/* Kapag naka-login na, dalhin sa tamang dashboard */
if (!empty($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        redirect('admin_dashboard.php');
    }

    redirect('student_dashboard.php');
}

$errors = [];

$fullname = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    /* Validation */
    if ($fullname === '') {
        $errors[] = 'Full name is required.';
    }

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must contain at least 6 characters.';
    }

    if ($confirm_password === '') {
        $errors[] = 'Confirm password is required.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Password and confirm password are not the same.';
    }

    /* Check kung existing na ang email */
    if (empty($errors)) {
        $check = $pdo->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'This email address is already registered.';
        }
    }

    /* Save student account */
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insert_user = $pdo->prepare(
                "INSERT INTO users
                (fullname, email, password, role)
                VALUES (?, ?, ?, 'student')"
            );

            $insert_user->execute([
                $fullname,
                $email,
                $hashed_password
            ]);

            $user_id = (int) $pdo->lastInsertId();

            /* Gumawa rin agad ng empty student profile */
            $insert_profile = $pdo->prepare(
                "INSERT INTO student_profiles (user_id)
                VALUES (?)"
            );

            $insert_profile->execute([$user_id]);

            $pdo->commit();

            flash(
                'Registration successful. You may now log in.',
                'success'
            );

            redirect('login.php');

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$page_title = 'Student Registration';

include "header.php";
?>

<style>
    .auth-section {
        min-height: calc(100vh - 70px);
        padding: 55px 20px;
        background:
            linear-gradient(
                rgba(255, 247, 141, 0.82),
                rgba(255, 255, 255, 0.95)
            );
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .auth-container {
        width: 100%;
        max-width: 980px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        background: #FFFFFF;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 15px 40px rgba(40, 63, 36, 0.16);
    }

    .auth-information {
        background: #283F24;
        color: #FFFFFF;
        padding: 55px 45px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .auth-information h1 {
        color: #FFBF00;
        font-size: 38px;
        margin-bottom: 18px;
    }

    .auth-information p {
        line-height: 1.7;
        color: rgba(255, 255, 255, 0.85);
        margin-bottom: 25px;
    }

    .auth-information ul {
        list-style: none;
    }

    .auth-information li {
        margin-bottom: 14px;
        line-height: 1.5;
    }

    .auth-form {
        padding: 45px;
    }

    .auth-form h2 {
        color: #283F24;
        margin-bottom: 8px;
    }

    .auth-subtitle {
        color: #6B7280;
        margin-bottom: 25px;
    }

    .error-list {
        background: #FCE7E5;
        color: #8E201A;
        border: 1px solid #E7AAA5;
        border-radius: 8px;
        padding: 13px 18px 13px 35px;
        margin-bottom: 20px;
    }

    .error-list li {
        margin-bottom: 5px;
    }

    .auth-form .btn {
        width: 100%;
        padding: 13px;
    }

    .auth-bottom-text {
        text-align: center;
        margin-top: 20px;
        color: #6B7280;
    }

    .auth-bottom-text a {
        color: #467235;
        font-weight: bold;
    }

    @media (max-width: 760px) {
        .auth-container {
            grid-template-columns: 1fr;
        }

        .auth-information {
            padding: 35px 28px;
        }

        .auth-form {
            padding: 35px 28px;
        }
    }
</style>

<section class="auth-section">

    <div class="auth-container">

        <div class="auth-information">

            <h1>Join ScholarTrack</h1>

            <p>
                Create your student account and begin searching
                for scholarship opportunities that match your
                academic and financial qualifications.
            </p>

            <ul>
                <li>✓ Complete your student profile</li>
                <li>✓ Check scholarship eligibility</li>
                <li>✓ Submit applications online</li>
                <li>✓ Track your application status</li>
            </ul>

        </div>

        <div class="auth-form">

            <h2>Student Registration</h2>

            <p class="auth-subtitle">
                Enter your information to create an account.
            </p>

            <?php if (!empty($errors)): ?>

                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error); ?></li>
                    <?php endforeach; ?>
                </ul>

            <?php endif; ?>

            <form method="POST" action="register.php">

                <div class="form-group">
                    <label for="fullname">Full Name</label>

                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        class="form-control"
                        value="<?= e($fullname); ?>"
                        placeholder="Enter your complete name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="<?= e($email); ?>"
                        placeholder="Enter your email address"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Minimum of 6 characters"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Re-enter your password"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Create Student Account
                </button>

            </form>

            <p class="auth-bottom-text">
                Already have an account?
                <a href="login.php">Log in here</a>
            </p>

        </div>

    </div>

</section>

<?php include "footer.php"; ?>