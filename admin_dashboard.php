<?php
require_once "config.php";

require_admin();

/*
|--------------------------------------------------------------------------
| Main Statistics
|--------------------------------------------------------------------------
*/

$total_students = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM users
     WHERE role = 'student'"
)->fetchColumn();

$total_scholarships = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM scholarships"
)->fetchColumn();

$total_applications = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM applications"
)->fetchColumn();

$pending_applications = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM applications
     WHERE status = 'Pending'"
)->fetchColumn();

$review_applications = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM applications
     WHERE status = 'Under Review'"
)->fetchColumn();

$waitlisted_applications = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM applications
     WHERE status = 'Waitlisted'"
)->fetchColumn();

$approved_applications = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM applications
     WHERE status = 'Approved'"
)->fetchColumn();

$rejected_applications = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM applications
     WHERE status = 'Rejected'"
)->fetchColumn();

$approval_rate = 0;

if ($total_applications > 0) {
    $approval_rate = round(
        ($approved_applications / $total_applications) * 100,
        1
    );
}

/*
|--------------------------------------------------------------------------
| Recent Applications
|--------------------------------------------------------------------------
*/

$recent_application_statement = $pdo->query(
    "SELECT
        applications.id,
        applications.status,
        applications.submitted_at,

        users.fullname,
        users.email,

        scholarships.title,
        scholarships.provider

     FROM applications

     INNER JOIN users
        ON users.id = applications.student_id

     INNER JOIN scholarships
        ON scholarships.id = applications.scholarship_id

     ORDER BY applications.submitted_at DESC

     LIMIT 8"
);

$recent_applications =
    $recent_application_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Latest Students
|--------------------------------------------------------------------------
*/

$recent_student_statement = $pdo->query(
    "SELECT
        users.id,
        users.fullname,
        users.email,
        users.created_at,

        student_profiles.student_no,
        student_profiles.school,
        student_profiles.course,
        student_profiles.other_course,
        student_profiles.profile_picture

     FROM users

     LEFT JOIN student_profiles
        ON student_profiles.user_id = users.id

     WHERE users.role = 'student'

     ORDER BY users.created_at DESC

     LIMIT 6"
);

$recent_students =
    $recent_student_statement->fetchAll();

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
| Top Scholarships
|--------------------------------------------------------------------------
*/

$top_scholarship_statement = $pdo->query(
    "SELECT
        scholarships.id,
        scholarships.title,
        scholarships.provider,
        COUNT(applications.id) AS application_count

     FROM scholarships

     LEFT JOIN applications
        ON applications.scholarship_id = scholarships.id

     GROUP BY
        scholarships.id,
        scholarships.title,
        scholarships.provider

     ORDER BY
        application_count DESC,
        scholarships.title ASC

     LIMIT 5"
);

$top_scholarships =
    $top_scholarship_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Monthly Application Data
|--------------------------------------------------------------------------
*/

$monthly_statement = $pdo->query(
    "SELECT
        DATE_FORMAT(submitted_at, '%Y-%m') AS month_value,
        DATE_FORMAT(submitted_at, '%b %Y') AS month_name,
        COUNT(*) AS total_count

     FROM applications

     GROUP BY
        DATE_FORMAT(submitted_at, '%Y-%m'),
        DATE_FORMAT(submitted_at, '%b %Y')

     ORDER BY month_value DESC

     LIMIT 6"
);

$monthly_data =
    array_reverse(
        $monthly_statement->fetchAll()
    );

$monthly_max = 1;

