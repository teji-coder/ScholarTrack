<?php
require_once "config.php";

require_admin();

$allowed_statuses = [
    "Pending",
    "Under Review",
    "Waitlisted",
    "Approved",
    "Rejected"
];

$search = trim($_GET["search"] ?? "");
$status_filter = trim($_GET["status"] ?? "");
$scholarship_filter = isset($_GET["scholarship_id"])
    ? (int) $_GET["scholarship_id"]
    : 0;

/*
|--------------------------------------------------------------------------
| Update Application Status
|--------------------------------------------------------------------------
*/

if (isset($_POST["update_status"])) {
    $application_id = (int) ($_POST["application_id"] ?? 0);
    $new_status = trim($_POST["new_status"] ?? "");
    $admin_note = trim($_POST["admin_note"] ?? "");

    if ($application_id <= 0) {
        flash(
            "Invalid application selected.",
            "error"
        );

        redirect("manage_applications.php");
    }

    if (!in_array($new_status, $allowed_statuses, true)) {
        flash(
            "Invalid application status.",
            "error"
        );

        redirect("manage_applications.php");
    }

    $application_statement = $pdo->prepare(
        "SELECT
            applications.id,
            applications.student_id,
            applications.status,
            scholarships.title
         FROM applications
         INNER JOIN scholarships
            ON scholarships.id = applications.scholarship_id
         WHERE applications.id = ?
         LIMIT 1"
    );

    $application_statement->execute([
        $application_id
    ]);

    $application = $application_statement->fetch();

    if (!$application) {
        flash(
            "Application record was not found.",
            "error"
        );

        redirect("manage_applications.php");
    }

    try {
        $pdo->beginTransaction();

        $update_statement = $pdo->prepare(
            "UPDATE applications
             SET
                status = ?,
                admin_note = ?
             WHERE id = ?"
        );

        $update_statement->execute([
            $new_status,
            $admin_note,
            $application_id
        ]);

        if ($new_status === "Approved") {
            $notification_message =
                'Congratulations! Your application for "' .
                $application["title"] .
                '" has been approved.';
        } elseif ($new_status === "Rejected") {
            $notification_message =
                'Your application for "' .
                $application["title"] .
                '" has been rejected.';
        } elseif ($new_status === "Waitlisted") {
            $notification_message =
                'Your application for "' .
                $application["title"] .
                '" has been placed on the waitlist.';
        } elseif ($new_status === "Under Review") {
            $notification_message =
                'Your application for "' .
                $application["title"] .
                '" is now under review.';
        } else {
            $notification_message =
                'Your application for "' .
                $application["title"] .
                '" is currently pending.';
        }

        if ($admin_note !== "") {
            $notification_message .=
                " Admin note: " .
                $admin_note;
        }

        $notification_statement = $pdo->prepare(
            "INSERT INTO notifications
            (
                user_id,
                message,
                is_read
            )
            VALUES (?, ?, 0)"
        );

        $notification_statement->execute([
            $application["student_id"],
            $notification_message
        ]);

        $pdo->commit();

        flash(
            "Application status updated successfully.",
            "success"
        );

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash(
            "Unable to update the application. Make sure the admin_note column exists.",
            "error"
        );
    }

    redirect("manage_applications.php");
}

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Scholarship Filter Options
|--------------------------------------------------------------------------
*/

$scholarship_options = $pdo->query(
    "SELECT id, title
     FROM scholarships
     ORDER BY title ASC"
)->fetchAll();

