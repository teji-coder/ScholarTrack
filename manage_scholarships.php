<?php
require_once "config.php";

require_admin();

$search = trim($_GET["search"] ?? "");
$category_filter = trim($_GET["category"] ?? "");
$region_filter = trim($_GET["region"] ?? "");
$status_filter = trim($_GET["status"] ?? "");

/*
|--------------------------------------------------------------------------
| Delete Scholarship
|--------------------------------------------------------------------------
*/

if (isset($_POST["delete_scholarship"])) {
    $scholarship_id = (int) ($_POST["scholarship_id"] ?? 0);

    if ($scholarship_id <= 0) {
        flash(
            "Invalid scholarship selected.",
            "error"
        );

        redirect("manage_scholarships.php");
    }

    /*
     * Check if the scholarship already has applications.
     * Scholarships with applications should not be deleted.
     */
    $application_check = $pdo->prepare(
        "SELECT COUNT(*)
         FROM applications
         WHERE scholarship_id = ?"
    );

    $application_check->execute([
        $scholarship_id
    ]);

    $application_count = (int) $application_check->fetchColumn();

    if ($application_count > 0) {
        flash(
            "This scholarship cannot be deleted because it already has student applications.",
            "error"
        );

        redirect("manage_scholarships.php");
    }

    try {
        $delete_statement = $pdo->prepare(
            "DELETE FROM scholarships
             WHERE id = ?"
        );

        $delete_statement->execute([
            $scholarship_id
        ]);

        flash(
            "Scholarship deleted successfully.",
            "success"
        );

    } catch (PDOException $e) {
        flash(
            "Unable to delete the scholarship.",
            "error"
        );
    }

    redirect("manage_scholarships.php");
}

/*
|--------------------------------------------------------------------------
| Scholarship Statistics
|--------------------------------------------------------------------------
*/

$total_scholarships = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM scholarships"
)->fetchColumn();

$active_scholarships = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM scholarships
     WHERE deadline IS NOT NULL
     AND deadline >= CURDATE()"
)->fetchColumn();

$expired_scholarships = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM scholarships
     WHERE deadline IS NOT NULL
     AND deadline < CURDATE()"
)->fetchColumn();

$without_deadline = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM scholarships
     WHERE deadline IS NULL"
)->fetchColumn();

/*
|--------------------------------------------------------------------------
| Category and Region Options
|--------------------------------------------------------------------------
*/

$category_statement = $pdo->query(
    "SELECT DISTINCT category
     FROM scholarships
     WHERE category IS NOT NULL
     AND category != ''
     ORDER BY category ASC"
);

$categories = $category_statement->fetchAll();

$region_statement = $pdo->query(
    "SELECT DISTINCT region
     FROM scholarships
     WHERE region IS NOT NULL
     AND region != ''
     ORDER BY region ASC"
);

$regions = $region_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Dynamic Scholarship Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        scholarships.*,
        COUNT(applications.id) AS application_count

    FROM scholarships

    LEFT JOIN applications
        ON applications.scholarship_id = scholarships.id

    WHERE 1 = 1
";

$params = [];

