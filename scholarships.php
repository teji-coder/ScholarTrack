<?php
require_once "config.php";

require_student();

$user_id = (int) $_SESSION["user"]["id"];

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$provider_filter = trim($_GET["provider"] ?? "");
$category_filter = trim($_GET["category"] ?? "");
$region_filter = trim($_GET["region"] ?? "");
$course_filter = trim($_GET["course"] ?? "");
$deadline_filter = trim($_GET["deadline"] ?? "");
$eligibility_filter = trim($_GET["eligibility"] ?? "");
$minimum_allowance = trim($_GET["minimum_allowance"] ?? "");

/*
|--------------------------------------------------------------------------
| Student Profile
|--------------------------------------------------------------------------
*/

$profile_statement = $pdo->prepare(
    "SELECT
        school,
        course,
        other_course,
        year_level,
        gwa,
        annual_income
     FROM student_profiles
     WHERE user_id = ?
     LIMIT 1"
);

$profile_statement->execute([
    $user_id
]);

$profile = $profile_statement->fetch();

$student_course = "";

if ($profile) {
    if (
        ($profile["course"] ?? "") === "Other" &&
        !empty($profile["other_course"])
    ) {
        $student_course = $profile["other_course"];
    } else {
        $student_course = $profile["course"] ?? "";
    }
}

$student_gwa =
    $profile["gwa"] ?? null;

$student_income =
    $profile["annual_income"] ?? null;

$has_eligibility_data =
    $student_gwa !== null &&
    $student_gwa !== "" &&
    $student_income !== null &&
    $student_income !== "";

/*
|--------------------------------------------------------------------------
| Filter Options
|--------------------------------------------------------------------------
*/

$providers = $pdo->query(
    "SELECT DISTINCT provider
     FROM scholarships
     WHERE provider IS NOT NULL
     AND provider != ''
     ORDER BY provider ASC"
)->fetchAll();

$categories = $pdo->query(
    "SELECT DISTINCT category
     FROM scholarships
     WHERE category IS NOT NULL
     AND category != ''
     ORDER BY category ASC"
)->fetchAll();

$regions = $pdo->query(
    "SELECT DISTINCT region
     FROM scholarships
     WHERE region IS NOT NULL
     AND region != ''
     ORDER BY region ASC"
)->fetchAll();

$course_options = [
    "All Courses",
    "BS Information Technology",
    "BS Computer Science",
    "BS Information Systems",
    "BS Civil Engineering",
    "BS Mechanical Engineering",
    "BS Electrical Engineering",
    "BS Electronics Engineering",
    "BS Accountancy",
    "BS Business Administration",
    "BS Entrepreneurship",
    "BS Nursing",
    "BS Medical Technology",
    "BS Pharmacy",
    "BS Psychology",
    "BS Architecture",
    "BS Education",
    "BS Political Science",
    "BA Communication",
    "BA English Language",
    "BS Hospitality Management",
    "BS Tourism Management"
];

/*
|--------------------------------------------------------------------------
| Scholarship Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        scholarships.*,

        CASE
            WHEN applications.id IS NOT NULL
            THEN 1
            ELSE 0
        END AS already_applied,

        applications.status AS application_status

    FROM scholarships

    LEFT JOIN applications
        ON applications.scholarship_id = scholarships.id
        AND applications.student_id = ?

    WHERE 1 = 1
";

$params = [
    $user_id
];

/* Search */
if ($search !== "") {
    $sql .= "
        AND (
            scholarships.title LIKE ?
            OR scholarships.provider LIKE ?
            OR scholarships.description LIKE ?
            OR scholarships.benefits LIKE ?
            OR scholarships.eligible_courses LIKE ?
            OR scholarships.category LIKE ?
            OR scholarships.region LIKE ?
        )
    ";

    $search_term = "%" . $search . "%";

    for ($i = 0; $i < 7; $i++) {
        $params[] = $search_term;
    }
}

