<?php
require_once "config.php";

require_student();

$user_id = (int) $_SESSION["user"]["id"];
$scholarship_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($scholarship_id <= 0) {
    flash(
        "Invalid scholarship selected.",
        "error"
    );

    redirect("scholarships.php");
}

/*
|--------------------------------------------------------------------------
| Get Scholarship
|--------------------------------------------------------------------------
*/

$scholarship_statement = $pdo->prepare(
    "SELECT *
     FROM scholarships
     WHERE id = ?
     LIMIT 1"
);

$scholarship_statement->execute([
    $scholarship_id
]);

$scholarship = $scholarship_statement->fetch();

if (!$scholarship) {
    flash(
        "Scholarship was not found.",
        "error"
    );

    redirect("scholarships.php");
}

/*
|--------------------------------------------------------------------------
| Get Student Profile
|--------------------------------------------------------------------------
*/

$profile_statement = $pdo->prepare(
    "SELECT
        student_no,
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
| Eligibility Check
|--------------------------------------------------------------------------
*/

$meets_gwa = true;
$meets_income = true;

if (
    $scholarship["minimum_gwa"] !== null &&
    $scholarship["minimum_gwa"] !== ""
) {
    $meets_gwa =
        $student_gwa !== null &&
        $student_gwa !== "" &&
        (float) $student_gwa <=
        (float) $scholarship["minimum_gwa"];
}

if (
    $scholarship["max_income"] !== null &&
    $scholarship["max_income"] !== ""
) {
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

/*
|--------------------------------------------------------------------------
| Existing Application
|--------------------------------------------------------------------------
*/

$application_statement = $pdo->prepare(
    "SELECT
        id,
        status,
        admin_note,
        submitted_at,
        updated_at
     FROM applications
     WHERE scholarship_id = ?
     AND student_id = ?
     LIMIT 1"
);

$application_statement->execute([
    $scholarship_id,
    $user_id
]);

$existing_application =
    $application_statement->fetch();

/*
|--------------------------------------------------------------------------
| Deadline Status
|--------------------------------------------------------------------------
*/

$has_deadline =
    !empty($scholarship["deadline"]);

$is_expired = false;

if ($has_deadline) {
    $is_expired =
        strtotime($scholarship["deadline"]) <
        strtotime(date("Y-m-d"));
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function scholarship_lines($text)
{
    $items = [];

    if ($text === null || trim($text) === "") {
        return $items;
    }

    $lines = preg_split(
        "/\r\n|\r|\n/",
        trim($text)
    );

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line !== "") {
            $items[] = $line;
        }
    }

    return $items;
}

function money_display($value)
{
    if ($value === null || $value === "") {
        return "Not specified";
    }

    return "₱" . number_format(
        (float) $value,
        2
    );
}

$qualification_items =
    scholarship_lines(
        $scholarship["qualifications"] ?? ""
    );

$requirement_items =
    scholarship_lines(
        $scholarship["documentary_requirements"] ?? ""
    );

$process_items =
    scholarship_lines(
        $scholarship["selection_process"] ?? ""
    );

$page_title = "Scholarship Details";

include "header.php";
?>

<style>
    .scholarship-hero {
        background:
            linear-gradient(
                135deg,
                #283F24,
                #467235
            );
        color: #FFFFFF;
        border-radius: 18px;
        padding: 34px;
        margin-bottom: 24px;
        box-shadow:
            0 10px 28px
            rgba(40, 63, 36, 0.16);
    }

    .hero-labels {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }

    .hero-label {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 20px;
        background:
            rgba(255, 255, 255, 0.13);
        border:
            1px solid
            rgba(255, 255, 255, 0.2);
        color: #FFFFFF;
        font-size: 12px;
        font-weight: bold;
    }

    .scholarship-hero h1 {
        color: #FFBF00;
        font-size: 34px;
        line-height: 1.3;
        margin-bottom: 10px;
    }

    .hero-provider {
        font-size: 17px;
        font-weight: bold;
        margin-bottom: 12px;
    }

    .hero-description {
        max-width: 850px;
        color:
            rgba(255, 255, 255, 0.85);
        line-height: 1.7;
    }

    .details-layout {
        display: grid;
        grid-template-columns:
            minmax(0, 2fr)
            minmax(280px, 1fr);
        gap: 20px;
        align-items: start;
    }

    .section-card h2 {
        color: #283F24;
        margin-bottom: 15px;
    }

    .section-card p {
        color: #4B5563;
        line-height: 1.8;
    }

    .benefit-summary {
        background: #FFF9DB;
        border: 1px solid #EAD47D;
        color: #614B00;
        border-radius: 10px;
        padding: 16px;
        line-height: 1.7;
        margin-bottom: 18px;
    }

    .money-grid {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .money-item {
        background: #F7F9F6;
        border: 1px solid #DDE2D9;
        border-radius: 10px;
        padding: 15px;
    }

    .money-item span {
        display: block;
        color: #6B7280;
        font-size: 12px;
        margin-bottom: 6px;
    }

    .money-item strong {
        color: #283F24;
        font-size: 17px;
    }

    .detail-list {
        list-style: none;
    }

    .detail-list li {
        position: relative;
        padding:
            12px 10px
            12px 35px;
        border-bottom:
            1px solid #DDE2D9;
        color: #4B5563;
        line-height: 1.6;
    }

    .detail-list li:last-child {
        border-bottom: none;
    }

    .detail-list li::before {
        content: "✓";
        position: absolute;
        left: 8px;
        top: 12px;
        width: 19px;
        height: 19px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #DCEFD6;
        color: #28651F;
        border-radius: 50%;
        font-size: 11px;
        font-weight: bold;
    }

    .requirement-list li::before {
        content: "•";
        background: #FFF2BD;
        color: #775800;
    }

    .process-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .process-item {
        display: grid;
        grid-template-columns:
            42px 1fr;
        gap: 12px;
        align-items: center;
        background: #F7F9F6;
        border: 1px solid #DDE2D9;
        border-radius: 10px;
        padding: 13px;
    }

    .process-number {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #467235;
        color: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .sidebar-card {
        position: sticky;
        top: 108px;
    }

    .status-panel {
        text-align: center;
    }

    .status-symbol {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        margin: 0 auto 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #FFF2BD;
        color: #775800;
        font-size: 28px;
        font-weight: bold;
    }

    .status-panel h3 {
        color: #283F24;
        margin-bottom: 8px;
    }

    .status-panel p {
        color: #6B7280;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .criteria-box {
        background: #F7F9F6;
        border-radius: 10px;
        padding: 15px;
        text-align: left;
        line-height: 1.8;
        margin-bottom: 16px;
    }

    .criteria-box strong {
        color: #283F24;
    }

    .deadline-box {
        background: #FFF8D4;
        border: 1px solid #E7CB67;
        color: #6C5200;
        border-radius: 9px;
        padding: 14px;
        margin-bottom: 15px;
        line-height: 1.6;
    }

    .source-note {
        background: #F7F9F6;
        border-left: 4px solid #467235;
        border-radius: 8px;
        padding: 14px;
        color: #4B5563;
        line-height: 1.7;
        margin-bottom: 15px;
    }

    .official-link {
        width: 100%;
        text-align: center;
    }

    .action-stack {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .action-stack .btn {
        width: 100%;
        text-align: center;
    }

    .application-note {
        background: #F7F9F6;
        border-radius: 9px;
        padding: 13px;
        margin-top: 12px;
        text-align: left;
        color: #4B5563;
        line-height: 1.6;
        font-size: 13px;
    }

    @media (max-width: 950px) {
        .details-layout {
            grid-template-columns: 1fr;
        }

        .sidebar-card {
            position: static;
        }
    }

    @media (max-width: 650px) {
        .money-grid {
            grid-template-columns: 1fr;
        }

        .scholarship-hero h1 {
            font-size: 27px;
        }

        .scholarship-hero {
            padding: 25px;
        }
    }
</style>

<div class="scholarship-hero">

    <div class="hero-labels">

        <?php if (
            !empty($scholarship["category"])
        ): ?>

            <span class="hero-label">
                <?= e(
                    $scholarship["category"]
                ); ?>
            </span>

        <?php endif; ?>

        <?php if (
            !empty($scholarship["region"])
        ): ?>

            <span class="hero-label">
                <?= e(
                    $scholarship["region"]
                ); ?>
            </span>

        <?php endif; ?>

        <?php if ($is_expired): ?>

            <span class="hero-label">
                Application Closed
            </span>

        <?php elseif ($has_deadline): ?>

            <span class="hero-label">
                Accepting Applications
            </span>

        <?php else: ?>

            <span class="hero-label">
                Deadline To Be Announced
            </span>

        <?php endif; ?>

    </div>

    <h1>
        <?= e($scholarship["title"]); ?>
    </h1>

    <p class="hero-provider">
        <?= e($scholarship["provider"]); ?>
    </p>

    <p class="hero-description">
        Review the official scholarship information,
        qualifications, documentary requirements,
        benefits, and application process before applying.
    </p>

</div>

<div class="details-layout">

    <div>

        <div class="card section-card">

            <h2>
                Scholarship Overview
            </h2>

            <p>
                <?= nl2br(
                    e(
                        $scholarship["description"]
                        ?? "No description was provided."
                    )
                ); ?>
            </p>

        </div>

        <div class="card section-card">

            <h2>
                Benefits and Coverage
            </h2>

            <?php if (
                !empty($scholarship["benefits"])
            ): ?>

                <div class="benefit-summary">
                    <?= nl2br(
                        e(
                            $scholarship["benefits"]
                        )
                    ); ?>
                </div>

            <?php endif; ?>

            <?php if (
                !empty($scholarship["coverage"])
            ): ?>

                <p style="margin-bottom:18px;">
                    <?= nl2br(
                        e(
                            $scholarship["coverage"]
                        )
                    ); ?>
                </p>

            <?php endif; ?>

            <div class="money-grid">

                <div class="money-item">
                    <span>
                        Monthly Allowance
                    </span>

                    <strong>
                        <?= money_display(
                            $scholarship["monthly_allowance"]
                        ); ?>
                    </strong>
                </div>

                <div class="money-item">
                    <span>
                        Tuition Fee Coverage
                    </span>

                    <strong>
                        <?= money_display(
                            $scholarship["tuition_fee"]
                        ); ?>
                    </strong>
                </div>

                <div class="money-item">
                    <span>
                        Book Allowance
                    </span>

                    <strong>
                        <?= money_display(
                            $scholarship["book_allowance"]
                        ); ?>
                    </strong>
                </div>

                <div class="money-item">
                    <span>
                        Transportation Allowance
                    </span>

                    <strong>
                        <?= money_display(
                            $scholarship["transportation_allowance"]
                        ); ?>
                    </strong>
                </div>

                <div class="money-item">
                    <span>
                        Living Allowance
                    </span>

                    <strong>
                        <?= money_display(
                            $scholarship["living_allowance"]
                        ); ?>
                    </strong>
                </div>

                <div class="money-item">
                    <span>
                        One-Time Grant
                    </span>

                    <strong>
                        <?= money_display(
                            $scholarship["one_time_grant"]
                        ); ?>
                    </strong>
                </div>

            </div>

        </div>

        <div class="card section-card">

            <h2>
                Qualifications
            </h2>

            <?php if (
                empty($qualification_items)
            ): ?>

                <p>
                    No detailed qualifications were
                    provided. Please check the official
                    scholarship website.
                </p>

            <?php else: ?>

                <ul class="detail-list">

                    <?php foreach (
                        $qualification_items
                        as $qualification
                    ): ?>

                        <li>
                            <?= e($qualification); ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php endif; ?>

        </div>

        <div class="card section-card">

            <h2>
                Documentary Requirements
            </h2>

            <?php if (
                empty($requirement_items)
            ): ?>

                <p>
                    No detailed documentary requirements
                    were provided. Please verify them on
                    the official scholarship website.
                </p>

            <?php else: ?>

                <ul class="detail-list requirement-list">

                    <?php foreach (
                        $requirement_items
                        as $requirement
                    ): ?>

                        <li>
                            <?= e($requirement); ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php endif; ?>

        </div>

        <div class="card section-card">

            <h2>
                Selection Process
            </h2>

            <?php if (
                empty($process_items)
            ): ?>

                <p>
                    The official selection process has not
                    been provided.
                </p>

            <?php else: ?>

                <div class="process-list">

                    <?php foreach (
                        $process_items
                        as $index => $process
                    ): ?>

                        <div class="process-item">

                            <div class="process-number">
                                <?= $index + 1; ?>
                            </div>

                            <div>
                                <?= e($process); ?>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

        <div class="card section-card">

            <h2>
                Eligible Courses
            </h2>

            <p>
                <?= !empty(
                    $scholarship["eligible_courses"]
                )
                    ? nl2br(
                        e(
                            $scholarship["eligible_courses"]
                        )
                    )
                    : "Eligible courses were not specified."; ?>
            </p>

        </div>

    </div>

    <div>

        <div class="card sidebar-card status-panel">

            <?php if (
                $existing_application
            ): ?>

                <?php
                $status_class = strtolower(
                    str_replace(
                        " ",
                        "-",
                        $existing_application["status"]
                    )
                );
                ?>

                <div class="status-symbol">
                    ✓
                </div>

                <h3>
                    Application Submitted
                </h3>

                <p>
                    Your application has already been
                    submitted for this scholarship.
                </p>

                <div class="criteria-box">

                    <strong>Status:</strong>

                    <span
                        class="badge badge-<?= e(
                            $status_class
                        ); ?>"
                    >
                        <?= e(
                            $existing_application["status"]
                        ); ?>
                    </span>

                    <br>

                    <strong>Submitted:</strong>

                    <?= date(
                        "F d, Y",
                        strtotime(
                            $existing_application["submitted_at"]
                        )
                    ); ?>

                    <?php if (
                        !empty(
                            $existing_application["admin_note"]
                        )
                    ): ?>

                        <div class="application-note">

                            <strong>
                                Admin Note:
                            </strong>

                            <br>

                            <?= nl2br(
                                e(
                                    $existing_application["admin_note"]
                                )
                            ); ?>

                        </div>

                    <?php endif; ?>

                </div>

                <a
                    href="my_applications.php"
                    class="btn btn-primary"
                >
                    View My Application
                </a>

            <?php elseif ($is_expired): ?>

                <div class="status-symbol">
                    ×
                </div>

                <h3>
                    Application Closed
                </h3>

                <p>
                    The official deadline for this
                    scholarship has already passed.
                </p>

                <a
                    href="scholarships.php"
                    class="btn btn-secondary"
                >
                    Browse Other Scholarships
                </a>

            <?php elseif (
                !$has_eligibility_data
            ): ?>

                <div class="status-symbol">
                    i
                </div>

                <h3>
                    Complete Your Profile
                </h3>

                <p>
                    Add your GWA and annual family
                    income so ScholarTrack can check
                    your basic eligibility.
                </p>

                <a
                    href="profile.php"
                    class="btn btn-primary"
                >
                    Complete Profile
                </a>

            <?php elseif ($is_qualified): ?>

                <div class="status-symbol">
                    ✓
                </div>

                <h3>
                    Basic Criteria Matched
                </h3>

                <p>
                    Based on your saved GWA and annual
                    family income, you meet the basic
                    numeric requirements.
                </p>

                <div class="criteria-box">

                    <strong>Your GWA:</strong>
                    <?= e(
                        (string) $student_gwa
                    ); ?>

                    <br>

                    <strong>Required GWA:</strong>
                    <?= $scholarship["minimum_gwa"] !== null
                        ? e(
                            (string)
                            $scholarship["minimum_gwa"]
                        ) . " or better"
                        : "Not specified"; ?>

                    <br>

                    <strong>Your Annual Income:</strong>
                    <?= money_display(
                        $student_income
                    ); ?>

                    <br>

                    <strong>Maximum Income:</strong>
                    <?= money_display(
                        $scholarship["max_income"]
                    ); ?>

                </div>

                <?php if ($has_deadline): ?>

                    <div class="deadline-box">
                        Apply on or before

                        <strong>
                            <?= date(
                                "F d, Y",
                                strtotime(
                                    $scholarship["deadline"]
                                )
                            ); ?>
                        </strong>.
                    </div>

                <?php endif; ?>

                <div class="action-stack">

                    <a
                        href="apply.php?id=<?= (int) $scholarship["id"]; ?>"
                        class="btn btn-gold"
                    >
                        Apply Now
                    </a>

                    <a
                        href="profile.php"
                        class="btn btn-secondary"
                    >
                        Review My Profile
                    </a>

                </div>

            <?php else: ?>

                <div class="status-symbol">
                    !
                </div>

                <h3>
                    Basic Criteria Not Met
                </h3>

                <p>
                    Your profile currently does not
                    satisfy one or more of the basic
                    numeric requirements.
                </p>

                <div class="criteria-box">

                    <strong>Your GWA:</strong>
                    <?= e(
                        (string) $student_gwa
                    ); ?>

                    <br>

                    <strong>Required GWA:</strong>
                    <?= $scholarship["minimum_gwa"] !== null
                        ? e(
                            (string)
                            $scholarship["minimum_gwa"]
                        ) . " or better"
                        : "Not specified"; ?>

                    <br>

                    <strong>Your Annual Income:</strong>
                    <?= money_display(
                        $student_income
                    ); ?>

                    <br>

                    <strong>Maximum Income:</strong>
                    <?= money_display(
                        $scholarship["max_income"]
                    ); ?>

                </div>

                <a
                    href="profile.php"
                    class="btn btn-secondary"
                >
                    Review My Profile
                </a>

            <?php endif; ?>

        </div>

        <div class="card">

            <h3 class="card-title">
                Official Information
            </h3>

            <div class="source-note">

                <?= nl2br(
                    e(
                        !empty($scholarship["source_note"])
                            ? $scholarship["source_note"]
                            : "Information is based on publicly available scholarship guidelines. Applicants should verify all current details through the official scholarship provider."
                    )
                ); ?>

            </div>

            <?php if (
                !empty(
                    $scholarship["official_website"]
                )
            ): ?>

                <a
                    href="<?= e(
                        $scholarship["official_website"]
                    ); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-primary official-link"
                >
                    Visit Official Website
                </a>

            <?php else: ?>

                <p style="color:#6B7280;">
                    No official website link has been
                    added yet.
                </p>

            <?php endif; ?>

        </div>

        <a
            href="scholarships.php"
            class="btn btn-secondary"
        >
            Back to Scholarships
        </a>

    </div>

</div>

<?php include "footer.php"; ?>