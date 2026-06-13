<?php
require_once '../config.php';
require_once 'auth.php';

// Require admin login
require_admin_login();

$message = '';
$messageType = '';

// Handle logout
if (isset($_GET['logout'])) {
    logout_admin();
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['confirm'])) {
    $id = intval($_GET['delete']);

    // Get the poem to delete files
    $query = "SELECT audio_filename, cover_image FROM poems WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $poem = $result->fetch_assoc();

        // Delete audio file
        $audioPath = $base_path . '/uploads/audio/' . $poem['audio_filename'];
        if (file_exists($audioPath)) {
            unlink($audioPath);
        }

        // Delete cover image
        if ($poem['cover_image']) {
            $coverPath = $base_path . '/uploads/covers/' . $poem['cover_image'];
            if (file_exists($coverPath)) {
                unlink($coverPath);
            }
        }

        // Delete from database
        $deleteQuery = "DELETE FROM poems WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param('i', $id);
        if ($deleteStmt->execute()) {
            $message = 'Poem deleted successfully';
            $messageType = 'success';
        } else {
            $message = 'Error deleting poem: ' . $conn->error;
            $messageType = 'error';
        }
        $deleteStmt->close();
    }
    $stmt->close();
}

// Fetch all poems
$result = $conn->query("SELECT * FROM poems ORDER BY sort_order ASC, created_at DESC");
$poems = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $poems[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Soul Whispers</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=EB+Garamond:wght@400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="admin-title-section">
                <h1>Soul Whispers Admin</h1>
                <p>Manage poems and uploads</p>
            </div>
            <div class="admin-controls">
                <span class="logged-in-user">Logged in as: <strong><?php echo get_admin_username(); ?></strong></span>
                <?php if (is_super_admin()): ?>
                <a href="batch-upload.php" class="btn btn-primary">⚡ Batch Upload</a>
                <a href="analytics.php" class="btn btn-primary">📊 Analytics</a>
                <?php endif; ?>
                <a href="upload.php" class="btn btn-secondary">+ Single Poem</a>
                <a href="../index.php" class="btn btn-secondary" target="_blank">👁️ View Gallery</a>
                <a href="?logout=1" class="btn btn-logout">Logout</a>
            </div>
        </header>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <main class="admin-main">
            <?php if (empty($poems)): ?>
            <div class="empty-state">
                <p>No poems yet. <a href="upload.php">Upload your first poem</a></p>
            </div>
            <?php else: ?>
            <div class="poems-table-wrapper">
                <table class="poems-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subtitle</th>
                            <th>Audio File</th>
                            <th>Sort Order</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($poems as $poem): ?>
                        <tr>
                            <td class="cell-title"><?php echo htmlspecialchars($poem['title']); ?></td>
                            <td><?php echo htmlspecialchars($poem['subtitle'] ?? '—'); ?></td>
                            <td class="cell-filename"><?php echo htmlspecialchars($poem['audio_filename']); ?></td>
                            <td class="cell-number"><?php echo $poem['sort_order']; ?></td>
                            <td class="cell-date"><?php echo date('M d, Y', strtotime($poem['created_at'])); ?></td>
                            <td class="cell-actions">
                                <a href="edit.php?id=<?php echo $poem['id']; ?>" class="btn-small btn-edit">Edit</a>
                                <a href="?delete=<?php echo $poem['id']; ?>&confirm=1" class="btn-small btn-delete" onclick="return confirm('Delete this poem? This cannot be undone.');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
