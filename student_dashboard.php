<?php
require_once "config.php";

require_student();

$user_id = (int) $_SESSION["user"]["id"];

/*
|--------------------------------------------------------------------------
| Student Profile
|--------------------------------------------------------------------------
*/

$profile_statement = $pdo->prepare(
    "SELECT *
     FROM student_profiles
     WHERE user_id = ?
     LIMIT 1"
);

$profile_statement->execute([
    $user_id
]);

$profile = $profile_statement->fetch();

if (!$profile) {
    $profile = [];
}

/*
|--------------------------------------------------------------------------
| Profile Completion
|--------------------------------------------------------------------------
*/

$profile_fields = [
    "first_name",
    "last_name",
    "birthday",
    "sex",
    "civil_status",
    "nationality",
    "contact_number",
    "barangay",
    "municipality",
    "province",
    "zipcode",
    "school",
    "student_no",
    "course",
    "year_level",
    "gwa",
    "annual_income"
];

$completed_fields = 0;

foreach ($profile_fields as $field) {
    if (
        isset($profile[$field]) &&
        $profile[$field] !== null &&
        trim((string) $profile[$field]) !== ""
    ) {
        $completed_fields++;
    }
}

$profile_completion = (int) round(
    ($completed_fields / count($profile_fields)) * 100
);

/*
|--------------------------------------------------------------------------
| Application Counts
|--------------------------------------------------------------------------
*/

$count_statement = $pdo->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'Pending') AS pending,
        SUM(status = 'Under Review') AS under_review,
        SUM(status = 'Waitlisted') AS waitlisted,
        SUM(status = 'Approved') AS approved,
        SUM(status = 'Rejected') AS rejected
     FROM applications
     WHERE student_id = ?"
);

$count_statement->execute([
    $user_id
]);

$counts = $count_statement->fetch();

$total_applications =
    (int) ($counts["total"] ?? 0);

$pending_applications =
    (int) ($counts["pending"] ?? 0);

$review_applications =
    (int) ($counts["under_review"] ?? 0);

$waitlisted_applications =
    (int) ($counts["waitlisted"] ?? 0);

$approved_applications =
    (int) ($counts["approved"] ?? 0);

$rejected_applications =
    (int) ($counts["rejected"] ?? 0);

/*
|--------------------------------------------------------------------------
| Eligible Scholarship Count
|--------------------------------------------------------------------------
*/

$student_gwa =
    $profile["gwa"] ?? null;

$student_income =
    $profile["annual_income"] ?? null;

$has_eligibility_data =
    $student_gwa !== null &&
    $student_gwa !== "" &&
    $student_income !== null &&
    $student_income !== "";

$eligible_count = 0;

if ($has_eligibility_data) {
    $eligible_statement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM scholarships
         WHERE
            (
                minimum_gwa IS NULL
                OR minimum_gwa = ''
                OR ? <= minimum_gwa
            )
         AND
            (
                max_income IS NULL
                OR max_income = ''
                OR ? <= max_income
            )
         AND
            (
                deadline IS NULL
                OR deadline >= CURDATE()
            )"
    );

    $eligible_statement->execute([
        $student_gwa,
        $student_income
    ]);

    $eligible_count =
        (int) $eligible_statement->fetchColumn();
}

/*
|--------------------------------------------------------------------------
| Recent Applications
|--------------------------------------------------------------------------
*/

$recent_application_statement = $pdo->prepare(
    "SELECT
        applications.id,
        applications.status,
        applications.admin_note,
        applications.submitted_at,
        scholarships.id AS scholarship_id,
        scholarships.title,
        scholarships.provider
     FROM applications
     INNER JOIN scholarships
        ON scholarships.id = applications.scholarship_id
     WHERE applications.student_id = ?
     ORDER BY applications.submitted_at DESC
     LIMIT 5"
);

$recent_application_statement->execute([
    $user_id
]);

$recent_applications =
    $recent_application_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Upcoming Deadlines
|--------------------------------------------------------------------------
*/

$deadline_statement = $pdo->query(
    "SELECT
        id,
        title,
        provider,
        deadline
     FROM scholarships
     WHERE deadline IS NOT NULL
     AND deadline >= CURDATE()
     ORDER BY deadline ASC
     LIMIT 6"
);

$upcoming_deadlines =
    $deadline_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Latest Scholarships
|--------------------------------------------------------------------------
*/

