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
        2
    );
}

/*
|--------------------------------------------------------------------------
| Scholarship Utilization
|--------------------------------------------------------------------------
*/

$scholarship_report_statement = $pdo->query(
    "SELECT
        scholarships.id,
        scholarships.title,
        scholarships.provider,
        scholarships.category,
        scholarships.region,
        scholarships.deadline,

        COUNT(applications.id) AS total_applications,

        SUM(applications.status = 'Pending') AS pending_count,
        SUM(applications.status = 'Under Review') AS review_count,
        SUM(applications.status = 'Waitlisted') AS waitlisted_count,
        SUM(applications.status = 'Approved') AS approved_count,
        SUM(applications.status = 'Rejected') AS rejected_count

     FROM scholarships

     LEFT JOIN applications
        ON applications.scholarship_id = scholarships.id

     GROUP BY
        scholarships.id,
        scholarships.title,
        scholarships.provider,
        scholarships.category,
        scholarships.region,
        scholarships.deadline

     ORDER BY
        total_applications DESC,
        scholarships.title ASC"
);

$scholarship_reports =
    $scholarship_report_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Monthly Applications
|--------------------------------------------------------------------------
*/

$monthly_report_statement = $pdo->query(
    "SELECT
        DATE_FORMAT(submitted_at, '%Y-%m') AS month_value,
        DATE_FORMAT(submitted_at, '%M %Y') AS month_name,

        COUNT(*) AS total_count,

        SUM(status = 'Pending') AS pending_count,
        SUM(status = 'Under Review') AS review_count,
        SUM(status = 'Waitlisted') AS waitlisted_count,
        SUM(status = 'Approved') AS approved_count,
        SUM(status = 'Rejected') AS rejected_count

     FROM applications

     GROUP BY
        DATE_FORMAT(submitted_at, '%Y-%m'),
        DATE_FORMAT(submitted_at, '%M %Y')

     ORDER BY month_value DESC

     LIMIT 12"
);

$monthly_reports =
    $monthly_report_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Student Registration Report
|--------------------------------------------------------------------------
*/

$registration_report_statement = $pdo->query(
    "SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month_value,
        DATE_FORMAT(created_at, '%M %Y') AS month_name,
        COUNT(*) AS registration_count

     FROM users

     WHERE role = 'student'

     GROUP BY
        DATE_FORMAT(created_at, '%Y-%m'),
        DATE_FORMAT(created_at, '%M %Y')

     ORDER BY month_value DESC

     LIMIT 12"
);

$registration_reports =
    $registration_report_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Approved Scholars
|--------------------------------------------------------------------------
*/

$approved_scholar_statement = $pdo->query(
    "SELECT
        applications.id,
        applications.submitted_at,
        applications.admin_note,

        users.fullname,
        users.email,

        student_profiles.student_no,
        student_profiles.school,
        student_profiles.course,
        student_profiles.other_course,
        student_profiles.year_level,

        scholarships.title,
        scholarships.provider

     FROM applications

     INNER JOIN users
        ON users.id = applications.student_id

     LEFT JOIN student_profiles
        ON student_profiles.user_id = users.id

     INNER JOIN scholarships
        ON scholarships.id = applications.scholarship_id

     WHERE applications.status = 'Approved'

     ORDER BY applications.submitted_at DESC"
);

$approved_scholars =
    $approved_scholar_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Waitlisted Applications
|--------------------------------------------------------------------------
*/

$waitlisted_statement = $pdo->query(
    "SELECT
        applications.id,
        applications.submitted_at,
        applications.admin_note,

        users.fullname,
        users.email,

        student_profiles.student_no,
        student_profiles.course,
        student_profiles.other_course,

        scholarships.title,
        scholarships.provider

     FROM applications

     INNER JOIN users
        ON users.id = applications.student_id

     LEFT JOIN student_profiles
        ON student_profiles.user_id = users.id

     INNER JOIN scholarships
        ON scholarships.id = applications.scholarship_id

     WHERE applications.status = 'Waitlisted'

     ORDER BY applications.submitted_at DESC"
);

$waitlisted_reports =
    $waitlisted_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Rejected Applications
|--------------------------------------------------------------------------
*/

