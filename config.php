<?php
session_start();

$host = 'localhost';
$db   = 'scholartrack';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Database connection failed. Import database.sql and check config.php.');
}

/* Prevents special characters from breaking the HTML */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/* Redirects the user to another page */
function redirect(string $path): never
{
    header("Location: $path");
    exit;
}

/* Checks whether the user is logged in */
function require_login(): void
{
    if (empty($_SESSION['user'])) {
        redirect('login.php');
    }
}

/* Allows admin users only */
function require_admin(): void
{
    require_login();

    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        redirect('student_dashboard.php');
    }
}

/* Allows student users only */
function require_student(): void
{
    require_login();

    if (($_SESSION['user']['role'] ?? '') !== 'student') {
        redirect('admin_dashboard.php');
    }
}

/* Saves a temporary success or error message */
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

/* Displays the temporary message */
function display_flash(): void
{
    if (!empty($_SESSION['flash'])) {
        $flashMessage = $_SESSION['flash'];

        unset($_SESSION['flash']);

        echo '<div class="alert ' . e($flashMessage['type']) . '">';
        echo e($flashMessage['message']);
        echo '</div>';
    }
}
?>