/*
|--------------------------------------------------------------------------
| Application Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        applications.id,
        applications.student_id,
        applications.status,
        applications.admin_note,
        applications.submitted_at,

        users.fullname,
        users.email,

        student_profiles.student_no,
        student_profiles.school,
        student_profiles.course,
        student_profiles.other_course,
        student_profiles.year_level,
        student_profiles.gwa,
        student_profiles.annual_income,
        student_profiles.contact_number,
        student_profiles.municipality,
        student_profiles.province,
        student_profiles.profile_picture,

        scholarships.id AS scholarship_id,
        scholarships.title,
        scholarships.provider,
        scholarships.minimum_gwa,
        scholarships.max_income,
        scholarships.deadline,

        documents.file_name

    FROM applications

    INNER JOIN users
        ON users.id = applications.student_id

    LEFT JOIN student_profiles
        ON student_profiles.user_id = users.id

    INNER JOIN scholarships
        ON scholarships.id = applications.scholarship_id

    LEFT JOIN documents
        ON documents.application_id = applications.id

    WHERE 1 = 1
";

$params = [];

if ($status_filter !== "") {
    if (in_array($status_filter, $allowed_statuses, true)) {
        $sql .= "
            AND applications.status = ?
        ";

        $params[] = $status_filter;
    }
}

if ($scholarship_filter > 0) {
    $sql .= "
        AND scholarships.id = ?
    ";

    $params[] = $scholarship_filter;
}

if ($search !== "") {
    $sql .= "
        AND (
            users.fullname LIKE ?
            OR users.email LIKE ?
            OR student_profiles.student_no LIKE ?
            OR student_profiles.course LIKE ?
            OR student_profiles.other_course LIKE ?
            OR scholarships.title LIKE ?
            OR scholarships.provider LIKE ?
        )
    ";

    $search_term = "%" . $search . "%";

    for ($i = 0; $i < 7; $i++) {
        $params[] = $search_term;
    }
}

$sql .= "
    ORDER BY
        CASE applications.status
            WHEN 'Pending' THEN 1
            WHEN 'Under Review' THEN 2
            WHEN 'Waitlisted' THEN 3
            WHEN 'Approved' THEN 4
            WHEN 'Rejected' THEN 5
            ELSE 6
        END,
        applications.submitted_at DESC
";

$application_statement =
    $pdo->prepare($sql);

$application_statement->execute($params);

$applications =
    $application_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Helper
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

$page_title =
    "Manage Applications";

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
        padding: 30px;
        border-radius: 18px;
        margin-bottom: 24px;
        box-shadow:
            0 10px 28px
            rgba(40, 63, 36, 0.15);
    }

    .applications-hero h1 {
        color: #FFBF00;
        margin-bottom: 8px;
    }

    .applications-hero p {
        color:
            rgba(255, 255, 255, 0.86);
        line-height: 1.7;
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

    .summary-card strong {
        display: block;
        font-size: 28px;
        margin-bottom: 5px;
        color: #283F24;
    }

    .summary-card span {
        color: #6B7280;
        font-size: 12px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns:
            2fr 1fr 1fr auto;
        gap: 12px;
        align-items: end;
    }

    .filter-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
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
        margin-bottom: 6px;
    }

    .provider-name {
        color: #467235;
        font-weight: bold;
    }

    .applicant-layout {
        display: grid;
        grid-template-columns:
            90px 1fr;
        gap: 16px;
        align-items: start;
        margin-bottom: 18px;
    }

    .applicant-photo,
    .applicant-avatar {
        width: 82px;
        height: 82px;
        border-radius: 50%;
        border: 3px solid #FFBF00;
        background: #F7F9F6;
        object-fit: cover;
    }

    .applicant-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #467235;
        font-size: 30px;
        font-weight: bold;
    }

    .applicant-grid {
        display: grid;
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .detail-item {
        background: #F7F9F6;
        border-radius: 9px;
        padding: 12px;
    }

    .detail-item span {
        display: block;
        color: #6B7280;
        font-size: 11px;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .detail-item strong {
        color: #283F24;
        font-size: 13px;
        word-break: break-word;
    }

    .eligibility-panel {
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 18px;
        line-height: 1.7;
    }

    .eligible {
        background: #E4F2DF;
        border: 1px solid #A9D49B;
        color: #24551C;
    }

    .not-eligible {
        background: #FCE7E5;
        border: 1px solid #E7AAA5;
        color: #8E201A;
    }

    .document-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .review-form {
        background: #F7F9F6;
        border-radius: 12px;
        padding: 18px;
    }

    .review-grid {
        display: grid;
        grid-template-columns:
            1fr 2fr;
        gap: 14px;
        align-items: start;
    }

    .review-form textarea {
        min-height: 95px;
    }

    .review-actions {
        margin-top: 12px;
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
    }

    @media (max-width: 1200px) {
        .status-summary {
            grid-template-columns:
                repeat(3, minmax(0, 1fr));
        }

        .applicant-grid {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 850px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }

        .review-grid {
            grid-template-columns: 1fr;
        }

        .application-header {
            flex-direction: column;
        }
    }

    @media (max-width: 650px) {
        .status-summary {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .applicant-layout {
            grid-template-columns: 1fr;
        }

        .applicant-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="applications-hero">

    <h1>
        Manage Applications
    </h1>

    <p>
        Review student submissions, inspect profile
        information and uploaded documents, then update
        the application status and leave an admin note.
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

<div class="card">

    <h2 class="card-title">
        Search and Filter
    </h2>

    <form
        method="GET"
        action="manage_applications.php"
    >

        <div class="filter-grid">

            <div class="form-group">

                <label for="search">
                    Search
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    value="<?= e($search); ?>"
                    placeholder="Student, email, student number, course, or scholarship"
                >

            </div>

            <div class="form-group">

                <label for="status">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                    class="form-control"
                >
                    <option value="">
                        All Statuses
                    </option>

                    <?php foreach (
                        $allowed_statuses as $status
                    ): ?>

                        <option
                            value="<?= e($status); ?>"
                            <?= $status_filter === $status
                                ? "selected"
                                : ""; ?>
                        >
                            <?= e($status); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label for="scholarship_id">
                    Scholarship
                </label>

                <select
                    id="scholarship_id"
                    name="scholarship_id"
                    class="form-control"
                >
                    <option value="0">
                        All Scholarships
                    </option>

                    <?php foreach (
                        $scholarship_options
                        as $scholarship_option
                    ): ?>

                        <option
                            value="<?= (int) $scholarship_option["id"]; ?>"
                            <?= $scholarship_filter ===
                                (int) $scholarship_option["id"]
                                ? "selected"
                                : ""; ?>
                        >
                            <?= e(
                                $scholarship_option["title"]
                            ); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="filter-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Apply Filter
                </button>

                <a
                    href="manage_applications.php"
                    class="btn btn-secondary"
                >
                    Reset
                </a>

            </div>

        </div>

    </form>

</div>

<p style="color:#6B7280;margin-bottom:18px;">

    <strong>
        <?= count($applications); ?>
    </strong>

    application<?= count($applications) === 1
        ? ""
        : "s"; ?>

    found.

</p>

<?php if (empty($applications)): ?>

    <div class="card">

        <p class="empty-message">
            No applications match the selected filters.
        </p>

    </div>

<?php else: ?>

    <div class="application-list">

        <?php foreach (
            $applications as $application
        ): ?>

            <?php
            $status_class =
                application_status_class(
                    $application["status"]
                );

            $student_course =
                $application["course"] ?? "";

            if (
                $student_course === "Other" &&
                !empty(
                    $application["other_course"]
                )
            ) {
                $student_course =
                    $application["other_course"];
            }

            $has_profile_data =
                $application["gwa"] !== null &&
                $application["gwa"] !== "" &&
                $application["annual_income"] !== null &&
                $application["annual_income"] !== "";

            $meets_gwa = true;
            $meets_income = true;

            if (
                $application["minimum_gwa"] !== null &&
                $application["minimum_gwa"] !== ""
            ) {
                $meets_gwa =
                    $application["gwa"] !== null &&
                    $application["gwa"] !== "" &&
                    (float) $application["gwa"] <=
                    (float) $application["minimum_gwa"];
            }

            if (
                $application["max_income"] !== null &&
                $application["max_income"] !== ""
            ) {
                $meets_income =
                    $application["annual_income"] !== null &&
                    $application["annual_income"] !== "" &&
                    (float) $application["annual_income"] <=
                    (float) $application["max_income"];
            }

            $meets_basic_requirements =
                $has_profile_data &&
                $meets_gwa &&
                $meets_income;

            $profile_picture_name =
                basename(
                    (string) (
                        $application["profile_picture"] ?? ""
                    )
                );

            $profile_picture_url = "";

            if (
                $profile_picture_name !== "" &&
                $profile_picture_name !== "default.png"
            ) {
                $exact_picture_path =
                    __DIR__ .
                    "/profile/" .
                    $profile_picture_name;

                if (is_file($exact_picture_path)) {
                    $profile_picture_url =
                        "profile/" .
                        rawurlencode($profile_picture_name);
                }
            }

            if ($profile_picture_url === "") {
                $student_id_for_picture =
                    (int) ($application["student_id"] ?? 0);

                $picture_matches = [];

                foreach (["jpg", "jpeg", "png"] as $picture_extension) {
                    $matches = glob(
                        __DIR__ .
                        "/profile/profile_" .
                        $student_id_for_picture .
                        "_*." .
                        $picture_extension
                    );

                    if (is_array($matches)) {
                        $picture_matches = array_merge(
                            $picture_matches,
                            $matches
                        );
                    }
                }

                if (!empty($picture_matches)) {
                    usort(
                        $picture_matches,
                        function ($first, $second) {
                            return filemtime($second) <=>
                                filemtime($first);
                        }
                    );

                    $matched_picture_name =
                        basename($picture_matches[0]);

                    $profile_picture_url =
                        "profile/" .
                        rawurlencode($matched_picture_name);
                }
            }

            $initial = strtoupper(
                substr(
                    $application["fullname"],
                    0,
                    1
                )
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
                        <?= e(
                            $application["status"]
                        ); ?>
                    </span>

                </div>

                <div class="applicant-layout">

                    <div>

                        <?php if ($profile_picture_url !== ""): ?>

                            <img
                                src="<?= e(
                                    $profile_picture_url
                                ); ?>?v=<?= time(); ?>"
                                alt="Student Profile"
                                class="applicant-photo"
                                onerror="
                                    this.style.display='none';
                                    this.nextElementSibling.style.display='flex';
                                "
                            >

                            <div
                                class="applicant-avatar"
                                style="display:none;"
                            >
                                <?= e($initial); ?>
                            </div>

                        <?php else: ?>

                            <div class="applicant-avatar">
                                <?= e($initial); ?>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="applicant-grid">

                        <div class="detail-item">
                            <span>Applicant</span>
                            <strong>
                                <?= e(
                                    $application["fullname"]
                                ); ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Email</span>
                            <strong>
                                <?= e(
                                    $application["email"]
                                ); ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Student Number</span>
                            <strong>
                                <?= !empty(
                                    $application["student_no"]
                                )
                                    ? e(
                                        $application["student_no"]
                                    )
                                    : "Not provided"; ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>School</span>
                            <strong>
                                <?= !empty(
                                    $application["school"]
                                )
                                    ? e(
                                        $application["school"]
                                    )
                                    : "Not provided"; ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Course</span>
                            <strong>
                                <?= $student_course !== ""
                                    ? e($student_course)
                                    : "Not provided"; ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Year Level</span>
                            <strong>
                                <?= !empty(
                                    $application["year_level"]
                                )
                                    ? e(
                                        $application["year_level"]
                                    )
                                    : "Not provided"; ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Contact Number</span>
                            <strong>
                                <?= !empty(
                                    $application["contact_number"]
                                )
                                    ? e(
                                        $application["contact_number"]
                                    )
                                    : "Not provided"; ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Address</span>
                            <strong>
                                <?= e(
                                    trim(
                                        ($application["municipality"] ?? "") .
                                        ", " .
                                        ($application["province"] ?? ""),
                                        ", "
                                    )
                                ) ?: "Not provided"; ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Student GWA</span>
                            <strong>
                                <?= $application["gwa"] !== null
                                    ? e(
                                        (string)
                                        $application["gwa"]
                                    )
                                    : "Not provided"; ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Required GWA</span>
                            <strong>
                                <?= $application["minimum_gwa"] !== null
                                    ? e(
                                        (string)
                                        $application["minimum_gwa"]
                                    ) . " or better"
                                    : "Not specified"; ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Annual Family Income</span>
                            <strong>
                                <?= $application["annual_income"] !== null
                                    ? "₱" .
                                        number_format(
                                            (float)
                                            $application["annual_income"],
                                            2
                                        )
                                    : "Not provided"; ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Maximum Income</span>
                            <strong>
                                <?= $application["max_income"] !== null
                                    ? "₱" .
                                        number_format(
                                            (float)
                                            $application["max_income"],
                                            2
                                        )
                                    : "Not specified"; ?>
                            </strong>
                        </div>

                        <div class="detail-item">
                            <span>Date Submitted</span>
                            <strong>
                                <?= date(
                                    "F d, Y",
                                    strtotime(
                                        $application["submitted_at"]
                                    )
                                ); ?>
                            </strong>
                        </div>

                    </div>

                </div>

                <?php if (
                    $meets_basic_requirements
                ): ?>

                    <div class="eligibility-panel eligible">

                        <strong>
                            Basic Eligibility Check:
                        </strong>

                        The student's GWA and annual
                        family income meet the saved
                        numeric requirements.

                    </div>

                <?php else: ?>

                    <div class="eligibility-panel not-eligible">

                        <strong>
                            Basic Eligibility Check:
                        </strong>

                        The student's profile is incomplete
                        or does not meet one or more saved
                        numeric requirements.

                    </div>

                <?php endif; ?>

                <div class="document-row">

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

                    <?php else: ?>

                        <span class="btn btn-secondary">
                            No Document Uploaded
                        </span>

                    <?php endif; ?>

                    <a
                        href="scholarship_view.php?id=<?= (int) $application["scholarship_id"]; ?>"
                        target="_blank"
                        class="btn btn-secondary"
                    >
                        View Scholarship
                    </a>

                </div>

                <form
                    method="POST"
                    action="manage_applications.php"
                    class="review-form"
                >

                    <input
                        type="hidden"
                        name="application_id"
                        value="<?= (int) $application["id"]; ?>"
                    >

                    <div class="review-grid">

                        <div class="form-group">

                            <label>
                                Application Status
                            </label>

                            <select
                                name="new_status"
                                class="form-control"
                                required
                            >

                                <?php foreach (
                                    $allowed_statuses
                                    as $status
                                ): ?>

                                    <option
                                        value="<?= e(
                                            $status
                                        ); ?>"
                                        <?= $application["status"] ===
                                            $status
                                            ? "selected"
                                            : ""; ?>
                                    >
                                        <?= e($status); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="form-group">

                            <label>
                                Admin Note
                            </label>

                            <textarea
                                name="admin_note"
                                class="form-control"
                                placeholder="Add a note, missing requirement, review result, or next instruction for the student."
                            ><?= e(
                                $application["admin_note"]
                                ?? ""
                            ); ?></textarea>

                        </div>

                    </div>

                    <div class="review-actions">

                        <button
                            type="submit"
                            name="update_status"
                            class="btn btn-primary"
                            onclick="return confirm('Update this application status?');"
                        >
                            Save Review
                        </button>

                    </div>

                </form>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php include "footer.php"; ?>