<?php
require_once "config.php";

require_student();

$user_id = (int) $_SESSION["user"]["id"];
$errors = [];

/* Course choices */
$courses = [
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
    "BS Tourism Management",
    "Other"
];

/* Get user and student profile */
$profile_statement = $pdo->prepare(
    "SELECT
        users.fullname,
        users.email,
        student_profiles.*
     FROM users
     LEFT JOIN student_profiles
        ON student_profiles.user_id = users.id
     WHERE users.id = ?
     LIMIT 1"
);

$profile_statement->execute([$user_id]);

$profile = $profile_statement->fetch();

if (!$profile) {
    flash("Student account was not found.", "error");
    redirect("logout.php");
}

/* Create student profile row if missing */
if (empty($profile["id"])) {
    $create_profile = $pdo->prepare(
        "INSERT INTO student_profiles (user_id)
         VALUES (?)"
    );

    $create_profile->execute([$user_id]);

    $profile_statement->execute([$user_id]);
    $profile = $profile_statement->fetch();
}

/* Get parent information */
$parent_statement = $pdo->prepare(
    "SELECT *
     FROM parent_information
     WHERE user_id = ?
     LIMIT 1"
);

$parent_statement->execute([$user_id]);

$parent = $parent_statement->fetch();

if (!$parent) {
    $parent = [
        "father_name" => "",
        "father_occupation" => "",
        "father_company" => "",
        "father_income" => "",
        "father_contact" => "",

        "mother_name" => "",
        "mother_occupation" => "",
        "mother_company" => "",
        "mother_income" => "",
        "mother_contact" => "",

        "guardian_name" => "",
        "guardian_relationship" => "",
        "guardian_contact" => ""
    ];
}

