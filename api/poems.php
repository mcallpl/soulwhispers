<?php
require_once '../config.php';

header('Content-Type: application/json');

// Get poem by ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT id, title, subtitle, lyrics, audio_filename, cover_image FROM poems WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $poem = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'poem' => $poem
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Poem not found'
        ]);
    }
    $stmt->close();
} else {
    // Return all poems
    $query = "SELECT id, title, subtitle, audio_filename, cover_image FROM poems ORDER BY sort_order ASC, created_at DESC";
    $result = $conn->query($query);
    $poems = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $poems[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'poems' => $poems
    ]);
}

$conn->close();
?>
