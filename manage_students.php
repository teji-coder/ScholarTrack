<?php
require_once "config.php";

require_admin();

$search = trim($_GET["search"] ?? "");

/*
|--------------------------------------------------------------------------
| Delete Student Account
|--------------------------------------------------------------------------
*/

if (isset($_POST["delete_student"])) {
    $student_id = (int) ($_POST["student_id"] ?? 0);

    if ($student_id <= 0) {
        flash(
            "Invalid student account selected.",
            "error"
        );

        redirect("manage_students.php");
    }

    $student_check = $pdo->prepare(
        "SELECT id, fullname
         FROM users
         WHERE id = ?
         AND role = 'student'
         LIMIT 1"
    );

    $student_check->execute([
        $student_id
    ]);

    $student_record = $student_check->fetch();

    if (!$student_record) {
        flash(
            "Student account was not found.",
            "error"
        );

        redirect("manage_students.php");
    }

    $application_check = $pdo->prepare(
        "SELECT COUNT(*)
         FROM applications
         WHERE student_id = ?"
    );

    $application_check->execute([
        $student_id
    ]);

    $application_count =
        (int) $application_check->fetchColumn();

    if ($application_count > 0) {
        flash(
            "This student cannot be deleted because the account already has scholarship applications.",
            "error"
        );

        redirect("manage_students.php");
    }

    try {
        $pdo->beginTransaction();

        $delete_notifications = $pdo->prepare(
            "DELETE FROM notifications
             WHERE user_id = ?"
        );

        $delete_notifications->execute([
            $student_id
        ]);

        $delete_parent = $pdo->prepare(
            "DELETE FROM parent_information
             WHERE user_id = ?"
        );

        $delete_parent->execute([
            $student_id
        ]);

        $delete_profile = $pdo->prepare(
            "DELETE FROM student_profiles
             WHERE user_id = ?"
        );

        $delete_profile->execute([
            $student_id
        ]);

        $delete_user = $pdo->prepare(
            "DELETE FROM users
             WHERE id = ?
             AND role = 'student'"
        );

        $delete_user->execute([
            $student_id
        ]);

        $pdo->commit();

        flash(
            "Student account deleted successfully.",
            "success"
        );

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash(
            "Unable to delete the student account.",
            "error"
        );
    }

    redirect("manage_students.php");
}

/*
|--------------------------------------------------------------------------
| Student Statistics
|--------------------------------------------------------------------------
*/

$total_students = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM users
     WHERE role = 'student'"
)->fetchColumn();

$complete_profiles = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM users
     INNER JOIN student_profiles
        ON student_profiles.user_id = users.id
     WHERE users.role = 'student'
     AND COALESCE(student_profiles.student_no, '') != ''
     AND COALESCE(student_profiles.course, '') != ''
     AND COALESCE(student_profiles.year_level, '') != ''
     AND student_profiles.gwa IS NOT NULL
     AND student_profiles.annual_income IS NOT NULL
     AND COALESCE(student_profiles.barangay, '') != ''
     AND COALESCE(student_profiles.municipality, '') != ''
     AND COALESCE(student_profiles.province, '') != ''
     AND COALESCE(student_profiles.zipcode, '') != ''"
)->fetchColumn();

$incomplete_profiles =
    max(0, $total_students - $complete_profiles);

$students_with_applications = (int) $pdo->query(
    "SELECT COUNT(DISTINCT student_id)
     FROM applications"
)->fetchColumn();