$latest_scholarships = $pdo->query(
    "SELECT
        id,
        title,
        provider,
        category,
        region,
        monthly_allowance,
        deadline
     FROM scholarships
     WHERE deadline IS NULL
        OR deadline >= CURDATE()
     ORDER BY created_at DESC
     LIMIT 6"
)->fetchAll();

/*
|--------------------------------------------------------------------------
| Recommended Scholarships
|--------------------------------------------------------------------------
*/

$recommended_scholarships = [];

if ($has_eligibility_data) {
    $recommended_statement = $pdo->prepare(
        "SELECT
            id,
            title,
            provider,
            category,
            region,
            monthly_allowance,
            deadline
         FROM scholarships
         WHERE
            (
                minimum_gwa IS NULL
                OR minimum_gwa = ''
                OR ? <= minimum_gwa
            )
         AND
            (
                max_income IS NULL
                OR max_income = ''
                OR ? <= max_income
            )
         AND
            (
                deadline IS NULL
                OR deadline >= CURDATE()
            )
         ORDER BY
            CASE
                WHEN deadline IS NULL THEN 1
                ELSE 0
            END,
            deadline ASC
         LIMIT 4"
    );

    $recommended_statement->execute([
        $student_gwa,
        $student_income
    ]);

    $recommended_scholarships =
        $recommended_statement->fetchAll();
}

/*
|--------------------------------------------------------------------------
| Latest Notifications
|--------------------------------------------------------------------------
*/

$notification_statement = $pdo->prepare(
    "SELECT
        id,
        message,
        is_read,
        created_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 5"
);

$notification_statement->execute([
    $user_id
]);

$latest_notifications =
    $notification_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function dashboard_status_class($status)
{
    return strtolower(
        str_replace(
            " ",
            "-",
            $status
        )
    );
}

function dashboard_money($value)
{
    if ($value === null || $value === "") {
        return "Not specified";
    }

    return "₱" . number_format(
        (float) $value,
        2
    );
}

$page_title =
    "Student Dashboard";

include "header.php";
?>

