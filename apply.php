<?php
require_once "config.php";

require_student();

$user_id = (int) $_SESSION["user"]["id"];
$scholarship_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

$errors = [];

/*
|--------------------------------------------------------------------------
| Validate Scholarship
|--------------------------------------------------------------------------
*/

if ($scholarship_id <= 0) {
    flash(
        "Invalid scholarship selected.",
        "error"
    );

    redirect("scholarships.php");
}

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
| Deadline Check
|--------------------------------------------------------------------------
*/

if (
    !empty($scholarship["deadline"]) &&
    strtotime($scholarship["deadline"]) <
    strtotime(date("Y-m-d"))
) {
    flash(
        "Applications for this scholarship are already closed.",
        "error"
    );

    redirect(
        "scholarship_view.php?id=" .
        $scholarship_id
    );
}

/*
|--------------------------------------------------------------------------
| Existing Application Check
|--------------------------------------------------------------------------
*/

$existing_statement = $pdo->prepare(
    "SELECT id
     FROM applications
     WHERE student_id = ?
     AND scholarship_id = ?
     LIMIT 1"
);

$existing_statement->execute([
    $user_id,
    $scholarship_id
]);

if ($existing_statement->fetch()) {
    flash(
        "You have already applied for this scholarship.",
        "warning"
    );

    redirect("my_applications.php");
}

/*
|--------------------------------------------------------------------------
| Student Information
|--------------------------------------------------------------------------
*/

$profile_statement = $pdo->prepare(
    "SELECT
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
        student_profiles.house_no,
        student_profiles.street,
        student_profiles.barangay,
        student_profiles.municipality,
        student_profiles.province,
        student_profiles.zipcode

     FROM users

     LEFT JOIN student_profiles
        ON student_profiles.user_id = users.id

     WHERE users.id = ?
     LIMIT 1"
);

$profile_statement->execute([
    $user_id
]);

$profile = $profile_statement->fetch();

if (!$profile) {
    flash(
        "Student profile was not found.",
        "error"
    );

    redirect("profile.php");
}

$student_course =
    $profile["course"] ?? "";

if (
    $student_course === "Other" &&
    !empty($profile["other_course"])
) {
    $student_course =
        $profile["other_course"];
}

/*
|--------------------------------------------------------------------------
| Profile Completion Check
|--------------------------------------------------------------------------
*/

$required_profile_fields = [
    "student_no",
    "school",
    "course",
    "year_level",
    "gwa",
    "annual_income",
    "contact_number",
    "barangay",
    "municipality",
    "province",
    "zipcode"
];

$missing_profile_fields = [];

foreach ($required_profile_fields as $field) {
    if (
        !isset($profile[$field]) ||
        $profile[$field] === null ||
        trim((string) $profile[$field]) === ""
    ) {
        $missing_profile_fields[] = $field;
    }
}

/*
|--------------------------------------------------------------------------
| Basic Eligibility Check
|--------------------------------------------------------------------------
*/

$meets_gwa = true;
$meets_income = true;

if (
    $scholarship["minimum_gwa"] !== null &&
    $scholarship["minimum_gwa"] !== ""
) {
    $meets_gwa =
        $profile["gwa"] !== null &&
        $profile["gwa"] !== "" &&
        (float) $profile["gwa"] <=
        (float) $scholarship["minimum_gwa"];
}

if (
    $scholarship["max_income"] !== null &&
    $scholarship["max_income"] !== ""
) {
    $meets_income =
        $profile["annual_income"] !== null &&
        $profile["annual_income"] !== "" &&
        (float) $profile["annual_income"] <=
        (float) $scholarship["max_income"];
}

$is_eligible =
    empty($missing_profile_fields) &&
    $meets_gwa &&
    $meets_income;

/*
|--------------------------------------------------------------------------
| Requirements List
|--------------------------------------------------------------------------
*/

$requirements = [];

