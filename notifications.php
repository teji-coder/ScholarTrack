<?php
require_once "config.php";

require_student();

$user_id = (int) $_SESSION["user"]["id"];
$filter = trim($_GET["filter"] ?? "");
$search = trim($_GET["search"] ?? "");

/*
|--------------------------------------------------------------------------
| Notification Actions
|--------------------------------------------------------------------------
*/

if (isset($_POST["mark_read"])) {
    $notification_id = (int) ($_POST["notification_id"] ?? 0);

    if ($notification_id > 0) {
        $statement = $pdo->prepare(
            "UPDATE notifications
             SET is_read = 1
             WHERE id = ?
             AND user_id = ?"
        );

        $statement->execute([
            $notification_id,
            $user_id
        ]);
    }

    redirect("notifications.php");
}

if (isset($_POST["mark_all_read"])) {
    $statement = $pdo->prepare(
        "UPDATE notifications
         SET is_read = 1
         WHERE user_id = ?"
    );

    $statement->execute([
        $user_id
    ]);

    flash(
        "All notifications were marked as read.",
        "success"
    );

    redirect("notifications.php");
}

if (isset($_POST["delete_notification"])) {
    $notification_id = (int) ($_POST["notification_id"] ?? 0);

    if ($notification_id > 0) {
        $statement = $pdo->prepare(
            "DELETE FROM notifications
             WHERE id = ?
             AND user_id = ?"
        );

        $statement->execute([
            $notification_id,
            $user_id
        ]);
    }

    redirect("notifications.php");
}

/*
|--------------------------------------------------------------------------
| Counts
|--------------------------------------------------------------------------
*/

$count_statement = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_count,
        SUM(is_read = 0) AS unread_count,
        SUM(is_read = 1) AS read_count
     FROM notifications
     WHERE user_id = ?"
);

$count_statement->execute([
    $user_id
]);

$counts = $count_statement->fetch();

$total_count =
    (int) ($counts["total_count"] ?? 0);

$unread_count =
    (int) ($counts["unread_count"] ?? 0);

$read_count =
    (int) ($counts["read_count"] ?? 0);

