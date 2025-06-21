<?php
// Check if form is submitted
if (isset($_POST['send_announcement'])) {
    // Database connection
    require_once "../connection/connection.php";

    // Get form data
    $title = mysqli_real_escape_string($conn, $_POST['announcement_title']);
    $message = mysqli_real_escape_string($conn, $_POST['announcement_message']);
    $target_audience = mysqli_real_escape_string($conn, $_POST['target_audience']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $sender = mysqli_real_escape_string($conn, $_POST['sender']);
    $send_date = date('Y-m-d H:i:s');

    // Create announcements table if it doesn't exist
    $check_table = "SHOW TABLES LIKE 'announcements'";
    $table_result = mysqli_query($conn, $check_table);

    if (mysqli_num_rows($table_result) == 0) {
        $create_table = "CREATE TABLE announcements (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            target_audience VARCHAR(100) NOT NULL,
            priority VARCHAR(50) NOT NULL,
            sender VARCHAR(100) NOT NULL,
            send_date DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        mysqli_query($conn, $create_table);
    }

    // Insert announcement into database
    $insert_query = "INSERT INTO announcements (title, message, target_audience, priority, sender, send_date) 
                    VALUES ('$title', '$message', '$target_audience', '$priority', '$sender', '$send_date')";

    if (mysqli_query($conn, $insert_query)) {
        $success_message = "Announcement sent successfully!";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}

// Function to get recent announcements
function getRecentAnnouncements($limit = 5)
{
    require_once "../connection/connection.php";

    $query = "SELECT * FROM announcements ORDER BY send_date DESC LIMIT $limit";
    $result = mysqli_query($conn, $query);

    $announcements = array();

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $announcements[] = $row;
        }
    }

    mysqli_close($conn);
    return $announcements;
}
?>

<!-- Announcement Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="announcementModalLabel">Create New Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="announcement_title" class="form-label">Announcement Title</label>
                        <input type="text" class="form-control" id="announcement_title" name="announcement_title"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="announcement_message" class="form-label">Message</label>
                        <textarea class="form-control" id="announcement_message" name="announcement_message" rows="6"
                            required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="target_audience" class="form-label">Target Audience</label>
                                <select class="form-select" id="target_audience" name="target_audience" required>
                                    <option value="All Students">All Students</option>
                                    <option value="Enrolled Students">Enrolled Students</option>
                                    <option value="Reserved Students">Reserved Students</option>
                                    <option value="Staff">Staff</option>
                                    <option value="All">Everyone</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="Normal">Normal</option>
                                    <option value="Important">Important</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="sender" class="form-label">Sender</label>
                        <input type="text" class="form-control" id="sender" name="sender" value="Admin" required>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="send_announcement" class="btn btn-primary">Send
                            Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Announcement List Modal -->
<div class="modal fade" id="viewAnnouncementsModal" tabindex="-1" aria-labelledby="viewAnnouncementsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="viewAnnouncementsModalLabel">Recent Announcements</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                $recent_announcements = getRecentAnnouncements();
                if (empty($recent_announcements)):
                    ?>
                    <div class="alert alert-info">No announcements found.</div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recent_announcements as $announcement): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">
                                        <?php if ($announcement['priority'] == 'Urgent'): ?>
                                            <span class="badge bg-danger">Urgent</span>
                                        <?php elseif ($announcement['priority'] == 'Important'): ?>
                                            <span class="badge bg-warning">Important</span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($announcement['title']); ?>
                                    </h5>
                                    <small><?php echo date('M d, Y h:i A', strtotime($announcement['send_date'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
                                <small>
                                    To: <?php echo htmlspecialchars($announcement['target_audience']); ?> |
                                    From: <?php echo htmlspecialchars($announcement['sender']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add these buttons to your dashboard -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Add Bootstrap if not already included
        if (typeof bootstrap === 'undefined') {
            var bootstrapCSS = document.createElement('link');
            bootstrapCSS.rel = 'stylesheet';
            bootstrapCSS.href = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css';
            document.head.appendChild(bootstrapCSS);

            var bootstrapJS = document.createElement('script');
            bootstrapJS.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';
            document.body.appendChild(bootstrapJS);
        }
    });
</script>