if (
    !empty(
        $scholarship["documentary_requirements"]
    )
) {
    $requirement_lines = preg_split(
        "/\r\n|\r|\n/",
        trim(
            $scholarship["documentary_requirements"]
        )
    );

    foreach ($requirement_lines as $line) {
        $line = trim($line);

        if ($line !== "") {
            $requirements[] = $line;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Submit Application
|--------------------------------------------------------------------------
*/

$application_statement =
    trim(
        $_POST["application_statement"] ?? ""
    );

$declaration =
    isset($_POST["declaration"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!empty($missing_profile_fields)) {
        $errors[] =
            "Please complete your student profile before applying.";
    }

    if (!$meets_gwa || !$meets_income) {
        $errors[] =
            "Your current profile does not meet one or more basic scholarship requirements.";
    }

    if ($application_statement === "") {
        $errors[] =
            "Application statement is required.";
    }

    if (strlen($application_statement) < 50) {
        $errors[] =
            "Application statement must contain at least 50 characters.";
    }

    if (!$declaration) {
        $errors[] =
            "You must confirm that the information and documents are accurate.";
    }

    foreach ($requirements as $index => $requirement) {
        $field_name =
            "requirement_" . $index;

        if (!isset($_POST[$field_name])) {
            $errors[] =
                "Please confirm that you prepared all required documents.";
            break;
        }
    }

    $uploaded_file_name = "";

    if (
        !isset($_FILES["application_document"]) ||
        $_FILES["application_document"]["error"] ===
        UPLOAD_ERR_NO_FILE
    ) {
        $errors[] =
            "Please upload your compiled application document.";
    } else {
        $file =
            $_FILES["application_document"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            $errors[] =
                "There was an error uploading your document.";
        } else {
            $allowed_extensions = [
                "pdf",
                "jpg",
                "jpeg",
                "png"
            ];

            $extension = strtolower(
                pathinfo(
                    $file["name"],
                    PATHINFO_EXTENSION
                )
            );

            if (
                !in_array(
                    $extension,
                    $allowed_extensions,
                    true
                )
            ) {
                $errors[] =
                    "Application document must be PDF, JPG, JPEG, or PNG.";
            }

            if ($file["size"] > 10 * 1024 * 1024) {
                $errors[] =
                    "Application document must not exceed 10 MB.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $insert_application = $pdo->prepare(
                "INSERT INTO applications
                (
                    student_id,
                    scholarship_id,
                    application_statement,
                    status
                )
                VALUES (?, ?, ?, 'Pending')"
            );

            $insert_application->execute([
                $user_id,
                $scholarship_id,
                $application_statement
            ]);

            $application_id =
                (int) $pdo->lastInsertId();

            $upload_folder =
                __DIR__ . "/uploads/";

            if (!is_dir($upload_folder)) {
                mkdir(
                    $upload_folder,
                    0777,
                    true
                );
            }

            $uploaded_file_name =
                "application_" .
                $application_id .
                "_" .
                time() .
                "." .
                $extension;

            $destination =
                $upload_folder .
                $uploaded_file_name;

            if (
                !move_uploaded_file(
                    $file["tmp_name"],
                    $destination
                )
            ) {
                throw new Exception(
                    "Unable to save uploaded document."
                );
            }

            $insert_document = $pdo->prepare(
                "INSERT INTO documents
                (
                    application_id,
                    file_name
                )
                VALUES (?, ?)"
            );

            $insert_document->execute([
                $application_id,
                $uploaded_file_name
            ]);

            $notification_message =
                'Your application for "' .
                $scholarship["title"] .
                '" was submitted successfully and is now pending review.';

            $insert_notification = $pdo->prepare(
                "INSERT INTO notifications
                (
                    user_id,
                    message,
                    is_read
                )
                VALUES (?, ?, 0)"
            );

            $insert_notification->execute([
                $user_id,
                $notification_message
            ]);

            $pdo->commit();

            flash(
                "Application submitted successfully.",
                "success"
            );

            redirect("my_applications.php");

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if (
                $uploaded_file_name !== "" &&
                file_exists(
                    __DIR__ .
                    "/uploads/" .
                    $uploaded_file_name
                )
            ) {
                unlink(
                    __DIR__ .
                    "/uploads/" .
                    $uploaded_file_name
                );
            }

            $errors[] =
                "Unable to submit your application. Please check the database columns and try again.";
        }
    }
}

$page_title = "Apply for Scholarship";

include "header.php";
?>

<style>
    .application-hero {
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

    .application-hero h1 {
        color: #FFBF00;
        margin-bottom: 8px;
        line-height: 1.3;
    }

    .application-hero p {
        color:
            rgba(255, 255, 255, 0.86);
        line-height: 1.7;
    }

    .application-layout {
        display: grid;
        grid-template-columns:
            minmax(0, 2fr)
            minmax(290px, 1fr);
        gap: 20px;
        align-items: start;
    }

    .section-title {
        color: #283F24;
        padding-bottom: 10px;
        margin-bottom: 17px;
        border-bottom: 2px solid #FFF2AD;
    }

    .information-grid {
        display: grid;
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .information-item {
        background: #F7F9F6;
        border: 1px solid #DDE2D9;
        border-radius: 10px;
        padding: 13px;
    }

    .information-item span {
        display: block;
        color: #6B7280;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 5px;
    }

    .information-item strong {
        color: #283F24;
        font-size: 13px;
        word-break: break-word;
    }

    .requirement-checklist {
        display: flex;
        flex-direction: column;
        gap: 11px;
    }

    .requirement-check {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: #F7F9F6;
        border: 1px solid #DDE2D9;
        border-radius: 10px;
        padding: 13px;
        color: #4B5563;
        line-height: 1.5;
    }

    .requirement-check input {
        margin-top: 3px;
        width: 17px;
        height: 17px;
    }

    .upload-box {
        border: 2px dashed #AFC2A8;
        border-radius: 12px;
        padding: 22px;
        background: #FAFCF9;
    }

    .upload-box h3 {
        margin-bottom: 7px;
    }

    .upload-box p {
        color: #6B7280;
        line-height: 1.6;
        font-size: 13px;
        margin-bottom: 14px;
    }

    .declaration-box {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        background: #FFF8D4;
        border: 1px solid #E7CB67;
        border-radius: 10px;
        padding: 14px;
        color: #6C5200;
        line-height: 1.6;
        margin-bottom: 18px;
    }

    .declaration-box input {
        margin-top: 4px;
        width: 18px;
        height: 18px;
    }

    .error-list {
        background: #FCE7E5;
        color: #8E201A;
        border: 1px solid #E7AAA5;
        border-radius: 8px;
        padding: 14px 18px 14px 36px;
        margin-bottom: 20px;
    }

    .eligibility-box {
        border-radius: 11px;
        padding: 16px;
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

    .criteria-list {
        display: grid;
        gap: 10px;
    }

    .criteria-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #DDE2D9;
        padding-bottom: 9px;
    }

    .criteria-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .criteria-row span {
        color: #6B7280;
        font-size: 12px;
    }

    .criteria-row strong {
        color: #283F24;
        font-size: 12px;
        text-align: right;
    }

    .sidebar-card {
        position: sticky;
        top: 108px;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    @media (max-width: 950px) {
        .application-layout {
            grid-template-columns: 1fr;
        }

        .sidebar-card {
            position: static;
        }
    }

    @media (max-width: 650px) {
        .information-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="application-hero">

    <h1>
        Apply for
        <?= e(
            $scholarship["title"]
        ); ?>
    </h1>

    <p>
        <?= e(
            $scholarship["provider"]
        ); ?>

        <?php if (
            !empty(
                $scholarship["deadline"]
            )
        ): ?>

            · Deadline:
            <?= date(
                "F d, Y",
                strtotime(
                    $scholarship["deadline"]
                )
            ); ?>

        <?php endif; ?>
    </p>

</div>

<div class="application-layout">

    <div>

        <?php if (!empty($errors)): ?>

            <ul class="error-list">

                <?php foreach (
                    $errors as $error
                ): ?>

                    <li>
                        <?= e($error); ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        <?php endif; ?>

        <div class="card">

            <h2 class="section-title">
                Applicant Information
            </h2>

            <div class="information-grid">

                <div class="information-item">
                    <span>Full Name</span>
                    <strong>
                        <?= e(
                            $profile["fullname"]
                        ); ?>
                    </strong>
                </div>

                <div class="information-item">
                    <span>Email</span>
                    <strong>
                        <?= e(
                            $profile["email"]
                        ); ?>
                    </strong>
                </div>

                <div class="information-item">
                    <span>Student Number</span>
                    <strong>
                        <?= !empty(
                            $profile["student_no"]
                        )
                            ? e(
                                $profile["student_no"]
                            )
                            : "Not provided"; ?>
                    </strong>
                </div>

                <div class="information-item">
                    <span>School</span>
                    <strong>
                        <?= !empty(
                            $profile["school"]
                        )
                            ? e(
                                $profile["school"]
                            )
                            : "Not provided"; ?>
                    </strong>
                </div>

                <div class="information-item">
                    <span>Course</span>
                    <strong>
                        <?= $student_course !== ""
                            ? e($student_course)
                            : "Not provided"; ?>
                    </strong>
                </div>

                <div class="information-item">
                    <span>Year Level</span>
                    <strong>
                        <?= !empty(
                            $profile["year_level"]
                        )
                            ? e(
                                $profile["year_level"]
                            )
                            : "Not provided"; ?>
                    </strong>
                </div>

                <div class="information-item">
                    <span>GWA</span>
                    <strong>
                        <?= $profile["gwa"] !== null
                            ? e(
                                (string)
                                $profile["gwa"]
                            )
                            : "Not provided"; ?>
                    </strong>
                </div>

                <div class="information-item">
                    <span>Annual Family Income</span>
                    <strong>
                        <?= $profile["annual_income"] !== null
                            ? "₱" .
                                number_format(
                                    (float)
                                    $profile["annual_income"],
                                    2
                                )
                            : "Not provided"; ?>
                    </strong>
                </div>

            </div>

            <br>

            <a
                href="profile.php"
                class="btn btn-secondary"
            >
                Update Profile
            </a>

        </div>

        <form
            method="POST"
            action="apply.php?id=<?= $scholarship_id; ?>"
            enctype="multipart/form-data"
        >

            <div class="card">

                <h2 class="section-title">
                    Documentary Requirements Checklist
                </h2>

                <?php if (
                    empty($requirements)
                ): ?>

                    <p style="color:#6B7280;line-height:1.7;">
                        The administrator has not added a detailed
                        requirements list. Verify the requirements
                        on the official provider website before submitting.
                    </p>

                <?php else: ?>

                    <div class="requirement-checklist">

                        <?php foreach (
                            $requirements
                            as $index => $requirement
                        ): ?>

                            <label class="requirement-check">

                                <input
                                    type="checkbox"
                                    name="requirement_<?= $index; ?>"
                                    value="1"
                                    <?= isset(
                                        $_POST[
                                            "requirement_" .
                                            $index
                                        ]
                                    )
                                        ? "checked"
                                        : ""; ?>
                                >

                                <span>
                                    <?= e(
                                        $requirement
                                    ); ?>
                                </span>

                            </label>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

            <div class="card">

                <h2 class="section-title">
                    Application Statement
                </h2>

                <div class="form-group">

                    <label for="application_statement">
                        Why should you be considered for this scholarship?
                    </label>

                    <textarea
                        id="application_statement"
                        name="application_statement"
                        class="form-control"
                        minlength="50"
                        placeholder="Describe your academic goals, financial need, achievements, and how the scholarship will support your education."
                        required
                    ><?= e(
                        $application_statement
                    ); ?></textarea>

                    <p style="color:#6B7280;font-size:12px;margin-top:6px;">
                        Minimum of 50 characters.
                    </p>

                </div>

            </div>

            <div class="card">

                <h2 class="section-title">
                    Upload Application Documents
                </h2>

                <div class="upload-box">

                    <h3>
                        Compiled Requirements File
                    </h3>

                    <p>
                        Combine your required documents into one PDF
                        whenever possible. JPG, JPEG, and PNG files are
                        also accepted. Maximum file size is 10 MB.
                    </p>

                    <input
                        type="file"
                        name="application_document"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png"
                        required
                    >

                </div>

            </div>

            <label class="declaration-box">

                <input
                    type="checkbox"
                    name="declaration"
                    value="1"
                    <?= $declaration
                        ? "checked"
                        : ""; ?>
                >

                <span>
                    I certify that the information and uploaded
                    documents are true and accurate. I understand
                    that the scholarship provider may request
                    additional documents or conduct further verification.
                </span>

            </label>

            <div class="form-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                    <?= !$is_eligible
                        ? "disabled"
                        : ""; ?>
                    onclick="return confirm('Submit this scholarship application?');"
                >
                    Submit Application
                </button>

                <a
                    href="scholarship_view.php?id=<?= $scholarship_id; ?>"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

    <div>

        <div class="card sidebar-card">

            <h2 class="section-title">
                Eligibility Summary
            </h2>

            <?php if ($is_eligible): ?>

                <div class="eligibility-box eligible">

                    Your completed profile meets the saved
                    basic GWA and annual-income requirements.

                </div>

            <?php else: ?>

                <div class="eligibility-box not-eligible">

                    Your profile is incomplete or does not
                    meet one or more saved basic requirements.
                    You cannot submit this application yet.

                </div>

            <?php endif; ?>

            <div class="criteria-list">

                <div class="criteria-row">
                    <span>Your GWA</span>
                    <strong>
                        <?= $profile["gwa"] !== null
                            ? e(
                                (string)
                                $profile["gwa"]
                            )
                            : "Not provided"; ?>
                    </strong>
                </div>

                <div class="criteria-row">
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

                <div class="criteria-row">
                    <span>Your Annual Income</span>
                    <strong>
                        <?= $profile["annual_income"] !== null
                            ? "₱" .
                                number_format(
                                    (float)
                                    $profile["annual_income"],
                                    2
                                )
                            : "Not provided"; ?>
                    </strong>
                </div>

                <div class="criteria-row">
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

            </div>

            <?php if (
                !empty($missing_profile_fields)
            ): ?>

                <br>

                <a
                    href="profile.php"
                    class="btn btn-gold"
                >
                    Complete Profile
                </a>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php include "footer.php"; ?>