/* Provider */
if ($provider_filter !== "") {
    $sql .= "
        AND scholarships.provider = ?
    ";

    $params[] = $provider_filter;
}

/* Category */
if ($category_filter !== "") {
    $sql .= "
        AND scholarships.category = ?
    ";

    $params[] = $category_filter;
}

/* Region */
if ($region_filter !== "") {
    $sql .= "
        AND scholarships.region = ?
    ";

    $params[] = $region_filter;
}

/* Course */
if (
    $course_filter !== "" &&
    $course_filter !== "All Courses"
) {
    $sql .= "
        AND (
            scholarships.eligible_courses IS NULL
            OR scholarships.eligible_courses = ''
            OR scholarships.eligible_courses LIKE '%All Courses%'
            OR scholarships.eligible_courses LIKE ?
        )
    ";

    $params[] =
        "%" . $course_filter . "%";
}

/* Deadline */
if ($deadline_filter === "open") {
    $sql .= "
        AND scholarships.deadline IS NOT NULL
        AND scholarships.deadline >= CURDATE()
    ";
}

if ($deadline_filter === "closing_soon") {
    $sql .= "
        AND scholarships.deadline IS NOT NULL
        AND scholarships.deadline BETWEEN
            CURDATE()
            AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ";
}

if ($deadline_filter === "no_deadline") {
    $sql .= "
        AND scholarships.deadline IS NULL
    ";
}

/* Minimum allowance */
if (
    $minimum_allowance !== "" &&
    is_numeric($minimum_allowance)
) {
    $sql .= "
        AND scholarships.monthly_allowance IS NOT NULL
        AND scholarships.monthly_allowance >= ?
    ";

    $params[] =
        (float) $minimum_allowance;
}

/*
|--------------------------------------------------------------------------
| Eligibility Filter
|--------------------------------------------------------------------------
|
| A null GWA or income requirement means the provider did not set
| a numeric requirement in the system.
|
*/

if (
    $eligibility_filter === "qualified" &&
    $has_eligibility_data
) {
    $sql .= "
        AND (
            scholarships.minimum_gwa IS NULL
            OR scholarships.minimum_gwa = ''
            OR ? <= scholarships.minimum_gwa
        )

        AND (
            scholarships.max_income IS NULL
            OR scholarships.max_income = ''
            OR ? <= scholarships.max_income
        )
    ";

    $params[] =
        (float) $student_gwa;

    $params[] =
        (float) $student_income;
}

$sql .= "
    ORDER BY
        CASE
            WHEN scholarships.deadline IS NULL
            THEN 1
            ELSE 0
        END,
        scholarships.deadline ASC,
        scholarships.created_at DESC
";

$scholarship_statement =
    $pdo->prepare($sql);

$scholarship_statement->execute($params);

$scholarships =
    $scholarship_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function scholarship_money($value)
{
    if ($value === null || $value === "") {
        return "Not specified";
    }

    return "₱" . number_format(
        (float) $value,
        2
    );
}

function status_badge_class($status)
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
    "Scholarships";

include "header.php";
?>