/* Form submit */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* Personal information */
    $first_name = trim($_POST["first_name"] ?? "");
    $middle_name = trim($_POST["middle_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $birthday = trim($_POST["birthday"] ?? "");
    $sex = trim($_POST["sex"] ?? "");
    $civil_status = trim($_POST["civil_status"] ?? "");
    $nationality = trim($_POST["nationality"] ?? "");

    /* Contact information */
    $email = trim($_POST["email"] ?? "");
    $contact_number = trim($_POST["contact_number"] ?? "");
    $alternate_contact = trim($_POST["alternate_contact"] ?? "");

    /* Address */
    $house_no = trim($_POST["house_no"] ?? "");
    $street = trim($_POST["street"] ?? "");
    $barangay = trim($_POST["barangay"] ?? "");
    $municipality = trim($_POST["municipality"] ?? "");
    $province = trim($_POST["province"] ?? "");
    $zipcode = trim($_POST["zipcode"] ?? "");

    /* Academic information */
    $school = trim($_POST["school"] ?? "");
    $student_no = trim($_POST["student_no"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $other_course = trim($_POST["other_course"] ?? "");
    $year_level = trim($_POST["year_level"] ?? "");
    $gwa = trim($_POST["gwa"] ?? "");
    $annual_income = trim($_POST["annual_income"] ?? "");

    /* Father's information */
    $father_name = trim($_POST["father_name"] ?? "");
    $father_occupation = trim($_POST["father_occupation"] ?? "");
    $father_company = trim($_POST["father_company"] ?? "");
    $father_income = trim($_POST["father_income"] ?? "");
    $father_contact = trim($_POST["father_contact"] ?? "");

    /* Mother's information */
    $mother_name = trim($_POST["mother_name"] ?? "");
    $mother_occupation = trim($_POST["mother_occupation"] ?? "");
    $mother_company = trim($_POST["mother_company"] ?? "");
    $mother_income = trim($_POST["mother_income"] ?? "");
    $mother_contact = trim($_POST["mother_contact"] ?? "");

    /* Guardian information */
    $guardian_name = trim($_POST["guardian_name"] ?? "");
    $guardian_relationship = trim(
        $_POST["guardian_relationship"] ?? ""
    );
    $guardian_contact = trim($_POST["guardian_contact"] ?? "");

    /* Validation */
    if ($first_name === "") {
        $errors[] = "First name is required.";
    }

    if ($last_name === "") {
        $errors[] = "Last name is required.";
    }

    if ($birthday === "") {
        $errors[] = "Birthday is required.";
    }

    if ($sex === "") {
        $errors[] = "Sex is required.";
    }

    if ($civil_status === "") {
        $errors[] = "Civil status is required.";
    }

    if ($nationality === "") {
        $errors[] = "Nationality is required.";
    }

    if ($email === "") {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if ($contact_number === "") {
        $errors[] = "Contact number is required.";
    }

    if ($barangay === "") {
        $errors[] = "Barangay is required.";
    }

    if ($municipality === "") {
        $errors[] = "Municipality or city is required.";
    }

    if ($province === "") {
        $errors[] = "Province is required.";
    }

    if ($zipcode === "") {
        $errors[] = "ZIP code is required.";
    }

    if ($school === "") {
        $errors[] = "School is required.";
    }

    if ($student_no === "") {
        $errors[] = "Student number is required.";
    }

    if ($course === "") {
        $errors[] = "Course is required.";
    }

    if ($course === "Other" && $other_course === "") {
        $errors[] = "Please specify your course.";
    }

    if ($year_level === "") {
        $errors[] = "Year level is required.";
    }

    if ($gwa === "") {
        $errors[] = "GWA is required.";
    } elseif (!is_numeric($gwa)) {
        $errors[] = "GWA must be a valid number.";
    } elseif ((float) $gwa < 1 || (float) $gwa > 5) {
        $errors[] = "GWA must be between 1.00 and 5.00.";
    }

    if ($annual_income === "") {
        $errors[] = "Annual family income is required.";
    } elseif (!is_numeric($annual_income)) {
        $errors[] = "Annual family income must be a valid number.";
    }

    /* Check duplicate email */
    if (empty($errors)) {
        $email_check = $pdo->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             AND id != ?
             LIMIT 1"
        );

        $email_check->execute([
            $email,
            $user_id
        ]);

        if ($email_check->fetch()) {
            $errors[] =
                "This email address is already used by another account.";
        }
    }

    /* Existing profile picture */
    $profile_picture = $profile["profile_picture"] ?? "default.png";

    /* Upload profile picture */
    if (
        isset($_FILES["profile_picture"]) &&
        $_FILES["profile_picture"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES["profile_picture"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "There was an error uploading the profile picture.";
        } else {
            $allowed_mime_types = [
                "image/jpeg" => "jpg",
                "image/png" => "png"
            ];

            $detected_mime = "";

            if (
                function_exists("finfo_open") &&
                is_uploaded_file($file["tmp_name"])
            ) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                if ($finfo !== false) {
                    $detected_mime = finfo_file(
                        $finfo,
                        $file["tmp_name"]
                    );

                    finfo_close($finfo);
                }
            }

            if (
                $detected_mime === "" ||
                !isset($allowed_mime_types[$detected_mime])
            ) {
                $errors[] =
                    "Profile picture must be a valid JPG, JPEG, or PNG image.";
            }

            if ($file["size"] > 3 * 1024 * 1024) {
                $errors[] =
                    "Profile picture must not exceed 3 MB.";
            }

            $extension =
                $allowed_mime_types[$detected_mime] ?? "";

            if (empty($errors)) {
                $profile_folder =
                    __DIR__ . "/profile/";

                if (!is_dir($profile_folder)) {
                    mkdir($profile_folder, 0777, true);
                }

                $new_picture_name =
                    "profile_" .
                    $user_id .
                    "_" .
                    time() .
                    "." .
                    $extension;

                $destination =
                    $profile_folder .
                    $new_picture_name;

                if (
                    move_uploaded_file(
                        $file["tmp_name"],
                        $destination
                    )
                ) {
                    /* Delete old profile picture */
                    if (
                        !empty($profile_picture) &&
                        $profile_picture !== "default.png"
                    ) {
                        $old_picture =
                            $profile_folder .
                            $profile_picture;

                        if (file_exists($old_picture)) {
                            unlink($old_picture);
                        }
                    }

                    $profile_picture =
                        $new_picture_name;
                } else {
                    $errors[] =
                        "Unable to save the profile picture.";
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            /* Build full name */
            $fullname = trim(
                $first_name .
                " " .
                $middle_name .
                " " .
                $last_name
            );

            $fullname = preg_replace(
                "/\s+/",
                " ",
                $fullname
            );

            /* Update user account */
            $update_user = $pdo->prepare(
                "UPDATE users
                 SET fullname = ?, email = ?
                 WHERE id = ?"
            );

            $update_user->execute([
                $fullname,
                $email,
                $user_id
            ]);

            /* Update student profile */
            $update_profile = $pdo->prepare(
                "UPDATE student_profiles
                 SET
                    first_name = ?,
                    middle_name = ?,
                    last_name = ?,
                    birthday = ?,
                    sex = ?,
                    civil_status = ?,
                    nationality = ?,
                    contact_number = ?,
                    alternate_contact = ?,
                    house_no = ?,
                    street = ?,
                    barangay = ?,
                    municipality = ?,
                    province = ?,
                    zipcode = ?,
                    school = ?,
                    student_no = ?,
                    course = ?,
                    other_course = ?,
                    year_level = ?,
                    gwa = ?,
                    annual_income = ?,
                    profile_picture = ?
                 WHERE user_id = ?"
            );

            $update_profile->execute([
                $first_name,
                $middle_name,
                $last_name,
                $birthday,
                $sex,
                $civil_status,
                $nationality,
                $contact_number,
                $alternate_contact,
                $house_no,
                $street,
                $barangay,
                $municipality,
                $province,
                $zipcode,
                $school,
                $student_no,
                $course,
                $other_course,
                $year_level,
                $gwa,
                $annual_income,
                $profile_picture,
                $user_id
            ]);

            /* Check existing parent information */
            $parent_check = $pdo->prepare(
                "SELECT id
                 FROM parent_information
                 WHERE user_id = ?
                 LIMIT 1"
            );

            $parent_check->execute([$user_id]);

            if ($parent_check->fetch()) {
                $update_parent = $pdo->prepare(
                    "UPDATE parent_information
                     SET
                        father_name = ?,
                        father_occupation = ?,
                        father_company = ?,
                        father_income = ?,
                        father_contact = ?,
                        mother_name = ?,
                        mother_occupation = ?,
                        mother_company = ?,
                        mother_income = ?,
                        mother_contact = ?,
                        guardian_name = ?,
                        guardian_relationship = ?,
                        guardian_contact = ?
                     WHERE user_id = ?"
                );

                $update_parent->execute([
                    $father_name,
                    $father_occupation,
                    $father_company,
                    $father_income !== ""
                        ? $father_income
                        : null,
                    $father_contact,

                    $mother_name,
                    $mother_occupation,
                    $mother_company,
                    $mother_income !== ""
                        ? $mother_income
                        : null,
                    $mother_contact,

                    $guardian_name,
                    $guardian_relationship,
                    $guardian_contact,
                    $user_id
                ]);
            } else {
                $insert_parent = $pdo->prepare(
                    "INSERT INTO parent_information
                    (
                        user_id,
                        father_name,
                        father_occupation,
                        father_company,
                        father_income,
                        father_contact,
                        mother_name,
                        mother_occupation,
                        mother_company,
                        mother_income,
                        mother_contact,
                        guardian_name,
                        guardian_relationship,
                        guardian_contact
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $insert_parent->execute([
                    $user_id,

                    $father_name,
                    $father_occupation,
                    $father_company,
                    $father_income !== ""
                        ? $father_income
                        : null,
                    $father_contact,

                    $mother_name,
                    $mother_occupation,
                    $mother_company,
                    $mother_income !== ""
                        ? $mother_income
                        : null,
                    $mother_contact,

                    $guardian_name,
                    $guardian_relationship,
                    $guardian_contact
                ]);
            }

            $pdo->commit();

            /* Update session */
            $_SESSION["user"]["fullname"] =
                $fullname;

            $_SESSION["user"]["email"] =
                $email;

            flash(
                "Your profile was updated successfully.",
                "success"
            );

            redirect("profile.php");

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                "Unable to update your profile. " .
                "Please check the database columns.";
        }
    }

    /* Keep submitted values when validation fails */
    $profile = array_merge(
        $profile,
        [
            "first_name" => $first_name,
            "middle_name" => $middle_name,
            "last_name" => $last_name,
            "birthday" => $birthday,
            "sex" => $sex,
            "civil_status" => $civil_status,
            "nationality" => $nationality,
            "email" => $email,
            "contact_number" => $contact_number,
            "alternate_contact" => $alternate_contact,
            "house_no" => $house_no,
            "street" => $street,
            "barangay" => $barangay,
            "municipality" => $municipality,
            "province" => $province,
            "zipcode" => $zipcode,
            "school" => $school,
            "student_no" => $student_no,
            "course" => $course,
            "other_course" => $other_course,
            "year_level" => $year_level,
            "gwa" => $gwa,
            "annual_income" => $annual_income,
            "profile_picture" => $profile_picture
        ]
    );

    $parent = [
        "father_name" => $father_name,
        "father_occupation" => $father_occupation,
        "father_company" => $father_company,
        "father_income" => $father_income,
        "father_contact" => $father_contact,

        "mother_name" => $mother_name,
        "mother_occupation" => $mother_occupation,
        "mother_company" => $mother_company,
        "mother_income" => $mother_income,
        "mother_contact" => $mother_contact,

        "guardian_name" => $guardian_name,
        "guardian_relationship" => $guardian_relationship,
        "guardian_contact" => $guardian_contact
    ];
}

$page_title = "My Profile";

include "header.php";
?>

<style>
    .profile-header {
        background:
            linear-gradient(
                135deg,
                #283F24,
                #467235
            );
        color: #FFFFFF;
        border-radius: 16px;
        padding: 28px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 25px;
    }

    .profile-header h1 {
        color: #FFBF00;
        margin-bottom: 8px;
    }

    .profile-header p {
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.6;
    }

    .profile-photo-preview {
        width: 115px;
        height: 115px;
        border-radius: 50%;
        border: 4px solid #FFBF00;
        background: #FFFFFF;
        object-fit: cover;
    }

    .default-photo {
        width: 115px;
        height: 115px;
        border-radius: 50%;
        border: 4px solid #FFBF00;
        background: #FFFFFF;
        color: #283F24;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 38px;
        font-weight: bold;
    }

    .form-section {
        margin-bottom: 35px;
    }

    .form-section h3 {
        color: #283F24;
        margin-bottom: 18px;
        padding-bottom: 10px;
        border-bottom: 2px solid #FFF2AD;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .full-width {
        grid-column: 1 / -1;
    }

    .error-list {
        background: #FCE7E5;
        color: #8E201A;
        border: 1px solid #E7AAA5;
        border-radius: 8px;
        padding: 14px 18px 14px 36px;
        margin-bottom: 20px;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .photo-help {
        color: #6B7280;
        font-size: 13px;
        margin-top: 7px;
    }

    @media (max-width: 700px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .full-width {
            grid-column: auto;
        }

        .profile-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="profile-header">

    <div>
        <h1>My Student Profile</h1>

        <p>
            Complete your personal, contact, academic,
            and family information for scholarship matching.
        </p>
    </div>

    <?php if (
        !empty($profile["profile_picture"]) &&
        $profile["profile_picture"] !== "default.png"
    ): ?>

        <img
            src="profile/<?= e(
                $profile["profile_picture"]
            ); ?>"
            alt="Profile Picture"
            class="profile-photo-preview"
            id="profile-photo-preview"
        >

    <?php else: ?>

        <img
            src=""
            alt="Selected Profile Picture"
            class="profile-photo-preview"
            id="profile-photo-preview"
            style="display:none;"
        >

        <div class="default-photo" id="default-photo">
            <?= e(
                strtoupper(
                    substr(
                        $_SESSION["user"]["fullname"],
                        0,
                        1
                    )
                )
            ); ?>
        </div>

    <?php endif; ?>

</div>

<div class="card">

    <?php if (!empty($errors)): ?>

        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error); ?></li>
            <?php endforeach; ?>
        </ul>

    <?php endif; ?>

    <form
        method="POST"
        action="profile.php"
        enctype="multipart/form-data"
    >

        <div class="form-section">

            <h3>Profile Picture</h3>

            <div class="form-group">
                <label for="profile_picture">
                    Change Photo
                </label>

                <input
                    type="file"
                    id="profile_picture"
                    name="profile_picture"
                    class="form-control"
                    accept="image/jpeg,image/png"
                    onchange="previewProfilePicture(event)"
                >

                <p class="photo-help">
                    Allowed: JPG, JPEG, PNG. Maximum size: 3 MB.
                </p>
            </div>

        </div>

        <div class="form-section">

            <h3>Personal Information</h3>

            <div class="form-grid">

                <div class="form-group">
                    <label>First Name</label>

                    <input
                        type="text"
                        name="first_name"
                        class="form-control"
                        value="<?= e(
                            $profile["first_name"] ?? ""
                        ); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Middle Name</label>

                    <input
                        type="text"
                        name="middle_name"
                        class="form-control"
                        value="<?= e(
                            $profile["middle_name"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Last Name</label>

                    <input
                        type="text"
                        name="last_name"
                        class="form-control"
                        value="<?= e(
                            $profile["last_name"] ?? ""
                        ); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Birthday</label>

                    <input
                        type="date"
                        id="birthday"
                        name="birthday"
                        class="form-control"
                        value="<?= e(
                            $profile["birthday"] ?? ""
                        ); ?>"
                        onchange="calculateAge()"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Age</label>

                    <input
                        type="text"
                        id="age"
                        class="form-control"
                        readonly
                    >
                </div>

                <div class="form-group">
                    <label>Sex</label>

                    <select
                        name="sex"
                        class="form-control"
                        required
                    >
                        <option value="">
                            Select sex
                        </option>

                        <?php
                        $sex_options = [
                            "Male",
                            "Female",
                            "Prefer not to say"
                        ];
                        ?>

                        <?php foreach ($sex_options as $option): ?>

                            <option
                                value="<?= e($option); ?>"
                                <?= ($profile["sex"] ?? "") === $option
                                    ? "selected"
                                    : ""; ?>
                            >
                                <?= e($option); ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Civil Status</label>

                    <select
                        name="civil_status"
                        class="form-control"
                        required
                    >
                        <option value="">
                            Select civil status
                        </option>

                        <?php
                        $civil_options = [
                            "Single",
                            "Married",
                            "Widowed",
                            "Separated"
                        ];
                        ?>

                        <?php foreach ($civil_options as $option): ?>

                            <option
                                value="<?= e($option); ?>"
                                <?= ($profile["civil_status"] ?? "") === $option
                                    ? "selected"
                                    : ""; ?>
                            >
                                <?= e($option); ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nationality</label>

                    <input
                        type="text"
                        name="nationality"
                        class="form-control"
                        value="<?= e(
                            $profile["nationality"] ??
                            "Filipino"
                        ); ?>"
                        required
                    >
                </div>

            </div>

        </div>

        <div class="form-section">

            <h3>Contact Information</h3>

            <div class="form-grid">

                <div class="form-group">
                    <label>Email Address</label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?= e($profile["email"]); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Contact Number</label>

                    <input
                        type="text"
                        name="contact_number"
                        class="form-control"
                        value="<?= e(
                            $profile["contact_number"] ?? ""
                        ); ?>"
                        placeholder="09XXXXXXXXX"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>
                        Alternate Contact Number
                    </label>

                    <input
                        type="text"
                        name="alternate_contact"
                        class="form-control"
                        value="<?= e(
                            $profile["alternate_contact"] ?? ""
                        ); ?>"
                    >
                </div>

            </div>

        </div>

        <div class="form-section">

            <h3>Complete Address</h3>

            <div class="form-grid">

                <div class="form-group">
                    <label>House Number</label>

                    <input
                        type="text"
                        name="house_no"
                        class="form-control"
                        value="<?= e(
                            $profile["house_no"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Street</label>

                    <input
                        type="text"
                        name="street"
                        class="form-control"
                        value="<?= e(
                            $profile["street"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Barangay</label>

                    <input
                        type="text"
                        name="barangay"
                        class="form-control"
                        value="<?= e(
                            $profile["barangay"] ?? ""
                        ); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Municipality / City</label>

                    <input
                        type="text"
                        name="municipality"
                        class="form-control"
                        value="<?= e(
                            $profile["municipality"] ?? ""
                        ); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Province</label>

                    <input
                        type="text"
                        name="province"
                        class="form-control"
                        value="<?= e(
                            $profile["province"] ?? ""
                        ); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>ZIP Code</label>

                    <input
                        type="text"
                        name="zipcode"
                        class="form-control"
                        value="<?= e(
                            $profile["zipcode"] ?? ""
                        ); ?>"
                        required
                    >
                </div>

            </div>

        </div>

        <div class="form-section">

            <h3>Academic Information</h3>

            <div class="form-grid">

                <div class="form-group full-width">
                    <label>School</label>

                    <input
                        type="text"
                        name="school"
                        class="form-control"
                        value="<?= e(
                            $profile["school"] ?? ""
                        ); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Student Number</label>

                    <input
                        type="text"
                        name="student_no"
                        class="form-control"
                        value="<?= e(
                            $profile["student_no"] ?? ""
                        ); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Year Level</label>

                    <select
                        name="year_level"
                        class="form-control"
                        required
                    >
                        <option value="">
                            Select year level
                        </option>

                        <?php
                        $year_levels = [
                            "1st Year",
                            "2nd Year",
                            "3rd Year",
                            "4th Year",
                            "5th Year"
                        ];
                        ?>

                        <?php foreach ($year_levels as $year): ?>

                            <option
                                value="<?= e($year); ?>"
                                <?= ($profile["year_level"] ?? "") === $year
                                    ? "selected"
                                    : ""; ?>
                            >
                                <?= e($year); ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Course or Program</label>

                    <select
                        id="course"
                        name="course"
                        class="form-control"
                        onchange="toggleOtherCourse()"
                        required
                    >
                        <option value="">
                            Select course
                        </option>

                        <?php foreach ($courses as $course_item): ?>

                            <option
                                value="<?= e($course_item); ?>"
                                <?= ($profile["course"] ?? "") ===
                                    $course_item
                                    ? "selected"
                                    : ""; ?>
                            >
                                <?= e($course_item); ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div
                    class="form-group full-width"
                    id="other-course-group"
                    style="display:none;"
                >
                    <label>Specify Course</label>

                    <input
                        type="text"
                        name="other_course"
                        class="form-control"
                        value="<?= e(
                            $profile["other_course"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>General Weighted Average</label>

                    <input
                        type="number"
                        name="gwa"
                        class="form-control"
                        min="1"
                        max="5"
                        step="0.01"
                        value="<?= e(
                            (string) (
                                $profile["gwa"] ?? ""
                            )
                        ); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Annual Family Income</label>

                    <input
                        type="number"
                        name="annual_income"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="<?= e(
                            (string) (
                                $profile["annual_income"] ?? ""
                            )
                        ); ?>"
                        required
                    >
                </div>

            </div>

        </div>

        <div class="form-section">

            <h3>Father's Information</h3>

            <div class="form-grid">

                <div class="form-group">
                    <label>Father's Name</label>

                    <input
                        type="text"
                        name="father_name"
                        class="form-control"
                        value="<?= e(
                            $parent["father_name"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Occupation</label>

                    <input
                        type="text"
                        name="father_occupation"
                        class="form-control"
                        value="<?= e(
                            $parent["father_occupation"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Company</label>

                    <input
                        type="text"
                        name="father_company"
                        class="form-control"
                        value="<?= e(
                            $parent["father_company"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Monthly Income</label>

                    <input
                        type="number"
                        name="father_income"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="<?= e(
                            (string) (
                                $parent["father_income"] ?? ""
                            )
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Contact Number</label>

                    <input
                        type="text"
                        name="father_contact"
                        class="form-control"
                        value="<?= e(
                            $parent["father_contact"] ?? ""
                        ); ?>"
                    >
                </div>

            </div>

        </div>

        <div class="form-section">

            <h3>Mother's Information</h3>

            <div class="form-grid">

                <div class="form-group">
                    <label>Mother's Name</label>

                    <input
                        type="text"
                        name="mother_name"
                        class="form-control"
                        value="<?= e(
                            $parent["mother_name"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Occupation</label>

                    <input
                        type="text"
                        name="mother_occupation"
                        class="form-control"
                        value="<?= e(
                            $parent["mother_occupation"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Company</label>

                    <input
                        type="text"
                        name="mother_company"
                        class="form-control"
                        value="<?= e(
                            $parent["mother_company"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Monthly Income</label>

                    <input
                        type="number"
                        name="mother_income"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="<?= e(
                            (string) (
                                $parent["mother_income"] ?? ""
                            )
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Contact Number</label>

                    <input
                        type="text"
                        name="mother_contact"
                        class="form-control"
                        value="<?= e(
                            $parent["mother_contact"] ?? ""
                        ); ?>"
                    >
                </div>

            </div>

        </div>

        <div class="form-section">

            <h3>Guardian Information (Optional)</h3>

            <div class="form-grid">

                <div class="form-group">
                    <label>Guardian's Name</label>

                    <input
                        type="text"
                        name="guardian_name"
                        class="form-control"
                        value="<?= e(
                            $parent["guardian_name"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Relationship</label>

                    <input
                        type="text"
                        name="guardian_relationship"
                        class="form-control"
                        value="<?= e(
                            $parent["guardian_relationship"] ?? ""
                        ); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Contact Number</label>

                    <input
                        type="text"
                        name="guardian_contact"
                        class="form-control"
                        value="<?= e(
                            $parent["guardian_contact"] ?? ""
                        ); ?>"
                    >
                </div>

            </div>

        </div>

        <div class="form-actions">

            <button
                type="submit"
                class="btn btn-primary"
            >
                Save Profile
            </button>

            <a
                href="student_dashboard.php"
                class="btn btn-secondary"
            >
                Back to Dashboard
            </a>

        </div>

    </form>

</div>

<script>
    function previewProfilePicture(event) {
        const file = event.target.files[0];

        if (!file) {
            return;
        }

        const allowedTypes = [
            "image/jpeg",
            "image/png"
        ];

        if (!allowedTypes.includes(file.type)) {
            alert("Please choose a valid JPG, JPEG, or PNG image.");
            event.target.value = "";
            return;
        }

        if (file.size > 3 * 1024 * 1024) {
            alert("Profile picture must not exceed 3 MB.");
            event.target.value = "";
            return;
        }

        const preview =
            document.getElementById("profile-photo-preview");

        const defaultPhoto =
            document.getElementById("default-photo");

        preview.src = URL.createObjectURL(file);
        preview.style.display = "block";

        if (defaultPhoto) {
            defaultPhoto.style.display = "none";
        }
    }

    function calculateAge() {
        const birthdayInput =
            document.getElementById("birthday");

        const ageInput =
            document.getElementById("age");

        if (!birthdayInput.value) {
            ageInput.value = "";
            return;
        }

        const birthday =
            new Date(birthdayInput.value);

        const today =
            new Date();

        let age =
            today.getFullYear() -
            birthday.getFullYear();

        const monthDifference =
            today.getMonth() -
            birthday.getMonth();

        if (
            monthDifference < 0 ||
            (
                monthDifference === 0 &&
                today.getDate() <
                birthday.getDate()
            )
        ) {
            age--;
        }

        ageInput.value =
            age >= 0 ? age : "";
    }

    function toggleOtherCourse() {
        const course =
            document.getElementById("course").value;

        const otherGroup =
            document.getElementById(
                "other-course-group"
            );

        if (course === "Other") {
            otherGroup.style.display =
                "block";
        } else {
            otherGroup.style.display =
                "none";
        }
    }

    calculateAge();
    toggleOtherCourse();
</script>

<?php include "footer.php"; ?>