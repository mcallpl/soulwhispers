<?php
require_once '../config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No audio file provided']);
    exit;
}

try {
    $audioFile = $_FILES['audio'];

    // Validate file
    $ext = strtolower(pathinfo($audioFile['name'], PATHINFO_EXTENSION));
    $allowedExts = ['mp3', 'mp4', 'm4a', 'wav', 'mov', 'webm', 'aac'];

    if (!in_array($ext, $allowedExts)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid audio format']);
        exit;
    }

    // Create temp directory for processing
    $tmpDir = sys_get_temp_dir() . '/soulwhispers_lyrics_' . uniqid();
    mkdir($tmpDir, 0700, true);

    // Save uploaded file
    $tmpAudioPath = $tmpDir . '/audio.' . $ext;
    if (!move_uploaded_file($audioFile['tmp_name'], $tmpAudioPath)) {
        rmdir($tmpDir);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save audio file']);
        exit;
    }

    // Run Whisper with VTT output (includes timestamps)
    $outputFormat = 'vtt';
    $python3Path = trim(shell_exec('which python3 2>/dev/null') ?: '/usr/bin/python3');
    $cacheDir = sys_get_temp_dir() . '/.whisper_cache';
    @mkdir($cacheDir, 0755, true);

    // Build command with proper escaping - don't use escapeshellcmd on the entire string
    // Set XDG_CACHE_HOME to allow Whisper to cache models in /tmp
    $whisperCmd = sprintf(
        'XDG_CACHE_HOME=%s %s -m whisper %s --model tiny --language English --output_format %s -o %s --device cpu --no_speech_threshold 0.1',
        escapeshellarg($cacheDir),
        escapeshellarg($python3Path),
        escapeshellarg($tmpAudioPath),
        escapeshellarg($outputFormat),
        escapeshellarg($tmpDir)
    );

    $output = [];
    $returnCode = 0;
    exec($whisperCmd . ' 2>&1', $output, $returnCode);

    // Check if Whisper succeeded
    $outputFile = $tmpDir . '/audio.' . $outputFormat;
    if ($returnCode !== 0 || !file_exists($outputFile)) {
        // Clean up
        array_map('unlink', glob("$tmpDir/*"));
        if (is_dir($tmpDir)) {
            rmdir($tmpDir);
        }

        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Transcription failed. Please try again.']);
        exit;
    }

    // Read the VTT output
    $vttContent = file_get_contents($outputFile);

    // Convert VTT to LRC format
    $lrcContent = convertVttToLrc($vttContent);

    // Clean up temp files
    array_map('unlink', glob("$tmpDir/*"));
    rmdir($tmpDir);

    if (empty($lrcContent)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No speech detected in audio']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'lyrics' => $lrcContent
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    exit;
}

/**
 * Convert VTT format to LRC format
 * VTT: 00:00.000 --> 00:05.000  (MM:SS.ms)
 *      First line of text
 * Also handles: 00:00:01.000 --> 00:00:05.000 (HH:MM:SS.ms)
 * LRC: [0:00] First line of text
 */
function convertVttToLrc($vttContent) {
    $lines = explode("\n", $vttContent);
    $lrcLines = [];

    $currentTimestamp = null;
    $currentText = '';

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip WEBVTT header, empty lines, and NOTE lines
        if ($line === 'WEBVTT' || $line === '' || strpos($line, 'NOTE') === 0) {
            continue;
        }

        // Check if this is a timestamp line (MM:SS.ms or HH:MM:SS.ms format)
        if (preg_match('/\d{1,2}:\d{2}(?::\d{2})?\.\d{3}\s*-->\s*/', $line)) {
            // Save previous text if exists
            if (!empty($currentText) && $currentTimestamp !== null) {
                $lrcLines[] = $currentTimestamp . ' ' . trim($currentText);
                $currentText = '';
            }

            // Extract start time
            if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?\.\d{3}/', $line, $matches)) {
                // Format: MM:SS.ms or HH:MM:SS.ms
                if (isset($matches[3])) {
                    // HH:MM:SS format
                    $hours = intval($matches[1]);
                    $minutes = intval($matches[2]);
                    $seconds = intval($matches[3]);
                } else {
                    // MM:SS format
                    $hours = 0;
                    $minutes = intval($matches[1]);
                    $seconds = intval($matches[2]);
                }

                // Convert to LRC format [M:SS]
                $totalSeconds = $hours * 3600 + $minutes * 60 + $seconds;
                $lrcMinutes = intval($totalSeconds / 60);
                $lrcSeconds = $totalSeconds % 60;
                $currentTimestamp = sprintf('[%d:%02d]', $lrcMinutes, $lrcSeconds);
            }
        } else if (!empty($line) && $currentTimestamp !== null) {
            // This is text content
            if (!empty($currentText)) {
                $currentText .= ' ';
            }
            $currentText .= $line;
        }
    }

    // Don't forget the last entry
    if (!empty($currentText) && $currentTimestamp !== null) {
        $lrcLines[] = $currentTimestamp . ' ' . trim($currentText);
    }

    return implode("\n", $lrcLines);
}
?>