foreach ($monthly_data as $month) {
    if ((int) $month["total_count"] > $monthly_max) {
        $monthly_max =
            (int) $month["total_count"];
    }
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function admin_status_class($status)
{
    return strtolower(
        str_replace(
            " ",
            "-",
            $status
        )
    );
}

function student_course_name($student)
{
    if (
        ($student["course"] ?? "") === "Other" &&
        !empty($student["other_course"])
    ) {
        return $student["other_course"];
    }

    return $student["course"] ?? "";
}

$page_title = "Admin Dashboard";

include "header.php";
?>

<style>
    .admin-hero {
        background:
            linear-gradient(
                135deg,
                #1F351C,
                #467235
            );
        color: #FFFFFF;
        border-radius: 18px;
        padding: 32px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 25px;
        box-shadow:
            0 10px 28px
            rgba(40, 63, 36, 0.16);
    }

    .admin-hero h1 {
        color: #FFBF00;
        margin-bottom: 9px;
    }

    .admin-hero p {
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

    .hero-mark {
        width: 125px;
        height: 125px;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.11);
        border: 1px solid rgba(255, 255, 255, 0.16);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .hero-mark svg {
        width: 64px;
        height: 64px;
        fill: #FFBF00;
    }

    .admin-stats {
        display: grid;
        grid-template-columns:
            repeat(5, minmax(0, 1fr));
        gap: 15px;
        margin-bottom: 24px;
    }

    .admin-stat {
        background: #FFFFFF;
        border: 1px solid #DDE2D9;
        border-radius: 13px;
        padding: 19px;
        box-shadow:
            0 3px 10px
            rgba(40, 63, 36, 0.06);
    }

    .admin-stat.highlight {
        background: #FFF78D;
    }

    .admin-stat span {
        display: block;
        color: #6B7280;
        font-size: 12px;
        margin-bottom: 8px;
    }

    .admin-stat strong {
        color: #283F24;
        font-size: 29px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns:
            minmax(0, 2fr)
            minmax(300px, 1fr);
        gap: 20px;
        align-items: start;
    }

    .status-overview {
        display: grid;
        grid-template-columns:
            repeat(5, minmax(0, 1fr));
        gap: 12px;
    }

    .status-box {
        border-radius: 11px;
        padding: 16px;
        text-align: center;
    }

    .status-box strong {
        display: block;
        font-size: 26px;
        margin-bottom: 5px;
    }

    .status-pending {
        background: #FFF8D4;
        color: #6C5200;
        border: 1px solid #E7CB67;
    }

    .status-review {
        background: #EAF1FF;
        color: #244A84;
        border: 1px solid #AFC7F5;
    }

    .status-waitlisted {
        background: #FFF0D7;
        color: #8A5200;
        border: 1px solid #F0C47D;
    }

    .status-approved {
        background: #E4F2DF;
        color: #24551C;
        border: 1px solid #A9D49B;
    }

    .status-rejected {
        background: #FCE7E5;
        color: #8E201A;
        border: 1px solid #E7AAA5;
    }

    .chart-list {
        display: flex;
        flex-direction: column;
        gap: 13px;
    }

    .chart-row {
        display: grid;
        grid-template-columns:
            72px 1fr 35px;
        gap: 10px;
        align-items: center;
    }

    .chart-label {
        color: #6B7280;
        font-size: 12px;
    }

    .chart-track {
        height: 13px;
        background: #E6EAE3;
        border-radius: 20px;
        overflow: hidden;
    }

    .chart-fill {
        height: 100%;
        background:
            linear-gradient(
                90deg,
                #467235,
                #FFBF00
            );
        border-radius: 20px;
    }

    .chart-value {
        color: #283F24;
        font-size: 13px;
        font-weight: bold;
        text-align: right;
    }

    .application-list,
    .student-list,
    .deadline-list,
    .top-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .application-item,
    .student-item,
    .deadline-item,
    .top-item {
        border: 1px solid #DDE2D9;
        border-radius: 10px;
        padding: 14px;
        background: #F7F9F6;
    }

    .application-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .application-item h4,
    .student-item h4,
    .deadline-item h4,
    .top-item h4 {
        color: #283F24;
        margin-bottom: 5px;
        line-height: 1.4;
    }

    .application-item p,
    .student-item p,
    .deadline-item p,
    .top-item p {
        color: #6B7280;
        font-size: 12px;
        line-height: 1.5;
    }

    .student-item {
        display: grid;
        grid-template-columns:
            54px 1fr;
        gap: 12px;
        align-items: center;
    }

    .student-photo,
    .student-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: 2px solid #FFBF00;
        object-fit: cover;
        background: #FFFFFF;
    }

    .student-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #467235;
        font-weight: bold;
    }

    .deadline-item {
        display: grid;
        grid-template-columns:
            58px 1fr;
        gap: 12px;
        align-items: center;
    }

    .deadline-date {
        background: #FFF2BD;
        color: #775800;
        border-radius: 9px;
        text-align: center;
        padding: 9px 5px;
    }

    .deadline-date strong {
        display: block;
        font-size: 20px;
    }

    .deadline-date span {
        font-size: 10px;
        text-transform: uppercase;
    }

    .top-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .top-count {
        min-width: 45px;
        height: 45px;
        border-radius: 10px;
        background: #FFF78D;
        color: #283F24;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .section-link {
        margin-top: 15px;
    }

    @media (max-width: 1200px) {
        .admin-stats {
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
        }

        .status-overview {
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 950px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 750px) {
        .admin-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .status-overview {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .application-item {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 600px) {
        .admin-stats {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }
    }
</style>

<div class="admin-hero">

    <div>

        <h1>
            Welcome,
            <?= e(
                $_SESSION["user"]["fullname"]
            ); ?>!
        </h1>

        <p>
            Review scholarship applications, manage listings,
            monitor student activity, and view ScholarTrack
            performance from one administrative workspace.
        </p>

        <div class="hero-actions">

            <a
                href="manage_applications.php?status=Pending"
                class="btn btn-gold"
            >
                Review Pending Applications
            </a>

            <a
                href="scholarship_form.php"
                class="btn btn-secondary"
            >
                Add Scholarship
            </a>

            <a
                href="reports.php"
                class="btn btn-secondary"
            >
                View Reports
            </a>

        </div>

    </div>

    <div class="hero-mark">

        <svg viewBox="0 0 24 24">
            <path d="M12 2 3 6v6c0 5 3.8 9.7 9 11 5.2-1.3 9-6 9-11V6l-9-4Zm0 4.2 5 2.2V12c0 3.2-2.1 6.4-5 7.5-2.9-1.1-5-4.3-5-7.5V8.4l5-2.2Zm-1 3.3v3H8v2h3v3h2v-3h3v-2h-3v-3h-2Z"/>
        </svg>

    </div>

</div>

<div class="admin-stats">

    <div class="admin-stat highlight">
        <span>Total Students</span>
        <strong><?= $total_students; ?></strong>
    </div>

    <div class="admin-stat">
        <span>Scholarships</span>
        <strong><?= $total_scholarships; ?></strong>
    </div>

    <div class="admin-stat">
        <span>Total Applications</span>
        <strong><?= $total_applications; ?></strong>
    </div>

    <div class="admin-stat">
        <span>Needs Review</span>
        <strong>
            <?= $pending_applications + $review_applications; ?>
        </strong>
    </div>

    <div class="admin-stat">
        <span>Approval Rate</span>
        <strong><?= $approval_rate; ?>%</strong>
    </div>

</div>

<div class="card">

    <h2 class="card-title">
        Application Status Overview
    </h2>

    <div class="status-overview">

        <div class="status-box status-pending">
            <strong><?= $pending_applications; ?></strong>
            Pending
        </div>

        <div class="status-box status-review">
            <strong><?= $review_applications; ?></strong>
            Under Review
        </div>

        <div class="status-box status-waitlisted">
            <strong><?= $waitlisted_applications; ?></strong>
            Waitlisted
        </div>

        <div class="status-box status-approved">
            <strong><?= $approved_applications; ?></strong>
            Approved
        </div>

        <div class="status-box status-rejected">
            <strong><?= $rejected_applications; ?></strong>
            Rejected
        </div>

    </div>

</div>

<div class="dashboard-grid">

    <div>

        <div class="card">

            <h2 class="card-title">
                Monthly Applications
            </h2>

            <?php if (empty($monthly_data)): ?>

                <p class="empty-message">
                    No application data is available yet.
                </p>

            <?php else: ?>

                <div class="chart-list">

                    <?php foreach (
                        $monthly_data as $month
                    ): ?>

                        <?php
                        $width = (
                            (int) $month["total_count"] /
                            $monthly_max
                        ) * 100;
                        ?>

                        <div class="chart-row">

                            <span class="chart-label">
                                <?= e(
                                    $month["month_name"]
                                ); ?>
                            </span>

                            <div class="chart-track">

                                <div
                                    class="chart-fill"
                                    style="width:<?= $width; ?>%;"
                                ></div>

                            </div>

                            <span class="chart-value">
                                <?= (int) $month["total_count"]; ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

        <div class="card">

            <h2 class="card-title">
                Recent Applications
            </h2>

            <?php if (
                empty($recent_applications)
            ): ?>

                <p class="empty-message">
                    No applications have been submitted yet.
                </p>

            <?php else: ?>

                <div class="application-list">

                    <?php foreach (
                        $recent_applications
                        as $application
                    ): ?>

                        <?php
                        $status_class =
                            admin_status_class(
                                $application["status"]
                            );
                        ?>

                        <div class="application-item">

                            <div>

                                <h4>
                                    <?= e(
                                        $application["fullname"]
                                    ); ?>
                                </h4>

                                <p>
                                    Applied for
                                    <strong>
                                        <?= e(
                                            $application["title"]
                                        ); ?>
                                    </strong>

                                    ·

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

                <div class="section-link">

                    <a
                        href="manage_applications.php"
                        class="btn btn-primary"
                    >
                        Manage All Applications
                    </a>

                </div>

            <?php endif; ?>

        </div>

        <div class="card">

            <h2 class="card-title">
                Latest Student Registrations
            </h2>

            <?php if (
                empty($recent_students)
            ): ?>

                <p class="empty-message">
                    No student accounts have been registered.
                </p>

            <?php else: ?>

                <div class="student-list">

                    <?php foreach (
                        $recent_students as $student
                    ): ?>

                        <?php
                        $course =
                            student_course_name(
                                $student
                            );

                        $initial =
                            strtoupper(
                                substr(
                                    $student["fullname"],
                                    0,
                                    1
                                )
                            );
                        ?>

                        <div class="student-item">

                            <div>

                                <?php if (
                                    !empty($student["profile_picture"]) &&
                                    $student["profile_picture"] !== "default.png" &&
                                    file_exists(
                                        __DIR__ .
                                        "/profile/" .
                                        $student["profile_picture"]
                                    )
                                ): ?>

                                    <img
                                        src="profile/<?= e(
                                            $student["profile_picture"]
                                        ); ?>?v=<?= time(); ?>"
                                        alt="Student Profile"
                                        class="student-photo"
                                    >

                                <?php else: ?>

                                    <div class="student-avatar">
                                        <?= e($initial); ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                            <div>

                                <h4>
                                    <?= e(
                                        $student["fullname"]
                                    ); ?>
                                </h4>

                                <p>
                                    <?= !empty(
                                        $student["student_no"]
                                    )
                                        ? e(
                                            $student["student_no"]
                                        )
                                        : "No student number"; ?>

                                    ·

                                    <?= $course !== ""
                                        ? e($course)
                                        : "No course provided"; ?>

                                    ·

                                    Registered
                                    <?= date(
                                        "M d, Y",
                                        strtotime(
                                            $student["created_at"]
                                        )
                                    ); ?>
                                </p>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="section-link">

                    <a
                        href="manage_students.php"
                        class="btn btn-secondary"
                    >
                        View All Students
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <div>

        <div class="card">

            <h2 class="card-title">
                Pending Reviews
            </h2>

            <div class="top-item">

                <div>

                    <h4>
                        Applications Requiring Attention
                    </h4>

                    <p>
                        Pending and Under Review applications.
                    </p>

                </div>

                <div class="top-count">
                    <?= $pending_applications +
                        $review_applications; ?>
                </div>

            </div>

            <div class="section-link">

                <a
                    href="manage_applications.php"
                    class="btn btn-gold"
                >
                    Open Review Queue
                </a>

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
                    No upcoming scholarship deadlines.
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
                Top Scholarships
            </h2>

            <?php if (
                empty($top_scholarships)
            ): ?>

                <p class="empty-message">
                    No scholarship records found.
                </p>

            <?php else: ?>

                <div class="top-list">

                    <?php foreach (
                        $top_scholarships
                        as $scholarship
                    ): ?>

                        <div class="top-item">

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

                            <div class="top-count">
                                <?= (int) $scholarship["application_count"]; ?>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="section-link">

                    <a
                        href="manage_scholarships.php"
                        class="btn btn-secondary"
                    >
                        Manage Scholarships
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php include "footer.php"; ?>