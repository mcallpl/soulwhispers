<?php
require_once '../config.php';
require_once 'auth.php';

// Require admin login
require_admin_login();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $lyrics = trim($_POST['lyrics'] ?? '');
    $sortOrder = intval($_POST['sort_order'] ?? 0);

    // Validation
    if (empty($title)) {
        $message = 'Title is required';
        $messageType = 'error';
    } elseif (!isset($_FILES['audio'])) {
        $message = 'Audio file is required';
        $messageType = 'error';
    } elseif ($_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        // Debug upload error
        $errorCode = $_FILES['audio']['error'];
        $errorMsg = '';
        switch($errorCode) {
            case UPLOAD_ERR_INI_SIZE: $errorMsg = 'File exceeds upload_max_filesize'; break;
            case UPLOAD_ERR_FORM_SIZE: $errorMsg = 'File exceeds form MAX_FILE_SIZE'; break;
            case UPLOAD_ERR_PARTIAL: $errorMsg = 'File only partially uploaded'; break;
            case UPLOAD_ERR_NO_FILE: $errorMsg = 'No file uploaded'; break;
            case UPLOAD_ERR_NO_TMP_DIR: $errorMsg = 'Missing temp directory'; break;
            case UPLOAD_ERR_CANT_WRITE: $errorMsg = 'Failed to write file'; break;
            default: $errorMsg = 'Unknown error';
        }
        $message = 'Audio file upload error: ' . $errorMsg . ' (code: ' . $errorCode . ')';
        $messageType = 'error';
    } else {
        $audioFile = $_FILES['audio'];
        $coverFile = $_FILES['cover_image'] ?? null;

        // Validate audio file - check extension AND MIME type (for iPhone compatibility)
        $audioExt = strtolower(pathinfo($audioFile['name'], PATHINFO_EXTENSION));
        $allowedAudio = ['mp3', 'm4a', 'wav', 'aac', ''];  // Allow empty extension (iPhone)
        $allowedMime = ['audio/mpeg', 'audio/mp4', 'audio/x-m4a', 'audio/m4a', 'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/aac', 'audio/x-aac'];

        // Try to get MIME type
        $audioMime = '';
        if (function_exists('finfo_file')) {
            $audioMime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $audioFile['tmp_name']);
        } elseif (function_exists('mime_content_type')) {
            $audioMime = mime_content_type($audioFile['tmp_name']);
        }

        // Accept if extension is valid OR MIME type is valid OR no extension but appears to be audio
        $hasValidExt = in_array($audioExt, $allowedAudio);
        $hasValidMime = !empty($audioMime) && in_array($audioMime, $allowedMime);
        $couldBeAudio = (empty($audioExt) && $audioFile['size'] > 1000);  // If no ext but >1KB, likely audio

        if (!($hasValidExt || $hasValidMime || $couldBeAudio)) {
            $message = 'Audio file must be MP3, M4A, or WAV';
            $messageType = 'error';
        } else {
            // Sanitize and save audio file
            $audioFilename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($audioFile['name']));
            $audioPath = $base_path . '/uploads/audio/' . $audioFilename;

            $coverFilename = null;
            if ($coverFile && $coverFile['error'] === UPLOAD_ERR_OK) {
                $coverExt = strtolower(pathinfo($coverFile['name'], PATHINFO_EXTENSION));
                $allowedCover = ['jpg', 'jpeg', 'png'];

                if (!in_array($coverExt, $allowedCover)) {
                    $message = 'Cover image must be JPG or PNG';
                    $messageType = 'error';
                } else {
                    $coverFilename = time() . '_cover_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($coverFile['name']));
                    $coverPath = $base_path . '/uploads/covers/' . $coverFilename;

                    if (!move_uploaded_file($coverFile['tmp_name'], $coverPath)) {
                        $message = 'Failed to save cover image';
                        $messageType = 'error';
                    }
                }
            }

            // Save audio only if cover was successful (or no cover)
            if ($messageType !== 'error' && move_uploaded_file($audioFile['tmp_name'], $audioPath)) {
                // Insert into database
                $query = "INSERT INTO poems (title, subtitle, lyrics, audio_filename, cover_image, sort_order) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('sssssi', $title, $subtitle, $lyrics, $audioFilename, $coverFilename, $sortOrder);

                if ($stmt->execute()) {
                    $message = 'Poem uploaded successfully!';
                    $messageType = 'success';
                    // Clear form
                    $_POST = [];
                    // Redirect after 2 seconds
                    header('Refresh: 2; url=index.php');
                } else {
                    $message = 'Database error: ' . $conn->error;
                    $messageType = 'error';
                    // Clean up uploaded files
                    unlink($audioPath);
                    if ($coverFilename) unlink($base_path . '/uploads/covers/' . $coverFilename);
                }
                $stmt->close();
            } elseif ($messageType === '' ) {
                $message = 'Failed to save audio file';
                $messageType = 'error';
                if ($coverFilename) unlink($base_path . '/uploads/covers/' . $coverFilename);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Poem - Soul Whispers Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=EB+Garamond:wght@400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="admin-title-section">
                <a href="index.php" class="back-link">← Back to Dashboard</a>
                <h1>Upload New Poem</h1>
            </div>
        </header>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <main class="admin-main">
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <div id="generatingIndicator"></div>

                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="subtitle">Subtitle</label>
                    <input type="text" id="subtitle" name="subtitle" value="<?php echo htmlspecialchars($_POST['subtitle'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="lyrics">Lyrics</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <button type="button" id="generateLyricsBtn" class="btn-generate-lyrics">✨ Generate Lyrics from Audio</button>
                    </div>
                    <textarea id="lyrics" name="lyrics" rows="12" placeholder="Paste lyrics here. Use [MM:SS] format for timing, e.g.:&#10;[0:00] First line&#10;[0:05] Second line&#10;[0:10] Third line"><?php echo htmlspecialchars($_POST['lyrics'] ?? ''); ?></textarea>
                    <small>Optional. Use [MM:SS] format to sync lyrics with audio playback. Click "Generate Lyrics" to auto-extract from audio.</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="audio">Audio File * (MP3 from Suno)</label>

                        <div class="suno-steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-text">Go to Suno → Download your MP3</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-text">Tap the button below</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-text">Select the MP3 from Downloads</div>
                            </div>
                        </div>

                        <button type="button" class="upload-btn" id="audioButton">📱 Choose Audio File</button>
                        <input type="file" id="audio" name="audio" accept="audio/*,.mp3,.m4a,.wav" required hidden>

                        <div class="file-preview" id="audioPreview" style="display:none;">
                            <div class="preview-info">
                                <span class="preview-icon">✅</span>
                                <div class="preview-details">
                                    <p class="preview-name" id="audioFileName"></p>
                                    <p class="preview-size" id="audioFileSize"></p>
                                </div>
                            </div>
                            <button type="button" class="preview-remove" onclick="clearAudio()">Change</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cover_image">Cover Image (.jpg, .png)</label>
                        <input type="file" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png">
                        <small>Recommended: JPEG 500x500px or larger, under 500 KB.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="sort_order">Sort Order (0 = first)</label>
                    <input type="number" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($_POST['sort_order'] ?? '0'); ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Upload Poem</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </main>
    </div>

    <style>
        .suno-steps {
            background: rgba(123, 159, 212, 0.1);
            border-left: 3px solid #7b9fd4;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .step {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .step-number {
            background: #7b9fd4;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
            font-size: 1.1rem;
        }

        .step-text {
            color: #f5f5f5;
            font-size: 1rem;
            font-weight: 500;
            padding-top: 0.5rem;
            line-height: 1.4;
        }

        .upload-btn {
            width: 100%;
            background: linear-gradient(135deg, #c8a84b, #e8c47a);
            color: #1a1035;
            border: none;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(200, 168, 75, 0.3);
        }

        .upload-btn:active {
            transform: translateY(0);
        }

        .btn-generate-lyrics {
            background: linear-gradient(135deg, #6c63ff, #8b7eff);
            color: white;
            border: none;
            padding: 0.8rem 1.2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
        }

        .btn-generate-lyrics:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(108, 99, 255, 0.3);
        }

        .btn-generate-lyrics:active {
            transform: translateY(0);
        }

        .btn-generate-lyrics:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #generatingIndicator {
            display: none;
            padding: 1rem;
            background: rgba(108, 99, 255, 0.1);
            border-left: 3px solid #6c63ff;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
        }

        #generatingIndicator.show {
            display: block;
        }

        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(108, 99, 255, 0.3);
            border-top-color: #6c63ff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 0.5rem;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .file-preview {
            background: rgba(200, 168, 75, 0.15);
            border: 2px solid #c8a84b;
            border-radius: 10px;
            padding: 1.2rem;
            margin-top: 1rem;
        }

        .preview-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .preview-icon {
            font-size: 1.8rem;
        }

        .preview-details {
            flex: 1;
        }

        .preview-name {
            font-weight: 600;
            color: #f5f5f5;
            margin: 0;
            word-break: break-all;
            font-size: 0.95rem;
        }

        .preview-size {
            font-size: 0.8rem;
            color: #b0b0b0;
            margin: 0.3rem 0 0 0;
        }

        .preview-remove {
            background: #c8a84b;
            border: none;
            color: #1a1035;
            border-radius: 6px;
            padding: 0.5rem 1.2rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .preview-remove:active {
            opacity: 0.8;
        }
    </style>

    <script>
        const audioButton = document.getElementById('audioButton');
        const fileInput = document.getElementById('audio');
        const filePreview = document.getElementById('audioPreview');

        audioButton.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', updateFilePreview);

        function updateFilePreview() {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);

                document.getElementById('audioFileName').textContent = file.name;
                document.getElementById('audioFileSize').textContent = `${sizeMB} MB`;
                filePreview.style.display = 'block';
            }
        }

        function clearAudio() {
            fileInput.value = '';
            filePreview.style.display = 'none';
        }

        if (fileInput.files.length > 0) {
            updateFilePreview();
        }

        // Generate Lyrics functionality
        const generateBtn = document.getElementById('generateLyricsBtn');
        const lyricsTextarea = document.getElementById('lyrics');
        const generatingIndicator = document.getElementById('generatingIndicator');

        generateBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            // Check if audio file is selected
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Please select an audio file first');
                return;
            }

            const audioFile = fileInput.files[0];

            // Show loading state
            generateBtn.disabled = true;
            generatingIndicator.classList.add('show');
            generatingIndicator.innerHTML = '<div class="spinner"></div> Extracting lyrics from audio (this may take a few minutes)...';

            try {
                const formData = new FormData();
                formData.append('audio', audioFile);

                const response = await fetch('../api/generate-lyrics.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success && result.lyrics) {
                    // Populate the textarea with the generated lyrics
                    lyricsTextarea.value = result.lyrics;
                    generatingIndicator.classList.remove('show');
                    alert('Lyrics generated successfully! You can now submit the form.');
                } else {
                    throw new Error(result.error || 'Failed to generate lyrics');
                }
            } catch (error) {
                console.error('Error generating lyrics:', error);
                generatingIndicator.classList.remove('show');
                alert('Error generating lyrics: ' + error.message);
            } finally {
                generateBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