/*
|--------------------------------------------------------------------------
| Notification Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        message,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = ?
";

$params = [
    $user_id
];

if ($filter === "unread") {
    $sql .= "
        AND is_read = 0
    ";
}

if ($filter === "read") {
    $sql .= "
        AND is_read = 1
    ";
}

if ($filter === "pending") {
    $sql .= "
        AND message LIKE '%pending%'
    ";
}

if ($filter === "review") {
    $sql .= "
        AND message LIKE '%under review%'
    ";
}

if ($filter === "waitlisted") {
    $sql .= "
        AND message LIKE '%waitlist%'
    ";
}

if ($filter === "approved") {
    $sql .= "
        AND message LIKE '%approved%'
    ";
}

if ($filter === "rejected") {
    $sql .= "
        AND message LIKE '%rejected%'
    ";
}

if ($search !== "") {
    $sql .= "
        AND message LIKE ?
    ";

    $params[] =
        "%" . $search . "%";
}

$sql .= "
    ORDER BY created_at DESC
";

$notification_statement =
    $pdo->prepare($sql);

$notification_statement->execute($params);

$notifications =
    $notification_statement->fetchAll();

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function notification_type($message)
{
    $text = strtolower($message);

    if (strpos($text, "approved") !== false) {
        return "approved";
    }

    if (
        strpos($text, "rejected") !== false ||
        strpos($text, "not approved") !== false
    ) {
        return "rejected";
    }

    if (strpos($text, "waitlist") !== false) {
        return "waitlisted";
    }

    if (strpos($text, "under review") !== false) {
        return "review";
    }

    if (strpos($text, "pending") !== false) {
        return "pending";
    }

    return "general";
}

function notification_time($date)
{
    $timestamp = strtotime($date);
    $difference = time() - $timestamp;

    if ($difference < 60) {
        return "Just now";
    }

    if ($difference < 3600) {
        $minutes = floor($difference / 60);

        return $minutes .
            ($minutes === 1 ? " minute ago" : " minutes ago");
    }

    if ($difference < 86400) {
        $hours = floor($difference / 3600);

        return $hours .
            ($hours === 1 ? " hour ago" : " hours ago");
    }

    if ($difference < 172800) {
        return "Yesterday";
    }

    return date(
        "F d, Y h:i A",
        $timestamp
    );
}

$page_title = "Notifications";

include "header.php";
?>

<style>
    .notification-hero {
        background:
            linear-gradient(
                135deg,
                #283F24,
                #467235
            );
        color: #FFFFFF;
        border-radius: 18px;
        padding: 30px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        box-shadow:
            0 10px 28px
            rgba(40, 63, 36, 0.15);
    }

    .notification-hero h1 {
        color: #FFBF00;
        margin-bottom: 8px;
    }

    .notification-hero p {
        color:
            rgba(255, 255, 255, 0.86);
        line-height: 1.7;
    }

    .notification-stats {
        display: grid;
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
        gap: 15px;
        margin-bottom: 22px;
    }

    .filter-bar {
        display: grid;
        grid-template-columns:
            1fr auto;
        gap: 15px;
        align-items: end;
    }

    .filter-links {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    .filter-link {
        display: inline-block;
        padding: 8px 13px;
        border-radius: 20px;
        background: #E8ECE6;
        color: #283F24;
        font-size: 12px;
        font-weight: bold;
    }

    .filter-link:hover,
    .filter-link.active {
        background: #467235;
        color: #FFFFFF;
    }

    .notification-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .notification-card {
        border-left: 5px solid #467235;
        padding: 18px;
        background: #FFFFFF;
        border-radius: 12px;
        box-shadow:
            0 4px 14px
            rgba(40, 63, 36, 0.07);
    }

    .notification-card.unread {
        background: #FFFDF2;
    }

    .notification-card.pending {
        border-left-color: #FFBF00;
    }

    .notification-card.review {
        border-left-color: #3B82F6;
    }

    .notification-card.waitlisted {
        border-left-color: #F59E0B;
    }

    .notification-card.approved {
        border-left-color: #467235;
    }

    .notification-card.rejected {
        border-left-color: #C0392B;
    }

    .notification-content {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
    }

    .notification-message {
        color: #283F24;
        line-height: 1.7;
        margin-bottom: 8px;
    }

    .notification-time {
        color: #6B7280;
        font-size: 12px;
    }

    .notification-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 14px;
    }

    .small-button {
        padding: 8px 12px;
        font-size: 12px;
    }

    .unread-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #FFBF00;
        margin-top: 7px;
        flex-shrink: 0;
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

    @media (max-width: 800px) {
        .notification-hero {
            flex-direction: column;
            align-items: flex-start;
        }

        .filter-bar {
            grid-template-columns: 1fr;
        }

        .notification-stats {
            grid-template-columns: 1fr;
        }

        .notification-content {
            flex-direction: column;
        }
    }
</style>

<div class="notification-hero">

    <div>

        <h1>
            Notifications
        </h1>

        <p>
            View application updates, review notices,
            approval decisions, waitlist announcements,
            and other ScholarTrack messages.
        </p>

    </div>

    <?php if ($unread_count > 0): ?>

        <form method="POST">

            <button
                type="submit"
                name="mark_all_read"
                class="btn btn-gold"
            >
                Mark All as Read
            </button>

        </form>

    <?php endif; ?>

</div>

<div class="notification-stats">

    <div class="stat-card highlight">
        <h3>Total Notifications</h3>
        <div class="number">
            <?= $total_count; ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Unread</h3>
        <div class="number">
            <?= $unread_count; ?>
        </div>
    </div>

    <div class="stat-card">
        <h3>Read</h3>
        <div class="number">
            <?= $read_count; ?>
        </div>
    </div>

</div>

<div class="card">

    <form
        method="GET"
        action="notifications.php"
    >

        <div class="filter-bar">

            <div class="form-group">

                <label for="search">
                    Search Notifications
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    value="<?= e($search); ?>"
                    placeholder="Search message content"
                >

            </div>

            <div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Search
                </button>

                <a
                    href="notifications.php"
                    class="btn btn-secondary"
                >
                    Reset
                </a>

            </div>

        </div>

        <div class="filter-links">

            <?php
            $filter_options = [
                "" => "All",
                "unread" => "Unread",
                "read" => "Read",
                "pending" => "Pending",
                "review" => "Under Review",
                "waitlisted" => "Waitlisted",
                "approved" => "Approved",
                "rejected" => "Rejected"
            ];
            ?>

            <?php foreach (
                $filter_options
                as $value => $label
            ): ?>

                <a
                    href="notifications.php?filter=<?= urlencode(
                        $value
                    ); ?>"
                    class="filter-link <?= $filter === $value
                        ? "active"
                        : ""; ?>"
                >
                    <?= e($label); ?>
                </a>

            <?php endforeach; ?>

        </div>

    </form>

</div>

<?php if (empty($notifications)): ?>

    <div class="card empty-state">

        <h2>
            No notifications found
        </h2>

        <p>
            There are no notifications under the selected filter.
        </p>

        <a
            href="my_applications.php"
            class="btn btn-primary"
        >
            View My Applications
        </a>

    </div>

<?php else: ?>

    <div class="notification-list">

        <?php foreach (
            $notifications as $notification
        ): ?>

            <?php
            $type =
                notification_type(
                    $notification["message"]
                );

            $is_unread =
                (int) $notification["is_read"] === 0;
            ?>

            <div
                class="notification-card <?= e($type); ?> <?= $is_unread
                    ? "unread"
                    : ""; ?>"
            >

                <div class="notification-content">

                    <div style="display:flex;gap:12px;">

                        <?php if ($is_unread): ?>
                            <span class="unread-dot"></span>
                        <?php endif; ?>

                        <div>

                            <p class="notification-message">
                                <?= e(
                                    $notification["message"]
                                ); ?>
                            </p>

                            <span class="notification-time">
                                <?= e(
                                    notification_time(
                                        $notification["created_at"]
                                    )
                                ); ?>
                            </span>

                        </div>

                    </div>

                </div>

                <div class="notification-actions">

                    <?php if ($is_unread): ?>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="notification_id"
                                value="<?= (int) $notification["id"]; ?>"
                            >

                            <button
                                type="submit"
                                name="mark_read"
                                class="btn btn-secondary small-button"
                            >
                                Mark as Read
                            </button>

                        </form>

                    <?php endif; ?>

                    <form
                        method="POST"
                        onsubmit="return confirm('Delete this notification?');"
                    >

                        <input
                            type="hidden"
                            name="notification_id"
                            value="<?= (int) $notification["id"]; ?>"
                        >

                        <button
                            type="submit"
                            name="delete_notification"
                            class="btn btn-danger small-button"
                        >
                            Delete
                        </button>

                    </form>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php include "footer.php"; ?>