<style>
    .dashboard-hero {
        background:
            linear-gradient(
                135deg,
                #283F24,
                #467235
            );
        color: #FFFFFF;
        border-radius: 18px;
        padding: 32px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 25px;
        box-shadow:
            0 10px 28px
            rgba(40, 63, 36, 0.15);
    }

    .dashboard-hero h1 {
        color: #FFBF00;
        margin-bottom: 9px;
    }

    .dashboard-hero p {
        color:
            rgba(255, 255, 255, 0.86);
        line-height: 1.7;
        max-width: 760px;
    }

    .hero-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 18px;
    }

    .hero-profile {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        border: 4px solid #FFBF00;
        background: #FFFFFF;
        object-fit: cover;
        flex-shrink: 0;
    }

    .hero-avatar {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        border: 4px solid #FFBF00;
        background: #FFFFFF;
        color: #467235;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .hero-avatar svg {
        width: 62px;
        height: 62px;
        fill: currentColor;
    }

    .dashboard-stats {
        display: grid;
        grid-template-columns:
            repeat(5, minmax(0, 1fr));
        gap: 15px;
        margin-bottom: 24px;
    }

    .dashboard-stat {
        background: #FFFFFF;
        border: 1px solid #DDE2D9;
        border-radius: 13px;
        padding: 19px;
        box-shadow:
            0 3px 10px
            rgba(40, 63, 36, 0.06);
    }

    .dashboard-stat.highlight {
        background: #FFF78D;
    }

    .dashboard-stat span {
        display: block;
        color: #6B7280;
        font-size: 12px;
        margin-bottom: 8px;
    }

    .dashboard-stat strong {
        color: #283F24;
        font-size: 29px;
    }

    .dashboard-layout-grid {
        display: grid;
        grid-template-columns:
            minmax(0, 2fr)
            minmax(300px, 1fr);
        gap: 20px;
        align-items: start;
    }

    .progress-wrap {
        margin-top: 13px;
    }

    .progress-track {
        height: 14px;
        background: #E5E9E2;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 9px;
    }

    .progress-bar {
        height: 100%;
        background:
            linear-gradient(
                90deg,
                #467235,
                #FFBF00
            );
        border-radius: 20px;
    }

    .progress-meta {
        display: flex;
        justify-content: space-between;
        color: #6B7280;
        font-size: 13px;
    }

    .quick-actions {
        display: grid;
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .quick-action {
        background: #F7F9F6;
        border: 1px solid #DDE2D9;
        border-radius: 11px;
        padding: 17px;
        color: #283F24;
        font-weight: bold;
        text-align: center;
        transition: 0.2s;
    }

    .quick-action:hover {
        background: #FFF8D4;
        transform: translateY(-2px);
    }

    .application-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .application-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: #F7F9F6;
        border-radius: 10px;
    }

    .application-item h4 {
        color: #283F24;
        margin-bottom: 5px;
    }

    .application-item p {
        color: #6B7280;
        font-size: 12px;
    }

    .deadline-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .deadline-item {
        display: grid;
        grid-template-columns:
            58px 1fr;
        gap: 12px;
        align-items: center;
        padding: 12px;
        border: 1px solid #DDE2D9;
        border-radius: 10px;
    }

    .deadline-date {
        background: #FFF2BD;
        color: #775800;
        border-radius: 9px;
        padding: 9px 5px;
        text-align: center;
    }

    .deadline-date strong {
        display: block;
        font-size: 20px;
    }

    .deadline-date span {
        font-size: 10px;
        text-transform: uppercase;
    }

    .deadline-item h4 {
        color: #283F24;
        margin-bottom: 4px;
    }

    .deadline-item p {
        color: #6B7280;
        font-size: 12px;
    }

    .scholarship-grid {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .scholarship-card-mini {
        border: 1px solid #DDE2D9;
        border-radius: 11px;
        padding: 16px;
        background: #FFFFFF;
    }

    .scholarship-card-mini h4 {
        color: #283F24;
        margin-bottom: 6px;
        line-height: 1.4;
    }

    .scholarship-card-mini .provider {
        color: #467235;
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .mini-meta {
        display: grid;
        gap: 6px;
        color: #6B7280;
        font-size: 12px;
        margin-bottom: 13px;
    }

    .notification-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .notification-item {
        border-left: 4px solid #467235;
        background: #F7F9F6;
        border-radius: 9px;
        padding: 13px;
        color: #4B5563;
        line-height: 1.6;
        font-size: 13px;
    }

    .notification-item.unread {
        border-left-color: #FFBF00;
        background: #FFFDF1;
    }

    .notification-item small {
        display: block;
        color: #6B7280;
        margin-top: 6px;
    }

    @media (max-width: 1150px) {
        .dashboard-stats {
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
        }

        .dashboard-layout-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 800px) {
        .dashboard-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .quick-actions {
            grid-template-columns: 1fr;
        }

        .scholarship-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 650px) {
        .dashboard-stats {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .application-item {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="dashboard-hero">

    <div>

        <h1>
            Welcome back,
            <?= e(
                $_SESSION["user"]["fullname"]
            ); ?>!
        </h1>

        <p>
            Complete your profile, discover matching
            scholarships, submit applications, and track
            every status update from one dashboard.
        </p>

        <div class="hero-actions">

            <a
                href="scholarships.php"
                class="btn btn-gold"
            >
                Browse Scholarships
            </a>

            <a
                href="profile.php"
                class="btn btn-secondary"
            >
                Update Profile
            </a>

        </div>

    </div>

    <?php if (
        !empty($profile["profile_picture"]) &&
        $profile["profile_picture"] !== "default.png" &&
        file_exists(
            __DIR__ .
            "/profile/" .
            $profile["profile_picture"]
        )
    ): ?>

        <img
            src="profile/<?= e(
                $profile["profile_picture"]
            ); ?>?v=<?= time(); ?>"
            alt="Profile Picture"
            class="hero-profile"
        >

    <?php else: ?>

        <div class="hero-avatar">

            <svg
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-8 9v-2c0-3.3 3.6-5 8-5s8 1.7 8 5v2H4Z"/>
            </svg>

        </div>

    <?php endif; ?>

</div>

<div class="dashboard-stats">

    <div class="dashboard-stat highlight">
        <span>
            Profile Completion
        </span>

        <strong>
            <?= $profile_completion; ?>%
        </strong>
    </div>

    <div class="dashboard-stat">
        <span>
            Eligible Scholarships
        </span>

        <strong>
            <?= $eligible_count; ?>
        </strong>
    </div>

    <div class="dashboard-stat">
        <span>
            Total Applications
        </span>

        <strong>
            <?= $total_applications; ?>
        </strong>
    </div>

    <div class="dashboard-stat">
        <span>
            Under Review
        </span>

        <strong>
            <?= $review_applications; ?>
        </strong>
    </div>

    <div class="dashboard-stat">
        <span>
            Approved
        </span>

        <strong>
            <?= $approved_applications; ?>
        </strong>
    </div>

</div>

<div class="dashboard-layout-grid">

    <div>

        <div class="card">

            <h2 class="card-title">
                Profile Completion
            </h2>

            <p style="color:#6B7280;line-height:1.7;">
                A complete profile helps ScholarTrack compare
                your information with scholarship requirements.
            </p>

            <div class="progress-wrap">

                <div class="progress-track">

                    <div
                        class="progress-bar"
                        style="width:<?= $profile_completion; ?>%;"
                    ></div>

                </div>

                <div class="progress-meta">

                    <span>
                        <?= $completed_fields; ?>
                        of
                        <?= count($profile_fields); ?>
                        required fields completed
                    </span>

                    <strong>
                        <?= $profile_completion; ?>%
                    </strong>

                </div>

            </div>

            <br>

            <a
                href="profile.php"
                class="btn btn-primary"
            >
                Complete My Profile
            </a>

        </div>

        <div class="card">

            <h2 class="card-title">
                Quick Actions
            </h2>

            <div class="quick-actions">

                <a
                    href="scholarships.php"
                    class="quick-action"
                >
                    Find Scholarships
                </a>

                <a
                    href="my_applications.php"
                    class="quick-action"
                >
                    Track Applications
                </a>

                <a
                    href="notifications.php"
                    class="quick-action"
                >
                    View Notifications
                </a>

            </div>

        </div>

        <div class="card">

            <h2 class="card-title">
                Recent Applications
            </h2>

            <?php if (
                empty($recent_applications)
            ): ?>

                <p class="empty-message">
                    You have not submitted any applications yet.
                </p>

                <div style="text-align:center;">

                    <a
                        href="scholarships.php"
                        class="btn btn-primary"
                    >
                        Browse Scholarships
                    </a>

                </div>

            <?php else: ?>

                <div class="application-list">

                    <?php foreach (
                        $recent_applications
                        as $application
                    ): ?>

                        <?php
                        $status_class =
                            dashboard_status_class(
                                $application["status"]
                            );
                        ?>

                        <div class="application-item">

                            <div>

                                <h4>
                                    <?= e(
                                        $application["title"]
                                    ); ?>
                                </h4>

                                <p>
                                    <?= e(
                                        $application["provider"]
                                    ); ?>

                                    ·

                                    Submitted
                                    <?= date(
                                        "M d, Y",
                                        strtotime(
                                            $application["submitted_at"]
                                        )
                                    ); ?>
                                </p>

                            </div>

                            <span
                                class="badge badge-<?= e(
                                    $status_class
                                ); ?>"
                            >
                                <?= e(
                                    $application["status"]
                                ); ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

                <br>

                <a
                    href="my_applications.php"
                    class="btn btn-secondary"
                >
                    View All Applications
                </a>

            <?php endif; ?>

        </div>

        <div class="card">

            <h2 class="card-title">
                Recommended Scholarships
            </h2>

            <?php if (
                !$has_eligibility_data
            ): ?>

                <div class="alert warning">

                    Add your GWA and annual family income
                    to receive scholarship recommendations.

                    <a
                        href="profile.php"
                        style="
                            color:#467235;
                            font-weight:bold;
                        "
                    >
                        Complete Profile
                    </a>

                </div>

            <?php elseif (
                empty($recommended_scholarships)
            ): ?>

                <p class="empty-message">
                    No recommendations are available based on
                    your current profile.
                </p>

            <?php else: ?>

                <div class="scholarship-grid">

                    <?php foreach (
                        $recommended_scholarships
                        as $scholarship
                    ): ?>

                        <div class="scholarship-card-mini">

                            <h4>
                                <?= e(
                                    $scholarship["title"]
                                ); ?>
                            </h4>

                            <p class="provider">
                                <?= e(
                                    $scholarship["provider"]
                                ); ?>
                            </p>

                            <div class="mini-meta">

                                <span>
                                    Category:
                                    <?= !empty(
                                        $scholarship["category"]
                                    )
                                        ? e(
                                            $scholarship["category"]
                                        )
                                        : "Not specified"; ?>
                                </span>

                                <span>
                                    Monthly Allowance:
                                    <?= dashboard_money(
                                        $scholarship["monthly_allowance"]
                                    ); ?>
                                </span>

                                <span>
                                    Deadline:
                                    <?= !empty(
                                        $scholarship["deadline"]
                                    )
                                        ? date(
                                            "M d, Y",
                                            strtotime(
                                                $scholarship["deadline"]
                                            )
                                        )
                                        : "To be announced"; ?>
                                </span>

                            </div>

                            <a
                                href="scholarship_view.php?id=<?= (int) $scholarship["id"]; ?>"
                                class="btn btn-primary"
                            >
                                View Details
                            </a>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

        <div class="card">

            <h2 class="card-title">
                Latest Scholarships
            </h2>

            <?php if (
                empty($latest_scholarships)
            ): ?>

                <p class="empty-message">
                    No active scholarships are available.
                </p>

            <?php else: ?>

                <div class="scholarship-grid">

                    <?php foreach (
                        $latest_scholarships
                        as $scholarship
                    ): ?>

                        <div class="scholarship-card-mini">

                            <h4>
                                <?= e(
                                    $scholarship["title"]
                                ); ?>
                            </h4>

                            <p class="provider">
                                <?= e(
                                    $scholarship["provider"]
                                ); ?>
                            </p>

                            <div class="mini-meta">

                                <span>
                                    Region:
                                    <?= !empty(
                                        $scholarship["region"]
                                    )
                                        ? e(
                                            $scholarship["region"]
                                        )
                                        : "Not specified"; ?>
                                </span>

                                <span>
                                    Deadline:
                                    <?= !empty(
                                        $scholarship["deadline"]
                                    )
                                        ? date(
                                            "M d, Y",
                                            strtotime(
                                                $scholarship["deadline"]
                                            )
                                        )
                                        : "To be announced"; ?>
                                </span>

                            </div>

                            <a
                                href="scholarship_view.php?id=<?= (int) $scholarship["id"]; ?>"
                                class="btn btn-secondary"
                            >
                                View Scholarship
                            </a>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <div>

        <div class="card">

            <h2 class="card-title">
                Application Status
            </h2>

            <div class="application-list">

                <div class="application-item">
                    <span>Pending</span>
                    <strong><?= $pending_applications; ?></strong>
                </div>

                <div class="application-item">
                    <span>Under Review</span>
                    <strong><?= $review_applications; ?></strong>
                </div>

                <div class="application-item">
                    <span>Waitlisted</span>
                    <strong><?= $waitlisted_applications; ?></strong>
                </div>

                <div class="application-item">
                    <span>Approved</span>
                    <strong><?= $approved_applications; ?></strong>
                </div>

                <div class="application-item">
                    <span>Rejected</span>
                    <strong><?= $rejected_applications; ?></strong>
                </div>

            </div>

        </div>

        <div class="card">

            <h2 class="card-title">
                Upcoming Deadlines
            </h2>

            <?php if (
                empty($upcoming_deadlines)
            ): ?>

                <p class="empty-message">
                    No upcoming deadlines were found.
                </p>

            <?php else: ?>

                <div class="deadline-list">

                    <?php foreach (
                        $upcoming_deadlines
                        as $scholarship
                    ): ?>

                        <div class="deadline-item">

                            <div class="deadline-date">

                                <strong>
                                    <?= date(
                                        "d",
                                        strtotime(
                                            $scholarship["deadline"]
                                        )
                                    ); ?>
                                </strong>

                                <span>
                                    <?= date(
                                        "M",
                                        strtotime(
                                            $scholarship["deadline"]
                                        )
                                    ); ?>
                                </span>

                            </div>

                            <div>

                                <h4>
                                    <?= e(
                                        $scholarship["title"]
                                    ); ?>
                                </h4>

                                <p>
                                    <?= e(
                                        $scholarship["provider"]
                                    ); ?>
                                </p>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

        <div class="card">

            <h2 class="card-title">
                Latest Notifications
            </h2>

            <?php if (
                empty($latest_notifications)
            ): ?>

                <p class="empty-message">
                    You do not have notifications yet.
                </p>

            <?php else: ?>

                <div class="notification-list">

                    <?php foreach (
                        $latest_notifications
                        as $notification
                    ): ?>

                        <div
                            class="notification-item <?= (int) $notification["is_read"] === 0
                                ? "unread"
                                : ""; ?>"
                        >

                            <?= e(
                                $notification["message"]
                            ); ?>

                            <small>
                                <?= date(
                                    "M d, Y h:i A",
                                    strtotime(
                                        $notification["created_at"]
                                    )
                                ); ?>
                            </small>

                        </div>

                    <?php endforeach; ?>

                </div>

                <br>

                <a
                    href="notifications.php"
                    class="btn btn-secondary"
                >
                    View All Notifications
                </a>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php include "footer.php"; ?>