$rejected_statement = $pdo->query(
    "SELECT
        applications.id,
        applications.submitted_at,
        applications.admin_note,

        users.fullname,
        users.email,

        student_profiles.student_no,
        student_profiles.course,
        student_profiles.other_course,

        scholarships.title,
        scholarships.provider

     FROM applications

     INNER JOIN users
        ON users.id = applications.student_id

     LEFT JOIN student_profiles
        ON student_profiles.user_id = users.id

     INNER JOIN scholarships
        ON scholarships.id = applications.scholarship_id

     WHERE applications.status = 'Rejected'

     ORDER BY applications.submitted_at DESC"
);

$rejected_reports =
    $rejected_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function report_course_name($row)
{
    if (
        ($row["course"] ?? "") === "Other" &&
        !empty($row["other_course"])
    ) {
        return $row["other_course"];
    }

    return $row["course"] ?? "";
}

$page_title = "Reports";

include "header.php";
?>

<style>
    .reports-hero {
        background:
            linear-gradient(
                135deg,
                #283F24,
                #467235
            );
        color: #FFFFFF;
        padding: 30px;
        border-radius: 18px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        box-shadow:
            0 10px 28px
            rgba(40, 63, 36, 0.15);
    }

    .reports-hero h1 {
        color: #FFBF00;
        margin-bottom: 8px;
    }

    .reports-hero p {
        color:
            rgba(255, 255, 255, 0.86);
        line-height: 1.7;
        max-width: 760px;
    }

    .report-stats {
        display: grid;
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
        gap: 15px;
        margin-bottom: 24px;
    }

    .report-status-grid {
        display: grid;
        grid-template-columns:
            repeat(5, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 24px;
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

    .report-note {
        background: #FFF8D4;
        border: 1px solid #E7CB67;
        border-radius: 10px;
        padding: 15px;
        color: #6C5200;
        line-height: 1.7;
        margin-bottom: 22px;
    }

    .section-title {
        color: #283F24;
        margin-bottom: 16px;
    }

    .status-counts {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .print-button {
        white-space: nowrap;
    }

    .two-column-reports {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .report-card {
        margin-bottom: 22px;
    }

    @media print {
        .topbar,
        .sidebar,
        .print-button,
        .site-footer {
            display: none !important;
        }

        .main-content {
            margin-left: 0 !important;
            padding: 20px !important;
        }

        body {
            background: #FFFFFF;
        }

        .card,
        .stat-card,
        .status-box {
            box-shadow: none !important;
            break-inside: avoid;
        }

        a {
            color: #000000 !important;
            text-decoration: none !important;
        }
    }

    @media (max-width: 1100px) {
        .report-stats {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .report-status-grid {
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 850px) {
        .reports-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .two-column-reports {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 650px) {
        .report-stats,
        .report-status-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="reports-hero">

    <div>

        <h1>
            ScholarTrack Reports
        </h1>

        <p>
            Review scholarship utilization, monthly application
            trends, registration activity, approved scholars,
            waitlisted students, and rejected applications.
        </p>

    </div>

    <button
        type="button"
        class="btn btn-gold print-button"
        onclick="window.print()"
    >
        Print Reports
    </button>

</div>

<div class="report-stats">

    <div class="stat-card highlight">
        <h3>Registered Students</h3>
        <div class="number">
            <?= $total_students; ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Total Scholarships</h3>
        <div class="number">
            <?= $total_scholarships; ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Total Applications</h3>
        <div class="number">
            <?= $total_applications; ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Approval Rate</h3>
        <div class="number">
            <?= $approval_rate; ?>%
        </div>
    </div>

</div>

<div class="report-status-grid">

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

<div class="report-note">

    These reports are generated from the current ScholarTrack
    database records. Scholarship decisions remain subject to
    the official evaluation of the respective scholarship providers.

</div>

<div class="card report-card">

    <h2 class="section-title">
        Scholarship Utilization Report
    </h2>

    <?php if (empty($scholarship_reports)): ?>

        <p class="empty-message">
            No scholarship records are available.
        </p>

    <?php else: ?>

        <div class="table-responsive">

            <table>

                <thead>
                    <tr>
                        <th>Scholarship</th>
                        <th>Provider</th>
                        <th>Category</th>
                        <th>Region</th>
                        <th>Deadline</th>
                        <th>Total</th>
                        <th>Status Breakdown</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach (
                        $scholarship_reports
                        as $report
                    ): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= e(
                                        $report["title"]
                                    ); ?>
                                </strong>
                            </td>

                            <td>
                                <?= e(
                                    $report["provider"]
                                ); ?>
                            </td>

                            <td>
                                <?= !empty(
                                    $report["category"]
                                )
                                    ? e(
                                        $report["category"]
                                    )
                                    : "Not specified"; ?>
                            </td>

                            <td>
                                <?= !empty(
                                    $report["region"]
                                )
                                    ? e(
                                        $report["region"]
                                    )
                                    : "Not specified"; ?>
                            </td>

                            <td>
                                <?= !empty(
                                    $report["deadline"]
                                )
                                    ? date(
                                        "F d, Y",
                                        strtotime(
                                            $report["deadline"]
                                        )
                                    )
                                    : "To be announced"; ?>
                            </td>

                            <td>
                                <?= (int)
                                    $report["total_applications"]; ?>
                            </td>

                            <td>

                                <div class="status-counts">

                                    <span class="badge badge-pending">
                                        Pending:
                                        <?= (int)
                                            ($report["pending_count"] ?? 0); ?>
                                    </span>

                                    <span class="badge badge-under-review">
                                        Under Review:
                                        <?= (int)
                                            ($report["review_count"] ?? 0); ?>
                                    </span>

                                    <span class="badge badge-waitlisted">
                                        Waitlisted:
                                        <?= (int)
                                            ($report["waitlisted_count"] ?? 0); ?>
                                    </span>

                                    <span class="badge badge-approved">
                                        Approved:
                                        <?= (int)
                                            ($report["approved_count"] ?? 0); ?>
                                    </span>

                                    <span class="badge badge-rejected">
                                        Rejected:
                                        <?= (int)
                                            ($report["rejected_count"] ?? 0); ?>
                                    </span>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<div class="two-column-reports">

    <div class="card report-card">

        <h2 class="section-title">
            Monthly Application Summary
        </h2>

        <?php if (empty($monthly_reports)): ?>

            <p class="empty-message">
                No application data is available.
            </p>

        <?php else: ?>

            <div class="table-responsive">

                <table>

                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total</th>
                            <th>Pending</th>
                            <th>Review</th>
                            <th>Waitlisted</th>
                            <th>Approved</th>
                            <th>Rejected</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach (
                            $monthly_reports
                            as $report
                        ): ?>

                            <tr>

                                <td>
                                    <?= e(
                                        $report["month_name"]
                                    ); ?>
                                </td>

                                <td>
                                    <?= (int)
                                        $report["total_count"]; ?>
                                </td>

                                <td>
                                    <?= (int)
                                        ($report["pending_count"] ?? 0); ?>
                                </td>

                                <td>
                                    <?= (int)
                                        ($report["review_count"] ?? 0); ?>
                                </td>

                                <td>
                                    <?= (int)
                                        ($report["waitlisted_count"] ?? 0); ?>
                                </td>

                                <td>
                                    <?= (int)
                                        ($report["approved_count"] ?? 0); ?>
                                </td>

                                <td>
                                    <?= (int)
                                        ($report["rejected_count"] ?? 0); ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

    <div class="card report-card">

        <h2 class="section-title">
            Student Registration Report
        </h2>

        <?php if (
            empty($registration_reports)
        ): ?>

            <p class="empty-message">
                No registration data is available.
            </p>

        <?php else: ?>

            <div class="table-responsive">

                <table>

                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Registered Students</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach (
                            $registration_reports
                            as $report
                        ): ?>

                            <tr>

                                <td>
                                    <?= e(
                                        $report["month_name"]
                                    ); ?>
                                </td>

                                <td>
                                    <?= (int)
                                        $report["registration_count"]; ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

<div class="card report-card">

    <h2 class="section-title">
        Approved Scholars Report
    </h2>

    <?php if (empty($approved_scholars)): ?>

        <p class="empty-message">
            No approved applications found.
        </p>

    <?php else: ?>

        <div class="table-responsive">

            <table>

                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student Number</th>
                        <th>School</th>
                        <th>Course and Year</th>
                        <th>Scholarship</th>
                        <th>Provider</th>
                        <th>Date Submitted</th>
                        <th>Admin Note</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach (
                        $approved_scholars
                        as $scholar
                    ): ?>

                        <?php
                        $course =
                            report_course_name(
                                $scholar
                            );
                        ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= e(
                                        $scholar["fullname"]
                                    ); ?>
                                </strong>

                                <br>

                                <small>
                                    <?= e(
                                        $scholar["email"]
                                    ); ?>
                                </small>
                            </td>

                            <td>
                                <?= !empty(
                                    $scholar["student_no"]
                                )
                                    ? e(
                                        $scholar["student_no"]
                                    )
                                    : "Not provided"; ?>
                            </td>

                            <td>
                                <?= !empty(
                                    $scholar["school"]
                                )
                                    ? e(
                                        $scholar["school"]
                                    )
                                    : "Not provided"; ?>
                            </td>

                            <td>
                                <?= $course !== ""
                                    ? e($course)
                                    : "Not provided"; ?>

                                <br>

                                <small>
                                    <?= !empty(
                                        $scholar["year_level"]
                                    )
                                        ? e(
                                            $scholar["year_level"]
                                        )
                                        : "Not provided"; ?>
                                </small>
                            </td>

                            <td>
                                <?= e(
                                    $scholar["title"]
                                ); ?>
                            </td>

                            <td>
                                <?= e(
                                    $scholar["provider"]
                                ); ?>
                            </td>

                            <td>
                                <?= date(
                                    "F d, Y",
                                    strtotime(
                                        $scholar["submitted_at"]
                                    )
                                ); ?>
                            </td>

                            <td>
                                <?= !empty(
                                    $scholar["admin_note"]
                                )
                                    ? e(
                                        $scholar["admin_note"]
                                    )
                                    : "No note"; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<div class="card report-card">

    <h2 class="section-title">
        Waitlisted Applications Report
    </h2>

    <?php if (empty($waitlisted_reports)): ?>

        <p class="empty-message">
            No waitlisted applications found.
        </p>

    <?php else: ?>

        <div class="table-responsive">

            <table>

                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student Number</th>
                        <th>Course</th>
                        <th>Scholarship</th>
                        <th>Provider</th>
                        <th>Date Submitted</th>
                        <th>Admin Note</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach (
                        $waitlisted_reports
                        as $report
                    ): ?>

                        <?php
                        $course =
                            report_course_name(
                                $report
                            );
                        ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= e(
                                        $report["fullname"]
                                    ); ?>
                                </strong>

                                <br>

                                <small>
                                    <?= e(
                                        $report["email"]
                                    ); ?>
                                </small>
                            </td>

                            <td>
                                <?= !empty(
                                    $report["student_no"]
                                )
                                    ? e(
                                        $report["student_no"]
                                    )
                                    : "Not provided"; ?>
                            </td>

                            <td>
                                <?= $course !== ""
                                    ? e($course)
                                    : "Not provided"; ?>
                            </td>

                            <td>
                                <?= e(
                                    $report["title"]
                                ); ?>
                            </td>

                            <td>
                                <?= e(
                                    $report["provider"]
                                ); ?>
                            </td>

                            <td>
                                <?= date(
                                    "F d, Y",
                                    strtotime(
                                        $report["submitted_at"]
                                    )
                                ); ?>
                            </td>

                            <td>
                                <?= !empty(
                                    $report["admin_note"]
                                )
                                    ? e(
                                        $report["admin_note"]
                                    )
                                    : "No note"; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<div class="card report-card">

    <h2 class="section-title">
        Rejected Applications Report
    </h2>

    <?php if (empty($rejected_reports)): ?>

        <p class="empty-message">
            No rejected applications found.
        </p>

    <?php else: ?>

        <div class="table-responsive">

            <table>

                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student Number</th>
                        <th>Course</th>
                        <th>Scholarship</th>
                        <th>Provider</th>
                        <th>Date Submitted</th>
                        <th>Admin Note</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach (
                        $rejected_reports
                        as $report
                    ): ?>

                        <?php
                        $course =
                            report_course_name(
                                $report
                            );
                        ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= e(
                                        $report["fullname"]
                                    ); ?>
                                </strong>

                                <br>

                                <small>
                                    <?= e(
                                        $report["email"]
                                    ); ?>
                                </small>
                            </td>

                            <td>
                                <?= !empty(
                                    $report["student_no"]
                                )
                                    ? e(
                                        $report["student_no"]
                                    )
                                    : "Not provided"; ?>
                            </td>

                            <td>
                                <?= $course !== ""
                                    ? e($course)
                                    : "Not provided"; ?>
                            </td>

                            <td>
                                <?= e(
                                    $report["title"]
                                ); ?>
                            </td>

                            <td>
                                <?= e(
                                    $report["provider"]
                                ); ?>
                            </td>

                            <td>
                                <?= date(
                                    "F d, Y",
                                    strtotime(
                                        $report["submitted_at"]
                                    )
                                ); ?>
                            </td>

                            <td>
                                <?= !empty(
                                    $report["admin_note"]
                                )
                                    ? e(
                                        $report["admin_note"]
                                    )
                                    : "No note"; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<?php include "footer.php"; ?>