<style>
    .scholarship-page-hero {
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

    .scholarship-page-hero h1 {
        color: #FFBF00;
        margin-bottom: 9px;
        font-size: 33px;
    }

    .scholarship-page-hero p {
        color:
            rgba(255, 255, 255, 0.86);
        line-height: 1.7;
        max-width: 850px;
    }

    .profile-summary {
        display: grid;
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 22px;
    }

    .profile-summary-item {
        background:
            rgba(255, 255, 255, 0.11);
        border:
            1px solid
            rgba(255, 255, 255, 0.16);
        border-radius: 10px;
        padding: 13px;
    }

    .profile-summary-item span {
        display: block;
        font-size: 11px;
        color:
            rgba(255, 255, 255, 0.7);
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .profile-summary-item strong {
        color: #FFFFFF;
        font-size: 14px;
    }

    .filter-card {
        margin-bottom: 24px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns:
            repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .filter-search {
        grid-column: span 2;
    }

    .filter-actions {
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
        margin-top: 4px;
    }

    .result-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 18px;
    }

    .result-count {
        color: #6B7280;
    }

    .scholarship-list {
        display: grid;
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
        gap: 20px;
    }

    .scholarship-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        border-top: 5px solid #467235;
        transition:
            transform 0.2s,
            box-shadow 0.2s;
    }

    .scholarship-card:hover {
        transform: translateY(-4px);
        box-shadow:
            0 10px 24px
            rgba(40, 63, 36, 0.12);
    }

    .card-labels {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-bottom: 14px;
    }

    .card-label {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 20px;
        background: #EEF2EC;
        color: #40523A;
        font-size: 11px;
        font-weight: bold;
    }

    .deadline-open {
        background: #DCEFD6;
        color: #28651F;
    }

    .deadline-soon {
        background: #FFF2BD;
        color: #775800;
    }

    .deadline-closed {
        background: #F5D4D1;
        color: #9D221A;
    }

    .scholarship-card h2 {
        color: #283F24;
        font-size: 20px;
        line-height: 1.4;
        margin-bottom: 7px;
    }

    .provider-name {
        color: #467235;
        font-size: 13px;
        font-weight: bold;
        margin-bottom: 13px;
    }

    .description {
        color: #6B7280;
        line-height: 1.65;
        margin-bottom: 16px;
        flex-grow: 1;
    }

    .card-information {
        background: #F7F9F6;
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 15px;
        display: grid;
        gap: 9px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        border-bottom:
            1px solid #E2E7DF;
        padding-bottom: 8px;
    }

    .info-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .info-row span {
        color: #6B7280;
        font-size: 12px;
    }

    .info-row strong {
        color: #283F24;
        font-size: 12px;
        text-align: right;
    }

    .eligibility-box {
        border-radius: 9px;
        padding: 12px;
        margin-bottom: 15px;
        font-size: 13px;
        line-height: 1.55;
    }

    .eligibility-qualified {
        background: #E4F2DF;
        border: 1px solid #A9D49B;
        color: #24551C;
    }

    .eligibility-unqualified {
        background: #FCE7E5;
        border: 1px solid #E7AAA5;
        color: #8E201A;
    }

    .eligibility-incomplete {
        background: #FFF8D4;
        border: 1px solid #E7CB67;
        color: #6C5200;
    }

    .application-status-box {
        background: #F7F9F6;
        border-radius: 9px;
        padding: 12px;
        margin-bottom: 15px;
    }

    .card-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        border-top: 1px solid #DDE2D9;
        padding-top: 15px;
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
        .scholarship-list {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .filter-grid {
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
        }

        .filter-search {
            grid-column: span 2;
        }
    }

    @media (max-width: 750px) {
        .scholarship-list {
            grid-template-columns: 1fr;
        }

        .filter-grid {
            grid-template-columns: 1fr;
        }

        .filter-search {
            grid-column: auto;
        }

        .profile-summary {
            grid-template-columns: 1fr;
        }

        .result-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="scholarship-page-hero">

    <h1>
        Find Your Scholarship
    </h1>

    <p>
        Search verified scholarship listings,
        compare eligibility requirements,
        review official benefits, and submit
        your application through ScholarTrack.
    </p>

    <div class="profile-summary">

        <div class="profile-summary-item">

            <span>
                Course
            </span>

            <strong>
                <?= $student_course !== ""
                    ? e($student_course)
                    : "Not provided"; ?>
            </strong>

        </div>

        <div class="profile-summary-item">

            <span>
                Current GWA
            </span>

            <strong>
                <?= $student_gwa !== null &&
                    $student_gwa !== ""
                    ? e(
                        (string) $student_gwa
                    )
                    : "Not provided"; ?>
            </strong>

        </div>

        <div class="profile-summary-item">

            <span>
                Annual Family Income
            </span>

            <strong>
                <?= $student_income !== null &&
                    $student_income !== ""
                    ? scholarship_money(
                        $student_income
                    )
                    : "Not provided"; ?>
            </strong>

        </div>

    </div>

</div>

<?php if (!$has_eligibility_data): ?>

    <div class="alert warning">

        Complete your GWA and annual family
        income in your profile to use the
        qualified-only filter and eligibility
        checker.

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

<?php endif; ?>

<div class="card filter-card">

    <h2 class="card-title">
        Search and Filters
    </h2>

    <form
        method="GET"
        action="scholarships.php"
    >

        <div class="filter-grid">

            <div class="form-group filter-search">

                <label for="search">
                    Search Scholarships
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    value="<?= e($search); ?>"
                    placeholder="Search title, provider, benefit, course, region, or category"
                >

            </div>

            <div class="form-group">

                <label for="provider">
                    Provider
                </label>

                <select
                    id="provider"
                    name="provider"
                    class="form-control"
                >
                    <option value="">
                        All Providers
                    </option>

                    <?php foreach (
                        $providers as $provider
                    ): ?>

                        <option
                            value="<?= e(
                                $provider["provider"]
                            ); ?>"
                            <?= $provider_filter ===
                                $provider["provider"]
                                ? "selected"
                                : ""; ?>
                        >
                            <?= e(
                                $provider["provider"]
                            ); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

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

                    <?php foreach (
                        $categories as $category
                    ): ?>

                        <option
                            value="<?= e(
                                $category["category"]
                            ); ?>"
                            <?= $category_filter ===
                                $category["category"]
                                ? "selected"
                                : ""; ?>
                        >
                            <?= e(
                                $category["category"]
                            ); ?>
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

                    <?php foreach (
                        $regions as $region
                    ): ?>

                        <option
                            value="<?= e(
                                $region["region"]
                            ); ?>"
                            <?= $region_filter ===
                                $region["region"]
                                ? "selected"
                                : ""; ?>
                        >
                            <?= e(
                                $region["region"]
                            ); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label for="course">
                    Course
                </label>

                <select
                    id="course"
                    name="course"
                    class="form-control"
                >
                    <option value="">
                        Any Course
                    </option>

                    <?php foreach (
                        $course_options as $course
                    ): ?>

                        <option
                            value="<?= e($course); ?>"
                            <?= $course_filter === $course
                                ? "selected"
                                : ""; ?>
                        >
                            <?= e($course); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label for="deadline">
                    Deadline
                </label>

                <select
                    id="deadline"
                    name="deadline"
                    class="form-control"
                >
                    <option value="">
                        Any Deadline
                    </option>

                    <option
                        value="open"
                        <?= $deadline_filter === "open"
                            ? "selected"
                            : ""; ?>
                    >
                        Open Applications
                    </option>

                    <option
                        value="closing_soon"
                        <?= $deadline_filter === "closing_soon"
                            ? "selected"
                            : ""; ?>
                    >
                        Closing Within 30 Days
                    </option>

                    <option
                        value="no_deadline"
                        <?= $deadline_filter === "no_deadline"
                            ? "selected"
                            : ""; ?>
                    >
                        Deadline Not Announced
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="minimum_allowance">
                    Minimum Monthly Allowance
                </label>

                <input
                    type="number"
                    id="minimum_allowance"
                    name="minimum_allowance"
                    class="form-control"
                    min="0"
                    step="0.01"
                    value="<?= e(
                        $minimum_allowance
                    ); ?>"
                    placeholder="Example: 5000"
                >

            </div>

            <div class="form-group">

                <label for="eligibility">
                    Eligibility
                </label>

                <select
                    id="eligibility"
                    name="eligibility"
                    class="form-control"
                    <?= !$has_eligibility_data
                        ? "disabled"
                        : ""; ?>
                >
                    <option value="">
                        Show All
                    </option>

                    <option
                        value="qualified"
                        <?= $eligibility_filter ===
                            "qualified"
                            ? "selected"
                            : ""; ?>
                    >
                        Qualified Only
                    </option>

                </select>

                <?php if (
                    !$has_eligibility_data
                ): ?>

                    <input
                        type="hidden"
                        name="eligibility"
                        value=""
                    >

                <?php endif; ?>

            </div>

        </div>

        <div class="filter-actions">

            <button
                type="submit"
                class="btn btn-primary"
            >
                Apply Filters
            </button>

            <a
                href="scholarships.php"
                class="btn btn-secondary"
            >
                Reset
            </a>

        </div>

    </form>

