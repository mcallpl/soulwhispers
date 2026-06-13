<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$lyrics = trim($_POST['lyrics'] ?? '');

if (empty($title)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}

if (empty($lyrics)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Lyrics are required']);
    exit;
}

try {
    // Insert into database
    // Note: audio_filename will be added when user uploads audio separately
    $query = "INSERT INTO poems (title, subtitle, lyrics, audio_filename, sort_order) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $subtitle = '';
    $audioFilename = 'pending_upload';  // Placeholder - user adds audio later
    $sortOrder = 0;

    $stmt->bind_param('ssssi', $title, $subtitle, $lyrics, $audioFilename, $sortOrder);

    if (!$stmt->execute()) {
        throw new Exception('Database execute error: ' . $stmt->error);
    }

    $poemId = $conn->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'poem_id' => $poemId,
        'title' => $title
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save poem: ' . $e->getMessage()
    ]);
    exit;
}
?>