/*
|--------------------------------------------------------------------------
| Dynamic Student Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        users.id,
        users.fullname,
        users.email,
        users.created_at,

        student_profiles.student_no,
        student_profiles.school,
        student_profiles.course,
        student_profiles.other_course,
        student_profiles.year_level,
        student_profiles.gwa,
        student_profiles.annual_income,
        student_profiles.contact_number,
        student_profiles.profile_picture,

        CONCAT_WS(
            ', ',
            NULLIF(student_profiles.house_no, ''),
            NULLIF(student_profiles.street, ''),
            NULLIF(student_profiles.barangay, ''),
            NULLIF(student_profiles.municipality, ''),
            NULLIF(student_profiles.province, ''),
            NULLIF(student_profiles.zipcode, '')
        ) AS address,

        COUNT(applications.id) AS application_count,

        COALESCE(SUM(applications.status = 'Pending'), 0)
            AS pending_count,

        COALESCE(SUM(applications.status = 'Under Review'), 0)
            AS review_count,

        COALESCE(SUM(applications.status = 'Waitlisted'), 0)
            AS waitlisted_count,

        COALESCE(SUM(applications.status = 'Approved'), 0)
            AS approved_count,

        COALESCE(SUM(applications.status = 'Rejected'), 0)
            AS rejected_count

    FROM users

    LEFT JOIN student_profiles
        ON student_profiles.user_id = users.id

    LEFT JOIN applications
        ON applications.student_id = users.id

    WHERE users.role = 'student'
";

$params = [];

if ($search !== "") {
    $sql .= "
        AND (
            users.fullname LIKE ?
            OR users.email LIKE ?
            OR student_profiles.student_no LIKE ?
            OR student_profiles.school LIKE ?
            OR student_profiles.course LIKE ?
            OR student_profiles.other_course LIKE ?
            OR student_profiles.year_level LIKE ?
            OR student_profiles.contact_number LIKE ?
            OR CONCAT_WS(
                ' ',
                student_profiles.house_no,
                student_profiles.street,
                student_profiles.barangay,
                student_profiles.municipality,
                student_profiles.province,
                student_profiles.zipcode
            ) LIKE ?
        )
    ";

    $search_term = "%" . $search . "%";

    for ($i = 0; $i < 9; $i++) {
        $params[] = $search_term;
    }
}

$sql .= "
    GROUP BY
        users.id,
        users.fullname,
        users.email,
        users.created_at,
        student_profiles.student_no,
        student_profiles.school,
        student_profiles.course,
        student_profiles.other_course,
        student_profiles.year_level,
        student_profiles.gwa,
        student_profiles.annual_income,
        student_profiles.contact_number,
        student_profiles.profile_picture,
        student_profiles.house_no,
        student_profiles.street,
        student_profiles.barangay,
        student_profiles.municipality,
        student_profiles.province,
        student_profiles.zipcode

    ORDER BY users.created_at DESC
";

$student_statement = $pdo->prepare($sql);

$student_statement->execute($params);

$students = $student_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function student_course_display($student)
{
    if (
        ($student["course"] ?? "") === "Other" &&
        !empty($student["other_course"])
    ) {
        return $student["other_course"];
    }

    return $student["course"] ?? "";
}

$page_title = "Manage Students";

include "header.php";
?>

<style>
    .students-header {
        background:
            linear-gradient(
                135deg,
                #283F24,
                #467235
            );
        color: #FFFFFF;
        padding: 28px;
        border-radius: 14px;
        margin-bottom: 24px;
    }

    .students-header h1 {
        color: #FFBF00;
        margin-bottom: 8px;
    }

    .students-header p {
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.6;
    }

    .student-search-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 10px;
        align-items: end;
    }

    .student-list {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .student-card {
        border-left: 5px solid #467235;
    }

    .student-card.incomplete {
        border-left-color: #FFBF00;
    }

    .student-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 18px;
    }

    .student-identity {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .student-photo,
    .student-avatar {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        border: 2px solid #FFBF00;
        background: #FFFFFF;
        object-fit: cover;
        flex-shrink: 0;
    }

    .student-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #467235;
        font-weight: bold;
        font-size: 20px;
    }

    .student-name {
        color: #283F24;
        margin-bottom: 5px;
    }

    .student-email {
        color: #467235;
        font-weight: bold;
    }

    .student-information-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        background: #F7F9F6;
        padding: 16px;
        border-radius: 9px;
        margin-bottom: 18px;
    }

    .student-detail span {
        display: block;
        color: #6B7280;
        font-size: 12px;
        margin-bottom: 5px;
    }

    .student-detail strong {
        color: #283F24;
        font-size: 14px;
        word-break: break-word;
    }

    .profile-status {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
    }

    .profile-complete {
        background: #DCEFD6;
        color: #28651F;
    }

    .profile-incomplete {
        background: #FFF2BD;
        color: #775800;
    }

    .application-summary-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 12px;
        margin-bottom: 18px;
    }

    .application-summary-box {
        text-align: center;
        border: 1px solid #DDE2D9;
        border-radius: 8px;
        padding: 13px;
    }

    .application-summary-box strong {
        display: block;
        color: #283F24;
        font-size: 22px;
        margin-bottom: 4px;
    }

    .application-summary-box span {
        color: #6B7280;
        font-size: 12px;
    }

    .student-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .inline-form {
        display: inline;
    }

    @media (max-width: 1150px) {
        .student-information-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .application-summary-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 700px) {
        .student-search-row {
            grid-template-columns: 1fr;
        }

        .student-card-header {
            flex-direction: column;
        }

        .student-information-grid,
        .application-summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="students-header">

    <h1>Manage Students</h1>

    <p>
        Review registered student accounts, academic profiles,
        financial information, and scholarship application activity.
    </p>

</div>

<div class="grid grid-4">

    <div class="stat-card highlight">
        <h3>Total Students</h3>

        <div class="number">
            <?= $total_students; ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Complete Profiles</h3>

        <div class="number">
            <?= $complete_profiles; ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Incomplete Profiles</h3>

        <div class="number">
            <?= $incomplete_profiles; ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Students with Applications</h3>

        <div class="number">
            <?= $students_with_applications; ?>
        </div>
    </div>

</div>

<br>

<div class="card">

    <form
        method="GET"
        action="manage_students.php"
    >

        <div class="student-search-row">

            <div class="form-group">
                <label for="search">
                    Search Students
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    value="<?= e($search); ?>"
                    placeholder="Search by name, email, school, number, course, contact, year, or address"
                >
            </div>

            <div class="student-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Search
                </button>

                <a
                    href="manage_students.php"
                    class="btn btn-secondary"
                >
                    Reset
                </a>

            </div>

        </div>

    </form>

</div>

<p style="color:#6B7280; margin-bottom:18px;">

    <?= count($students); ?>

    student<?= count($students) === 1 ? "" : "s"; ?>

    found.

</p>

<?php if (empty($students)): ?>

    <div class="card">

        <p class="empty-message">
            No student accounts match your search.
        </p>

    </div>

<?php else: ?>

    <div class="student-list">

        <?php foreach ($students as $student): ?>

            <?php
            $course =
                student_course_display($student);

            $is_profile_complete =
                !empty($student["student_no"]) &&
                !empty($course) &&
                !empty($student["year_level"]) &&
                $student["gwa"] !== null &&
                $student["annual_income"] !== null &&
                !empty($student["address"]);

            $application_count =
                (int) ($student["application_count"] ?? 0);

            $pending_count =
                (int) ($student["pending_count"] ?? 0);

            $review_count =
                (int) ($student["review_count"] ?? 0);

            $waitlisted_count =
                (int) ($student["waitlisted_count"] ?? 0);

            $approved_count =
                (int) ($student["approved_count"] ?? 0);

            $rejected_count =
                (int) ($student["rejected_count"] ?? 0);

            $profile_picture_name =
                basename(
                    (string) (
                        $student["profile_picture"] ?? ""
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

            /*
             * Fallback: find the newest uploaded image
             * matching this student's user ID.
             */
            if ($profile_picture_url === "") {
                $student_id_for_picture =
                    (int) ($student["id"] ?? 0);

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
                    $student["fullname"],
                    0,
                    1
                )
            );
            ?>

            <div
                class="card student-card <?= $is_profile_complete
                    ? ""
                    : "incomplete"; ?>"
            >

                <div class="student-card-header">

                    <div class="student-identity">

                        <?php if ($profile_picture_url !== ""): ?>

                            <img
                                src="<?= e(
                                    $profile_picture_url
                                ); ?>?v=<?= time(); ?>"
                                alt="Student Profile"
                                class="student-photo"
                                onerror="
                                    this.style.display='none';
                                    this.nextElementSibling.style.display='flex';
                                "
                            >

                            <div
                                class="student-avatar"
                                style="display:none;"
                            >
                                <?= e($initial); ?>
                            </div>

                        <?php else: ?>

                            <div class="student-avatar">
                                <?= e($initial); ?>
                            </div>

                        <?php endif; ?>

                        <div>

                            <h3 class="student-name">
                                <?= e($student["fullname"]); ?>
                            </h3>

                            <p class="student-email">
                                <?= e($student["email"]); ?>
                            </p>

                        </div>

                    </div>

                    <?php if ($is_profile_complete): ?>

                        <span class="profile-status profile-complete">
                            Complete Profile
                        </span>

                    <?php else: ?>

                        <span class="profile-status profile-incomplete">
                            Incomplete Profile
                        </span>

                    <?php endif; ?>

                </div>

                <div class="student-information-grid">

                    <div class="student-detail">
                        <span>Student Number</span>

                        <strong>
                            <?= !empty($student["student_no"])
                                ? e($student["student_no"])
                                : "Not provided"; ?>
                        </strong>
                    </div>

                    <div class="student-detail">
                        <span>School</span>

                        <strong>
                            <?= !empty($student["school"])
                                ? e($student["school"])
                                : "Not provided"; ?>
                        </strong>
                    </div>

                    <div class="student-detail">
                        <span>Course</span>

                        <strong>
                            <?= $course !== ""
                                ? e($course)
                                : "Not provided"; ?>
                        </strong>
                    </div>

                    <div class="student-detail">
                        <span>Year Level</span>

                        <strong>
                            <?= !empty($student["year_level"])
                                ? e($student["year_level"])
                                : "Not provided"; ?>
                        </strong>
                    </div>

                    <div class="student-detail">
                        <span>GWA</span>

                        <strong>
                            <?= $student["gwa"] !== null
                                ? e((string) $student["gwa"])
                                : "Not provided"; ?>
                        </strong>
                    </div>

                    <div class="student-detail">
                        <span>Annual Family Income</span>

                        <strong>
                            <?= $student["annual_income"] !== null
                                ? "₱" . number_format(
                                    (float) $student["annual_income"],
                                    2
                                )
                                : "Not provided"; ?>
                        </strong>
                    </div>

                    <div class="student-detail">
                        <span>Contact Number</span>

                        <strong>
                            <?= !empty($student["contact_number"])
                                ? e($student["contact_number"])
                                : "Not provided"; ?>
                        </strong>
                    </div>

                    <div class="student-detail">
                        <span>Date Registered</span>

                        <strong>
                            <?= date(
                                "F d, Y",
                                strtotime($student["created_at"])
                            ); ?>
                        </strong>
                    </div>

                    <div
                        class="student-detail"
                        style="grid-column: 1 / -1;"
                    >
                        <span>Address</span>

                        <strong>
                            <?= !empty($student["address"])
                                ? e($student["address"])
                                : "Not provided"; ?>
                        </strong>
                    </div>

                </div>

                <div class="application-summary-grid">

                    <div class="application-summary-box">
                        <strong><?= $application_count; ?></strong>
                        <span>Total</span>
                    </div>

                    <div class="application-summary-box">
                        <strong><?= $pending_count; ?></strong>
                        <span>Pending</span>
                    </div>

                    <div class="application-summary-box">
                        <strong><?= $review_count; ?></strong>
                        <span>Under Review</span>
                    </div>

                    <div class="application-summary-box">
                        <strong><?= $waitlisted_count; ?></strong>
                        <span>Waitlisted</span>
                    </div>

                    <div class="application-summary-box">
                        <strong><?= $approved_count; ?></strong>
                        <span>Approved</span>
                    </div>

                    <div class="application-summary-box">
                        <strong><?= $rejected_count; ?></strong>
                        <span>Rejected</span>
                    </div>

                </div>

                <div class="student-actions">

                    <?php if ($application_count > 0): ?>

                        <a
                            href="manage_applications.php?search=<?= urlencode(
                                $student["email"]
                            ); ?>"
                            class="btn btn-primary"
                        >
                            View Applications
                        </a>

                    <?php endif; ?>

                    <?php if ($application_count === 0): ?>

                        <form
                            method="POST"
                            action="manage_students.php"
                            class="inline-form"
                            onsubmit="return confirm('Are you sure you want to delete this student account?');"
                        >

                            <input
                                type="hidden"
                                name="student_id"
                                value="<?= (int) $student["id"]; ?>"
                            >

                            <button
                                type="submit"
                                name="delete_student"
                                class="btn btn-danger"
                            >
                                Delete Account
                            </button>

                        </form>

                    <?php else: ?>

                        <span
                            class="btn btn-secondary"
                            title="Accounts with applications cannot be deleted."
                        >
                            Cannot Delete
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php include "footer.php"; ?>