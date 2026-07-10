<?php
require_once "config.php";

require_student();

$user_id = (int) $_SESSION["user"]["id"];

$allowed_statuses = [
    "Pending",
    "Under Review",
    "Waitlisted",
    "Approved",
    "Rejected"
];

$status_filter = trim($_GET["status"] ?? "");

/*
|--------------------------------------------------------------------------
| Statistics
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
| Application Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        applications.id,
        applications.status,
        applications.admin_note,
        applications.submitted_at,

        scholarships.id AS scholarship_id,
        scholarships.title,
        scholarships.provider,
        scholarships.category,
        scholarships.region,
        scholarships.deadline,
        scholarships.official_website,

        documents.file_name

    FROM applications

    INNER JOIN scholarships
        ON scholarships.id = applications.scholarship_id

    LEFT JOIN documents
        ON documents.application_id = applications.id

    WHERE applications.student_id = ?
";

$params = [
    $user_id
];

if (
    $status_filter !== "" &&
    in_array(
        $status_filter,
        $allowed_statuses,
        true
    )
) {
    $sql .= "
        AND applications.status = ?
    ";

    $params[] = $status_filter;
}

$sql .= "
    ORDER BY applications.submitted_at DESC
";

$application_statement =
    $pdo->prepare($sql);

$application_statement->execute($params);

$applications =
    $application_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function application_status_class($status)
{
    return strtolower(
        str_replace(
            " ",
            "-",
            $status
        )
    );
}

function application_reference($id)
{
    return "APP-" .
        str_pad(
            (string) $id,
            6,
            "0",
            STR_PAD_LEFT
        );
}

$page_title =
    "My Applications";

include "header.php";
?>

<style>
    .applications-hero {
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
        box-shadow:
            0 10px 28px
            rgba(40, 63, 36, 0.15);
    }

    .applications-hero h1 {
        color: #FFBF00;
        margin-bottom: 9px;
    }

    .applications-hero p {
        color:
            rgba(255, 255, 255, 0.86);
        line-height: 1.7;
        max-width: 850px;
    }

    .status-summary {
        display: grid;
        grid-template-columns:
            repeat(6, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }

    .summary-card {
        background: #FFFFFF;
        border: 1px solid #DDE2D9;
        border-radius: 12px;
        padding: 18px;
        text-align: center;
        box-shadow:
            0 3px 10px
            rgba(40, 63, 36, 0.06);
    }

    .summary-card:first-child {
        background: #FFF78D;
    }

    .summary-card strong {
        display: block;
        color: #283F24;
        font-size: 28px;
        margin-bottom: 5px;
    }

    .summary-card span {
        color: #6B7280;
        font-size: 12px;
    }

    .filter-card {
        margin-bottom: 22px;
    }

    .status-filters {
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
    }

    .filter-link {
        display: inline-block;
        padding: 9px 14px;
        border-radius: 20px;
        background: #E8ECE6;
        color: #283F24;
        font-size: 13px;
        font-weight: bold;
    }

    .filter-link:hover,
    .filter-link.active {
        background: #467235;
        color: #FFFFFF;
    }

    .application-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .application-card {
        border-left: 5px solid #467235;
    }

    .application-card.pending {
        border-left-color: #FFBF00;
    }

    .application-card.under-review {
        border-left-color: #3B82F6;
    }

    .application-card.waitlisted {
        border-left-color: #F59E0B;
    }

    .application-card.approved {
        border-left-color: #467235;
    }

    .application-card.rejected {
        border-left-color: #C0392B;
    }

    .application-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 18px;
    }

    .application-header h2 {
        color: #283F24;
        font-size: 22px;
        line-height: 1.4;
        margin-bottom: 6px;
    }

    .provider-name {
        color: #467235;
        font-weight: bold;
    }

    .application-meta {
        display: grid;
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .meta-item {
        background: #F7F9F6;
        border: 1px solid #E3E8E0;
        border-radius: 10px;
        padding: 13px;
    }

    .meta-item span {
        display: block;
        color: #6B7280;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 5px;
    }

    .meta-item strong {
        color: #283F24;
        font-size: 13px;
        word-break: break-word;
    }

    .status-message {
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 16px;
        line-height: 1.7;
        font-size: 14px;
    }

    .message-pending {
        background: #FFF8D4;
        border: 1px solid #E7CB67;
        color: #6C5200;
    }

    .message-under-review {
        background: #EAF1FF;
        border: 1px solid #AFC7F5;
        color: #244A84;
    }

    .message-waitlisted {
        background: #FFF0D7;
        border: 1px solid #F0C47D;
        color: #8A5200;
    }

    .message-approved {
        background: #E4F2DF;
        border: 1px solid #A9D49B;
        color: #24551C;
    }

    .message-rejected {
        background: #FCE7E5;
        border: 1px solid #E7AAA5;
        color: #8E201A;
    }

    .admin-note {
        background: #F7F9F6;
        border-left: 4px solid #467235;
        border-radius: 9px;
        padding: 15px;
        margin-bottom: 16px;
        color: #4B5563;
        line-height: 1.7;
    }

    .admin-note strong {
        color: #283F24;
    }

    .application-actions {
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
        border-top: 1px solid #DDE2D9;
        padding-top: 16px;
    }

    .empty-state {
        text-align: center;
        padding: 45px 20px;
    }

    .empty-state h2 {
        margin-bottom: 8px;
    }

    .empty-state p {
        color: #6B7280;
        margin-bottom: 18px;
    }

    @media (max-width: 1150px) {
        .status-summary {
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
        }

        .application-meta {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .status-summary {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .application-header {
            flex-direction: column;
        }

        .application-meta {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="applications-hero">

    <h1>
        My Scholarship Applications
    </h1>

    <p>
        Track each application from submission to final
        decision. Status updates and admin notes will appear
        here after the ScholarTrack administrator reviews
        your application.
    </p>

</div>

<div class="status-summary">

    <div class="summary-card">
        <strong>
            <?= $total_applications; ?>
        </strong>
        <span>Total</span>
    </div>

    <div class="summary-card">
        <strong>
            <?= $pending_applications; ?>
        </strong>
        <span>Pending</span>
    </div>

    <div class="summary-card">
        <strong>
            <?= $review_applications; ?>
        </strong>
        <span>Under Review</span>
    </div>

    <div class="summary-card">
        <strong>
            <?= $waitlisted_applications; ?>
        </strong>
        <span>Waitlisted</span>
    </div>

    <div class="summary-card">
        <strong>
            <?= $approved_applications; ?>
        </strong>
        <span>Approved</span>
    </div>

    <div class="summary-card">
        <strong>
            <?= $rejected_applications; ?>
        </strong>
        <span>Rejected</span>
    </div>

</div>

<div class="card filter-card">

    <h2 class="card-title">
        Filter Applications
    </h2>

    <div class="status-filters">

        <a
            href="my_applications.php"
            class="filter-link <?= $status_filter === ""
                ? "active"
                : ""; ?>"
        >
            All
        </a>

        <?php foreach (
            $allowed_statuses as $status
        ): ?>

            <a
                href="my_applications.php?status=<?= urlencode(
                    $status
                ); ?>"
                class="filter-link <?= $status_filter === $status
                    ? "active"
                    : ""; ?>"
            >
                <?= e($status); ?>
            </a>

        <?php endforeach; ?>

    </div>

</div>

<?php if (empty($applications)): ?>

    <div class="card empty-state">

        <h2>
            No applications found
        </h2>

        <p>
            You do not have any scholarship applications
            under this category yet.
        </p>

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
            $applications as $application
        ): ?>

            <?php
            $status =
                $application["status"];

            $status_class =
                application_status_class(
                    $status
                );

            $has_deadline =
                !empty(
                    $application["deadline"]
                );
            ?>

            <div
                class="card application-card <?= e(
                    $status_class
                ); ?>"
            >

                <div class="application-header">

                    <div>

                        <h2>
                            <?= e(
                                $application["title"]
                            ); ?>
                        </h2>

                        <p class="provider-name">
                            <?= e(
                                $application["provider"]
                            ); ?>
                        </p>

                    </div>

                    <span
                        class="badge badge-<?= e(
                            $status_class
                        ); ?>"
                    >
                        <?= e($status); ?>
                    </span>

                </div>

                <div class="application-meta">

                    <div class="meta-item">

                        <span>
                            Application Reference
                        </span>

                        <strong>
                            <?= e(
                                application_reference(
                                    $application["id"]
                                )
                            ); ?>
                        </strong>

                    </div>

                    <div class="meta-item">

                        <span>
                            Date Submitted
                        </span>

                        <strong>
                            <?= date(
                                "F d, Y",
                                strtotime(
                                    $application["submitted_at"]
                                )
                            ); ?>
                        </strong>

                    </div>

                    <div class="meta-item">

                        <span>
                            Category
                        </span>

                        <strong>
                            <?= !empty(
                                $application["category"]
                            )
                                ? e(
                                    $application["category"]
                                )
                                : "Not specified"; ?>
                        </strong>

                    </div>

                    <div class="meta-item">

                        <span>
                            Deadline
                        </span>

                        <strong>
                            <?= $has_deadline
                                ? date(
                                    "F d, Y",
                                    strtotime(
                                        $application["deadline"]
                                    )
                                )
                                : "To be announced"; ?>
                        </strong>

                    </div>

                </div>

                <?php if ($status === "Pending"): ?>

                    <div class="status-message message-pending">

                        Your application has been submitted and is
                        waiting to be opened by the administrator.

                    </div>

                <?php elseif ($status === "Under Review"): ?>

                    <div class="status-message message-under-review">

                        Your application is currently being reviewed.
                        The administrator may verify your profile,
                        documents, and eligibility information.

                    </div>

                <?php elseif ($status === "Waitlisted"): ?>

                    <div class="status-message message-waitlisted">

                        Your application is currently waitlisted.
                        This is not a rejection. Please wait for a
                        further update from the administrator.

                    </div>

                <?php elseif ($status === "Approved"): ?>

                    <div class="status-message message-approved">

                        Congratulations! Your application has been
                        approved in ScholarTrack. Review the admin
                        note and official provider instructions below.

                    </div>

                <?php elseif ($status === "Rejected"): ?>

                    <div class="status-message message-rejected">

                        Your application was not approved. Review the
                        admin note for available details, then browse
                        other scholarship opportunities.

                    </div>

                <?php endif; ?>

                <?php if (
                    !empty(
                        $application["admin_note"]
                    )
                ): ?>

                    <div class="admin-note">

                        <strong>
                            Admin Note
                        </strong>

                        <br>

                        <?= nl2br(
                            e(
                                $application["admin_note"]
                            )
                        ); ?>

                    </div>

                <?php endif; ?>

                <div class="application-actions">

                    <a
                        href="scholarship_view.php?id=<?= (int) $application["scholarship_id"]; ?>"
                        class="btn btn-primary"
                    >
                        View Scholarship
                    </a>

                    <?php if (
                        !empty(
                            $application["file_name"]
                        )
                    ): ?>

                        <a
                            href="uploads/<?= rawurlencode(
                                $application["file_name"]
                            ); ?>"
                            target="_blank"
                            class="btn btn-secondary"
                        >
                            View Uploaded Document
                        </a>

                    <?php endif; ?>

                    <?php if (
                        !empty(
                            $application["official_website"]
                        )
                    ): ?>

                        <a
                            href="<?= e(
                                $application["official_website"]
                            ); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-secondary"
                        >
                            Official Provider Website
                        </a>

                    <?php endif; ?>

                    <?php if (
                        $status === "Rejected"
                    ): ?>

                        <a
                            href="scholarships.php"
                            class="btn btn-gold"
                        >
                            Find Other Scholarships
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php include "footer.php"; ?>