/* Search */
if ($search !== "") {
    $sql .= "
        AND (
            scholarships.title LIKE ?
            OR scholarships.provider LIKE ?
            OR scholarships.description LIKE ?
            OR scholarships.category LIKE ?
            OR scholarships.region LIKE ?
            OR scholarships.eligible_courses LIKE ?
        )
    ";

    $search_term = "%" . $search . "%";

    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

/* Category Filter */
if ($category_filter !== "") {
    $sql .= "
        AND scholarships.category = ?
    ";

    $params[] = $category_filter;
}

/* Region Filter */
if ($region_filter !== "") {
    $sql .= "
        AND scholarships.region = ?
    ";

    $params[] = $region_filter;
}

/* Deadline Status Filter */
if ($status_filter === "active") {
    $sql .= "
        AND scholarships.deadline IS NOT NULL
        AND scholarships.deadline >= CURDATE()
    ";
}

if ($status_filter === "expired") {
    $sql .= "
        AND scholarships.deadline IS NOT NULL
        AND scholarships.deadline < CURDATE()
    ";
}

if ($status_filter === "no_deadline") {
    $sql .= "
        AND scholarships.deadline IS NULL
    ";
}

$sql .= "
    GROUP BY scholarships.id
    ORDER BY scholarships.created_at DESC
";

$scholarship_statement = $pdo->prepare($sql);

$scholarship_statement->execute($params);

$scholarships = $scholarship_statement->fetchAll();

$page_title = "Manage Scholarships";

include "header.php";
?>

<style>
    .manage-hero {
        background:
            linear-gradient(
                135deg,
                #283F24,
                #467235
            );
        color: #FFFFFF;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 25px;
    }

    .manage-hero h1 {
        color: #FFBF00;
        margin-bottom: 8px;
    }

    .manage-hero p {
        color: rgba(255, 255, 255, 0.86);
        line-height: 1.6;
        max-width: 700px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns:
            2fr
            1fr
            1fr
            1fr
            auto;
        gap: 12px;
        align-items: end;
    }

    .filter-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .scholarship-list {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .scholarship-admin-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        border-top: 5px solid #467235;
        transition: transform 0.2s;
    }

    .scholarship-admin-card:hover {
        transform: translateY(-3px);
    }

    .scholarship-card-header {
        display: flex;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 15px;
    }

    .scholarship-card-header h2 {
        color: #283F24;
        font-size: 20px;
        line-height: 1.4;
        margin-bottom: 7px;
    }

    .provider-name {
        color: #467235;
        font-weight: bold;
    }

    .scholarship-labels {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-bottom: 15px;
    }

    .information-label {
        display: inline-block;
        background: #EEF2EC;
        color: #40523A;
        border-radius: 20px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: bold;
    }

    .status-active {
        background: #DCEFD6;
        color: #28651F;
    }

    .status-expired {
        background: #F5D4D1;
        color: #9D221A;
    }

    .status-no-deadline {
        background: #FFF2BD;
        color: #775800;
    }

    .scholarship-description {
        color: #6B7280;
        line-height: 1.65;
        margin-bottom: 16px;
        flex-grow: 1;
    }

    .scholarship-details-grid {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 10px;
        background: #F7F9F6;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 16px;
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
        font-size: 14px;
        word-break: break-word;
    }

    .benefit-preview {
        background: #FFF9DB;
        border: 1px solid #EAD47D;
        border-radius: 9px;
        padding: 14px;
        margin-bottom: 16px;
        color: #614B00;
        line-height: 1.6;
        font-size: 13px;
    }

    .scholarship-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        border-top: 1px solid #DDE2D9;
        padding-top: 16px;
    }

    .inline-form {
        display: inline;
    }

    .application-count {
        min-width: 58px;
        height: 58px;
        border-radius: 12px;
        background: #FFF78D;
        color: #283F24;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        flex-shrink: 0;
    }

    .application-count strong {
        font-size: 21px;
    }

    @media (max-width: 1100px) {
        .filter-grid {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 850px) {
        .scholarship-list {
            grid-template-columns: 1fr;
        }

        .manage-hero {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 650px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }

        .scholarship-details-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="manage-hero">

    <div>
        <h1>Manage Scholarships</h1>

        <p>
            Add verified scholarship information, update benefits
            and requirements, review deadlines, and manage programs
            displayed to students.
        </p>
    </div>

    <a
        href="scholarship_form.php"
        class="btn btn-gold"
    >
        Add New Scholarship
    </a>

</div>

<div class="grid grid-4">

    <div class="stat-card highlight">
        <h3>Total Scholarships</h3>

        <div class="number">
            <?= $total_scholarships; ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Active Scholarships</h3>

        <div class="number">
            <?= $active_scholarships; ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Expired Scholarships</h3>

        <div class="number">
            <?= $expired_scholarships; ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Without Deadline</h3>

        <div class="number">
            <?= $without_deadline; ?>
        </div>
    </div>

</div>

<br>

<div class="card">

    <h2 class="card-title">
        Search and Filter
    </h2>

    <form
        method="GET"
        action="manage_scholarships.php"
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
                    placeholder="Title, provider, course, category, or region"
                >
            </div>

            <div class="form-group">
                <label for="category">
                    Category
                </label>

                <select
                    id="category"
                    name="category"
                    class="form-control"
                >
                    <option value="">
                        All Categories
                    </option>

                    <?php foreach ($categories as $category): ?>

                        <option
                            value="<?= e($category["category"]); ?>"
                            <?= $category_filter ===
                                $category["category"]
                                ? "selected"
                                : ""; ?>
                        >
                            <?= e($category["category"]); ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label for="region">
                    Region
                </label>

                <select
                    id="region"
                    name="region"
                    class="form-control"
                >
                    <option value="">
                        All Regions
                    </option>

                    <?php foreach ($regions as $region): ?>

                        <option
                            value="<?= e($region["region"]); ?>"
                            <?= $region_filter ===
                                $region["region"]
                                ? "selected"
                                : ""; ?>
                        >
                            <?= e($region["region"]); ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label for="status">
                    Deadline Status
                </label>

                <select
                    id="status"
                    name="status"
                    class="form-control"
                >
                    <option value="">
                        All Statuses
                    </option>

                    <option
                        value="active"
                        <?= $status_filter === "active"
                            ? "selected"
                            : ""; ?>
                    >
                        Active
                    </option>

                    <option
                        value="expired"
                        <?= $status_filter === "expired"
                            ? "selected"
                            : ""; ?>
                    >
                        Expired
                    </option>

                    <option
                        value="no_deadline"
                        <?= $status_filter === "no_deadline"
                            ? "selected"
                            : ""; ?>
                    >
                        No Deadline
                    </option>
                </select>
            </div>

            <div class="filter-buttons">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Apply Filter
                </button>

                <a
                    href="manage_scholarships.php"
                    class="btn btn-secondary"
                >
                    Reset
                </a>

            </div>

        </div>

    </form>

</div>

<p style="color:#6B7280; margin-bottom:18px;">

    <?= count($scholarships); ?>

    scholarship<?= count($scholarships) === 1
        ? ""
        : "s"; ?>

    found.

</p>

<?php if (empty($scholarships)): ?>

    <div class="card">

        <p class="empty-message">
            No scholarships match the selected filters.
        </p>

        <div style="text-align:center;">

            <a
                href="scholarship_form.php"
                class="btn btn-primary"
            >
                Add Scholarship
            </a>

        </div>

    </div>

<?php else: ?>

    <div class="scholarship-list">

        <?php foreach ($scholarships as $scholarship): ?>

            <?php
            $has_deadline =
                !empty($scholarship["deadline"]);

            $is_expired = false;

            if ($has_deadline) {
                $is_expired =
                    strtotime($scholarship["deadline"]) <
                    strtotime(date("Y-m-d"));
            }

            if (!$has_deadline) {
                $deadline_class =
                    "status-no-deadline";

                $deadline_status =
                    "Deadline Not Set";
            } elseif ($is_expired) {
                $deadline_class =
                    "status-expired";

                $deadline_status =
                    "Expired";
            } else {
                $deadline_class =
                    "status-active";

                $deadline_status =
                    "Active";
            }
            ?>

            <div class="card scholarship-admin-card">

                <div class="scholarship-card-header">

                    <div>

                        <h2>
                            <?= e($scholarship["title"]); ?>
                        </h2>

                        <p class="provider-name">
                            <?= e($scholarship["provider"]); ?>
                        </p>

                    </div>

                    <div class="application-count">

                        <strong>
                            <?= (int) $scholarship["application_count"]; ?>
                        </strong>

                        <span>
                            Applications
                        </span>

                    </div>

                </div>

                <div class="scholarship-labels">

                    <?php if (!empty($scholarship["category"])): ?>

                        <span class="information-label">
                            <?= e($scholarship["category"]); ?>
                        </span>

                    <?php endif; ?>

                    <?php if (!empty($scholarship["region"])): ?>

                        <span class="information-label">
                            <?= e($scholarship["region"]); ?>
                        </span>

                    <?php endif; ?>

                    <span
                        class="information-label <?= $deadline_class; ?>"
                    >
                        <?= e($deadline_status); ?>
                    </span>

                </div>

                <p class="scholarship-description">

                    <?php
                    $description =
                        $scholarship["description"] ??
                        "";

                    if (strlen($description) > 220) {
                        echo e(
                            substr(
                                $description,
                                0,
                                220
                            )
                        ) . "...";
                    } else {
                        echo e($description);
                    }
                    ?>

                </p>

                <div class="scholarship-details-grid">

                    <div class="detail-item">
                        <span>Required GWA</span>

                        <strong>
                            <?= $scholarship["minimum_gwa"] !== null
                                ? e(
                                    (string)
                                    $scholarship["minimum_gwa"]
                                ) . " or better"
                                : "Not specified"; ?>
                        </strong>
                    </div>

                    <div class="detail-item">
                        <span>Maximum Income</span>

                        <strong>
                            <?= $scholarship["max_income"] !== null
                                ? "₱" .
                                    number_format(
                                        (float)
                                        $scholarship["max_income"],
                                        2
                                    )
                                : "Not specified"; ?>
                        </strong>
                    </div>

                    <div class="detail-item">
                        <span>Monthly Allowance</span>

                        <strong>
                            <?= $scholarship["monthly_allowance"] !== null
                                ? "₱" .
                                    number_format(
                                        (float)
                                        $scholarship["monthly_allowance"],
                                        2
                                    )
                                : "Not specified"; ?>
                        </strong>
                    </div>

                    <div class="detail-item">
                        <span>Deadline</span>

                        <strong>
                            <?= $has_deadline
                                ? date(
                                    "F d, Y",
                                    strtotime(
                                        $scholarship["deadline"]
                                    )
                                )
                                : "To be announced"; ?>
                        </strong>
                    </div>

                </div>

                <?php if (!empty($scholarship["benefits"])): ?>

                    <div class="benefit-preview">

                        <strong>Benefits:</strong>

                        <br>

                        <?php
                        $benefits =
                            $scholarship["benefits"];

                        if (strlen($benefits) > 180) {
                            echo e(
                                substr(
                                    $benefits,
                                    0,
                                    180
                                )
                            ) . "...";
                        } else {
                            echo nl2br(
                                e($benefits)
                            );
                        }
                        ?>

                    </div>

                <?php endif; ?>

                <div class="scholarship-actions">

                    <a
                        href="scholarship_form.php?id=<?= (int) $scholarship["id"]; ?>"
                        class="btn btn-primary"
                    >
                        Edit
                    </a>

                    <a
                        href="scholarship_view.php?id=<?= (int) $scholarship["id"]; ?>"
                        class="btn btn-secondary"
                        target="_blank"
                    >
                        Preview
                    </a>

                    <?php if (
                        (int) $scholarship["application_count"] > 0
                    ): ?>

                        <a
                            href="manage_applications.php?scholarship_id=<?= (int) $scholarship["id"]; ?>"
                            class="btn btn-secondary"
                        >
                            View Applications
                        </a>

                    <?php else: ?>

                        <form
                            method="POST"
                            action="manage_scholarships.php"
                            class="inline-form"
                            onsubmit="return confirm('Delete this scholarship permanently?');"
                        >

                            <input
                                type="hidden"
                                name="scholarship_id"
                                value="<?= (int) $scholarship["id"]; ?>"
                            >

                            <button
                                type="submit"
                                name="delete_scholarship"
                                class="btn btn-danger"
                            >
                                Delete
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php include "footer.php"; ?>