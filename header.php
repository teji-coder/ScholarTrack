<?php
require_once "config.php";

if (!isset($page_title)) {
    $page_title = "ScholarTrack";
}

$current_page = basename($_SERVER["PHP_SELF"]);
$is_logged_in = isset($_SESSION["user"]);

$user_role = "";
$user_name = "User";
$user_initial = "U";
$profile_picture = "";

if ($is_logged_in) {
    $user_role = $_SESSION["user"]["role"] ?? "";
    $user_name = $_SESSION["user"]["fullname"] ?? "User";
    $user_initial = strtoupper(substr($user_name, 0, 1));

    if ($user_role === "student") {
        $picture_statement = $pdo->prepare(
            "SELECT profile_picture
             FROM student_profiles
             WHERE user_id = ?
             LIMIT 1"
        );

        $picture_statement->execute([
            (int) $_SESSION["user"]["id"]
        ]);

        $picture_record = $picture_statement->fetch();

        if (
            $picture_record &&
            !empty($picture_record["profile_picture"]) &&
            $picture_record["profile_picture"] !== "default.png"
        ) {
            $profile_picture =
                $picture_record["profile_picture"];
        }
    }
}

/* Simple inline SVG icons */
function menu_icon($name)
{
    $icons = [
        "dashboard" => '
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 3h8v8H3V3Zm10 0h8v5h-8V3ZM3 13h8v8H3v-8Zm10-3h8v11h-8V10Z"/>
            </svg>
        ',

        "scholarships" => '
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 3 2 8l10 5 8-4v5h2V8L12 3Zm-6 8.3V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.7l-6 3-6-3Z"/>
            </svg>
        ',

        "applications" => '
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M7 2h10v2h3v18H4V4h3V2Zm2 2v2h6V4H9Zm-2 5v2h10V9H7Zm0 4v2h10v-2H7Zm0 4v2h7v-2H7Z"/>
            </svg>
        ',

        "students" => '
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 9v-2c0-3.3 3.6-5 8-5s8 1.7 8 5v2H4Z"/>
            </svg>
        ',

        "reports" => '
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 3h16v18H4V3Zm3 13h2V9H7v7Zm4 0h2V6h-2v10Zm4 0h2v-4h-2v4Z"/>
            </svg>
        ',

        "profile" => '
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 9v-2c0-3.3 3.6-5 8-5s8 1.7 8 5v2H4Z"/>
            </svg>
        ',

        "notifications" => '
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 22a2.5 2.5 0 0 0 2.4-2H9.6A2.5 2.5 0 0 0 12 22Zm7-5H5l2-2v-4a5 5 0 0 1 10 0v4l2 2Z"/>
            </svg>
        ',

        "logout" => '
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M10 3H4v18h6v-2H6V5h4V3Zm5.6 4.6L14.2 9l2 2H9v2h7.2l-2 2 1.4 1.4L20 12l-4.4-4.4Z"/>
            </svg>
        '
    ];

    return $icons[$name] ?? "";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($page_title); ?> | ScholarTrack
    </title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --gold: #FFBF00;
            --gold-dark: #E5AB00;
            --light-yellow: #FFF78D;
            --green: #467235;
            --dark-green: #283F24;
            --deep-green: #1F351C;
            --white: #FFFFFF;
            --light-gray: #F5F6F4;
            --gray: #6B7280;
            --border: #DDE2D9;
            --danger: #C0392B;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: var(--light-gray);
            color: var(--dark-green);
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }

        /* =========================
           LOGO AREA
        ========================= */

        .brand-link {
            display: flex;
            align-items: center;
            gap: 16px;
            color: var(--white);
        }

        .brand-logo {
            width: 230px;
            height: 72px;
            object-fit: contain;
            object-position: left center;
        }

        .brand-tagline {
            color: rgba(255, 255, 255, 0.78);
            font-size: 12px;
            line-height: 1.35;
            padding-left: 15px;
            border-left: 1px solid rgba(255, 255, 255, 0.35);
            white-space: nowrap;
        }

        /* =========================
           PUBLIC NAVIGATION
        ========================= */

        .public-navbar {
            min-height: 88px;
            background:
                linear-gradient(
                    135deg,
                    var(--deep-green),
                    var(--dark-green)
                );
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 0 6%;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid var(--gold);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.16);
        }

        .public-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .public-links a {
            color: var(--white);
            padding: 11px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
        }

        .public-links a:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .public-links .register-link {
            background: var(--gold);
            color: var(--dark-green);
        }

        .public-links .register-link:hover {
            background: var(--gold-dark);
        }

        /* =========================
           TOPBAR
        ========================= */

        .topbar {
            min-height: 88px;
            background:
                linear-gradient(
                    135deg,
                    var(--deep-green),
                    var(--dark-green)
                );
            color: var(--white);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 0 24px;
            border-bottom: 3px solid var(--gold);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.16);
        }

        .topbar-left,
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .admin-label {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            background: var(--gold);
            color: var(--dark-green);
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.7px;
        }

        .user-name {
            font-size: 14px;
            font-weight: bold;
        }

        .profile-circle,
        .profile-photo {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            background: var(--green);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            object-fit: cover;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            width: 250px;
            background:
                linear-gradient(
                    180deg,
                    #467235,
                    #355B2B
                );
            color: var(--white);
            position: fixed;
            top: 88px;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            padding: 18px 0;
            z-index: 900;
        }

        .sidebar-user {
            padding: 8px 20px 19px;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        }

        .sidebar-user strong {
            display: block;
            font-size: 15px;
            margin-bottom: 5px;
        }

        .sidebar-user span {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.76);
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 3px;
        }

        .sidebar-menu a {
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 20px;
            font-size: 14px;
            border-left: 4px solid transparent;
            transition: 0.2s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: var(--dark-green);
            border-left-color: var(--gold);
            color: var(--light-yellow);
        }

        .menu-icon {
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .menu-icon svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }

        /* =========================
           MAIN CONTENT
        ========================= */

        .main-content {
            margin-left: 250px;
            padding: 112px 24px 35px;
            min-height: 100vh;
        }

        /* =========================
           REUSABLE COMPONENTS
        ========================= */

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 4px 14px rgba(40, 63, 36, 0.07);
            margin-bottom: 20px;
        }

        .card-title {
            color: var(--dark-green);
            margin-bottom: 16px;
        }

        .grid {
            display: grid;
            gap: 18px;
        }

        .grid-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .grid-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        .grid-4 {
            grid-template-columns: repeat(4, 1fr);
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(40, 63, 36, 0.06);
        }

        .stat-card.highlight {
            background: var(--light-yellow);
        }

        .stat-card h3 {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 30px;
            color: var(--dark-green);
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            border: none;
            border-radius: 8px;
            padding: 10px 17px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn-primary {
            background: var(--green);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--dark-green);
        }

        .btn-gold {
            background: var(--gold);
            color: var(--dark-green);
        }

        .btn-gold:hover {
            background: var(--gold-dark);
        }

        .btn-secondary {
            background: #E6E9E3;
            color: var(--dark-green);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            color: var(--dark-green);
        }

        .form-control {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #C8D0C3;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            background: var(--white);
        }

        .form-control:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(70, 114, 53, 0.12);
        }

        textarea.form-control {
            min-height: 110px;
            resize: vertical;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert.success {
            background: #E4F2DF;
            color: #24551C;
            border: 1px solid #A9D49B;
        }

        .alert.error {
            background: #FCE7E5;
            color: #8E201A;
            border: 1px solid #E7AAA5;
        }

        .alert.warning {
            background: #FFF5D2;
            color: #7A5700;
            border: 1px solid #EACD75;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        th {
            background: var(--dark-green);
            color: var(--white);
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-pending {
            background: #FFF2BD;
            color: #775800;
        }

        .badge-under-review {
            background: #DCE8FF;
            color: #244A84;
        }

        .badge-waitlisted {
            background: #FFE4B5;
            color: #8A5200;
        }

        .badge-approved {
            background: #DCEFD6;
            color: #28651F;
        }

        .badge-rejected {
            background: #F5D4D1;
            color: #9D221A;
        }

        .empty-message {
            color: var(--gray);
            text-align: center;
            padding: 30px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1050px) {
            .brand-tagline {
                display: none;
            }

            .grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-3 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 800px) {
            .sidebar {
                width: 76px;
            }

            .sidebar-user {
                display: none;
            }

            .sidebar-menu a {
                justify-content: center;
                padding: 15px 10px;
            }

            .sidebar-menu a span:not(.menu-icon) {
                display: none;
            }

            .main-content {
                margin-left: 76px;
                padding-left: 16px;
                padding-right: 16px;
            }

            .grid-2,
            .grid-3,
            .grid-4 {
                grid-template-columns: 1fr;
            }

            .brand-logo {
                width: 185px;
            }

            .admin-label {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .public-navbar {
                padding: 0 15px;
            }

            .public-links a {
                padding: 9px 10px;
                font-size: 13px;
            }

            .brand-logo {
                width: 155px;
            }

            .topbar {
                padding: 0 12px;
            }

            .user-name {
                display: none;
            }
        }
    </style>
</head>

<body>

<?php if ($is_logged_in): ?>

    <div class="dashboard-layout">

        <header class="topbar">

            <div class="topbar-left">

                <a
                    href="<?= $user_role === "admin"
                        ? "admin_dashboard.php"
                        : "student_dashboard.php"; ?>"
                    class="brand-link"
                >
                    <img
    			src="assets/logo.png?v=<?= time(); ?>"
   			alt="ScholarTrack"
   			class="brand-logo"
>

                    <span class="brand-tagline">
                        Scholarship Eligibility<br>
                        &amp; Application Portal
                    </span>
                </a>

                <?php if ($user_role === "admin"): ?>

                    <span class="admin-label">
                        ADMIN
                    </span>

                <?php endif; ?>

            </div>

            <div class="topbar-right">

                <span class="user-name">
                    <?= e($user_name); ?>
                </span>

                <?php if ($profile_picture !== ""): ?>

                   <img
    			src="profile/<?= e($profile_picture); ?>?v=<?= time(); ?>"
    			alt="Profile Picture"
    			class="profile-photo"
>

                <?php else: ?>

                    <div class="profile-circle">
                        <?= e($user_initial); ?>
                    </div>

                <?php endif; ?>

            </div>

        </header>

        <aside class="sidebar">

            <div class="sidebar-user">

                <strong>
                    <?= e($user_name); ?>
                </strong>

                <span>
                    <?= $user_role === "admin"
                        ? "Administrator Account"
                        : "Student Account"; ?>
                </span>

            </div>

            <?php if ($user_role === "admin"): ?>

                <ul class="sidebar-menu">

                    <li>
                        <a
                            href="admin_dashboard.php"
                            class="<?= $current_page === "admin_dashboard.php"
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <?= menu_icon("dashboard"); ?>
                            </span>

                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li>
                        <a
                            href="manage_scholarships.php"
                            class="<?= (
                                $current_page === "manage_scholarships.php" ||
                                $current_page === "scholarship_form.php"
                            )
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <?= menu_icon("scholarships"); ?>
                            </span>

                            <span>Scholarships</span>
                        </a>
                    </li>

                    <li>
                        <a
                            href="manage_applications.php"
                            class="<?= $current_page === "manage_applications.php"
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <?= menu_icon("applications"); ?>
                            </span>

                            <span>Applications</span>
                        </a>
                    </li>

                    <li>
                        <a
                            href="manage_students.php"
                            class="<?= $current_page === "manage_students.php"
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <?= menu_icon("students"); ?>
                            </span>

                            <span>Students</span>
                        </a>
                    </li>

                    <li>
                        <a
                            href="reports.php"
                            class="<?= $current_page === "reports.php"
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <?= menu_icon("reports"); ?>
                            </span>

                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a
                            href="change_password.php"
                            class="<?= $current_page === "change_password.php"
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M17 8h-1V6a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v10h14V10a2 2 0 0 0-2-2Zm-7-2a2 2 0 0 1 4 0v2h-4V6Zm3 9.7V18h-2v-2.3a2 2 0 1 1 2 0Z"/>
                                </svg>
                            </span>

                            <span>Change Password</span>
                        </a>
                    </li>


                    <li>
                        <a href="logout.php">

                            <span class="menu-icon">
                                <?= menu_icon("logout"); ?>
                            </span>

                            <span>Logout</span>
                        </a>
                    </li>

                </ul>

            <?php else: ?>

                <ul class="sidebar-menu">

                    <li>
                        <a
                            href="student_dashboard.php"
                            class="<?= $current_page === "student_dashboard.php"
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <?= menu_icon("dashboard"); ?>
                            </span>

                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li>
                        <a
                            href="profile.php"
                            class="<?= $current_page === "profile.php"
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <?= menu_icon("profile"); ?>
                            </span>

                            <span>My Profile</span>
                        </a>
                    </li>

                    <li>
                        <a
                            href="scholarships.php"
                            class="<?= (
                                $current_page === "scholarships.php" ||
                                $current_page === "scholarship_view.php" ||
                                $current_page === "apply.php"
                            )
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <?= menu_icon("scholarships"); ?>
                            </span>

                            <span>Scholarships</span>
                        </a>
                    </li>

                    <li>
                        <a
                            href="my_applications.php"
                            class="<?= $current_page === "my_applications.php"
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <?= menu_icon("applications"); ?>
                            </span>

                            <span>Applications</span>
                        </a>
                    </li>

                    <li>
                        <a
                            href="notifications.php"
                            class="<?= $current_page === "notifications.php"
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <?= menu_icon("notifications"); ?>
                            </span>

                            <span>Notifications</span>
                        </a>
                    </li>
                    <li>
                        <a
                            href="change_password.php"
                            class="<?= $current_page === "change_password.php"
                                ? "active"
                                : ""; ?>"
                        >
                            <span class="menu-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M17 8h-1V6a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v10h14V10a2 2 0 0 0-2-2Zm-7-2a2 2 0 0 1 4 0v2h-4V6Zm3 9.7V18h-2v-2.3a2 2 0 1 1 2 0Z"/>
                                </svg>
                            </span>

                            <span>Change Password</span>
                        </a>
                    </li>


                    <li>
                        <a href="logout.php">

                            <span class="menu-icon">
                                <?= menu_icon("logout"); ?>
                            </span>

                            <span>Logout</span>
                        </a>
                    </li>

                </ul>

            <?php endif; ?>

        </aside>

        <main class="main-content">

            <?php display_flash(); ?>

<?php else: ?>

    <nav class="public-navbar">

        <a
            href="index.php"
            class="brand-link"
        >
            <img
    		src="assets/logo.png?v=<?= time(); ?>"
    		alt="ScholarTrack"
    		class="brand-logo"
	>

            <span class="brand-tagline">
                Scholarship Eligibility<br>
                &amp; Application Portal
            </span>
        </a>

        <div class="public-links">

            <a href="index.php">
                Home
            </a>

            <a href="login.php">
                Login
            </a>

            <a
                href="register.php"
                class="register-link"
            >
                Register
            </a>

        </div>

    </nav>

    <?php display_flash(); ?>

<?php endif; ?>