</div>

<div class="result-toolbar">

    <p class="result-count">

        <strong>
            <?= count($scholarships); ?>
        </strong>

        scholarship<?= count($scholarships) === 1
            ? ""
            : "s"; ?>

        found.

    </p>

    <a
        href="profile.php"
        class="btn btn-secondary"
    >
        Update My Profile
    </a>

</div>

<?php if (empty($scholarships)): ?>

    <div class="card empty-state">

        <h2>
            No scholarships found
        </h2>

        <p>
            Try changing or removing some of
            your filters.
        </p>

        <a
            href="scholarships.php"
            class="btn btn-primary"
        >
            Clear Filters
        </a>

    </div>

<?php else: ?>

    <div class="scholarship-list">

        <?php foreach (
            $scholarships as $scholarship
        ): ?>

            <?php
            $minimum_gwa_required =
                $scholarship["minimum_gwa"] !== null &&
                $scholarship["minimum_gwa"] !== "";

            $income_required =
                $scholarship["max_income"] !== null &&
                $scholarship["max_income"] !== "";

            $meets_gwa = true;
            $meets_income = true;

            if ($minimum_gwa_required) {
                $meets_gwa =
                    $student_gwa !== null &&
                    $student_gwa !== "" &&
                    (float) $student_gwa <=
                    (float) $scholarship["minimum_gwa"];
            }

            if ($income_required) {
                $meets_income =
                    $student_income !== null &&
                    $student_income !== "" &&
                    (float) $student_income <=
                    (float) $scholarship["max_income"];
            }

            $is_qualified =
                $has_eligibility_data &&
                $meets_gwa &&
                $meets_income;

            $has_deadline =
                !empty(
                    $scholarship["deadline"]
                );

            $is_expired = false;
            $closing_soon = false;

            if ($has_deadline) {
                $deadline_timestamp =
                    strtotime(
                        $scholarship["deadline"]
                    );

                $today_timestamp =
                    strtotime(date("Y-m-d"));

                $soon_timestamp =
                    strtotime("+30 days");

                $is_expired =
                    $deadline_timestamp <
                    $today_timestamp;

                $closing_soon =
                    !$is_expired &&
                    $deadline_timestamp <=
                    $soon_timestamp;
            }

            if (!$has_deadline) {
                $deadline_label =
                    "Deadline Not Announced";

                $deadline_class =
                    "";
            } elseif ($is_expired) {
                $deadline_label =
                    "Closed";

                $deadline_class =
                    "deadline-closed";
            } elseif ($closing_soon) {
                $deadline_label =
                    "Closing Soon";

                $deadline_class =
                    "deadline-soon";
            } else {
                $deadline_label =
                    "Open";

                $deadline_class =
                    "deadline-open";
            }

            $description =
                $scholarship["description"]
                ?? "";

            if (strlen($description) > 180) {
                $description =
                    substr(
                        $description,
                        0,
                        180
                    ) . "...";
            }
            ?>

            <div class="card scholarship-card">

                <div class="card-labels">

                    <?php if (
                        !empty(
                            $scholarship["category"]
                        )
                    ): ?>

                        <span class="card-label">
                            <?= e(
                                $scholarship["category"]
                            ); ?>
                        </span>

                    <?php endif; ?>

                    <?php if (
                        !empty(
                            $scholarship["region"]
                        )
                    ): ?>

                        <span class="card-label">
                            <?= e(
                                $scholarship["region"]
                            ); ?>
                        </span>

                    <?php endif; ?>

                    <span
                        class="card-label <?= e(
                            $deadline_class
                        ); ?>"
                    >
                        <?= e($deadline_label); ?>
                    </span>

                </div>

                <h2>
                    <?= e(
                        $scholarship["title"]
                    ); ?>
                </h2>

                <p class="provider-name">
                    <?= e(
                        $scholarship["provider"]
                    ); ?>
                </p>

                <p class="description">
                    <?= e($description); ?>
                </p>

                <div class="card-information">

                    <div class="info-row">

                        <span>
                            Required GWA
                        </span>

                        <strong>
                            <?= $minimum_gwa_required
                                ? e(
                                    (string)
                                    $scholarship["minimum_gwa"]
                                ) . " or better"
                                : "Not specified"; ?>
                        </strong>

                    </div>

                    <div class="info-row">

                        <span>
                            Maximum Income
                        </span>

                        <strong>
                            <?= $income_required
                                ? scholarship_money(
                                    $scholarship["max_income"]
                                )
                                : "Not specified"; ?>
                        </strong>

                    </div>

                    <div class="info-row">

                        <span>
                            Monthly Allowance
                        </span>

                        <strong>
                            <?= scholarship_money(
                                $scholarship["monthly_allowance"]
                            ); ?>
                        </strong>

                    </div>

                    <div class="info-row">

                        <span>
                            Deadline
                        </span>

                        <strong>
                            <?= $has_deadline
                                ? date(
                                    "M d, Y",
                                    strtotime(
                                        $scholarship["deadline"]
                                    )
                                )
                                : "To be announced"; ?>
                        </strong>

                    </div>

                </div>

                <?php if (
                    (int)
                    $scholarship["already_applied"] === 1
                ): ?>

                    <?php
                    $application_status =
                        $scholarship["application_status"]
                        ?? "Pending";

                    $application_class =
                        status_badge_class(
                            $application_status
                        );
                    ?>

                    <div class="application-status-box">

                        <strong>
                            Your Application:
                        </strong>

                        <span
                            class="badge badge-<?= e(
                                $application_class
                            ); ?>"
                        >
                            <?= e(
                                $application_status
                            ); ?>
                        </span>

                    </div>

                <?php elseif (
                    !$has_eligibility_data
                ): ?>

                    <div class="eligibility-box eligibility-incomplete">

                        Complete your profile to check
                        whether you meet the basic
                        numeric requirements.

                    </div>

                <?php elseif ($is_qualified): ?>

                    <div class="eligibility-box eligibility-qualified">

                        Your current GWA and annual
                        family income meet the basic
                        requirements.

                    </div>

                <?php else: ?>

                    <div class="eligibility-box eligibility-unqualified">

                        Your current profile does not
                        meet one or more basic numeric
                        requirements.

                    </div>

                <?php endif; ?>

                <div class="card-actions">

                    <a
                        href="scholarship_view.php?id=<?= (int) $scholarship["id"]; ?>"
                        class="btn btn-primary"
                    >
                        View Details
                    </a>

                    <?php if (
                        (int)
                        $scholarship["already_applied"] === 1
                    ): ?>

                        <a
                            href="my_applications.php"
                            class="btn btn-secondary"
                        >
                            View Application
                        </a>

                    <?php elseif (
                        !$is_expired &&
                        $is_qualified
                    ): ?>

                        <a
                            href="apply.php?id=<?= (int) $scholarship["id"]; ?>"
                            class="btn btn-gold"
                        >
                            Apply Now
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php include "footer.php"; ?>