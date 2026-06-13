<?php
require_once '../config.php';
require_once 'auth.php';

// Require admin login
require_admin_login();

$message = '';
$messageType = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Upload Poems - Soul Whispers Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=EB+Garamond:wght@400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .batch-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .dropzone {
            border: 3px dashed #d4af37;
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            background: rgba(212, 175, 55, 0.05);
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 2rem 0;
        }

        .dropzone:hover,
        .dropzone.dragover {
            background: rgba(212, 175, 55, 0.15);
            border-color: #e8c547;
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.2);
        }

        .dropzone p {
            margin: 0.5rem 0;
            color: #f5f5f5;
        }

        .dropzone .main-text {
            font-size: 1.3rem;
            font-weight: 600;
            color: #d4af37;
            margin-bottom: 0.5rem;
        }

        .dropzone .sub-text {
            color: #b0b0b0;
            font-size: 0.95rem;
        }

        .file-input {
            display: none;
        }

        .file-list {
            margin: 2rem 0;
            background: rgba(212, 175, 55, 0.05);
            border-left: 3px solid #d4af37;
            border-radius: 8px;
            padding: 1.5rem;
            display: none;
        }

        .file-list.show {
            display: block;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 0.8rem;
            margin-bottom: 0.8rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            justify-content: space-between;
        }

        .file-item-info {
            flex: 1;
            text-align: left;
        }

        .file-item-name {
            color: #f5f5f5;
            font-weight: 500;
            margin-bottom: 0.3rem;
        }

        .file-item-size {
            color: #b0b0b0;
            font-size: 0.85rem;
        }

        .file-item-remove {
            background: #c8a84b;
            border: none;
            color: #1a1035;
            padding: 0.4rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .file-item-remove:hover {
            background: #e8c47a;
        }

        .processing-section {
            display: none;
            margin: 2rem 0;
        }

        .processing-section.show {
            display: block;
        }

        .processing-header {
            font-size: 1.2rem;
            font-weight: 600;
            color: #d4af37;
            margin-bottom: 1rem;
        }

        .progress-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 3px solid #6c63ff;
        }

        .progress-item.completed {
            border-left-color: #4caf50;
        }

        .progress-item.error {
            border-left-color: #f44336;
        }

        .progress-item-filename {
            font-weight: 600;
            color: #f5f5f5;
            margin-bottom: 0.5rem;
        }

        .progress-item-status {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin: 0.5rem 0;
            font-size: 0.9rem;
        }

        .spinner-small {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(108, 99, 255, 0.3);
            border-top-color: #6c63ff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .progress-item.completed .spinner-small {
            display: none;
        }

        .status-text {
            color: #b0b0b0;
        }

        .progress-item.completed .status-text {
            color: #4caf50;
        }

        .progress-item.error .status-text {
            color: #f44336;
        }

        .result-details {
            margin-top: 0.8rem;
            padding-top: 0.8rem;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
            font-size: 0.85rem;
        }

        .result-title {
            color: #d4af37;
            margin: 0.3rem 0;
        }

        .result-lyrics-preview {
            color: #b0b0b0;
            margin: 0.3rem 0;
            max-height: 60px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #c8a84b, #e8c47a);
            color: #1a1035;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(200, 168, 75, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: #2a2a2a;
            color: #f5f5f5;
            border: 1px solid #3a3a3a;
        }

        .btn-secondary:hover {
            background: #3a3a3a;
        }

        .summary-section {
            display: none;
            background: rgba(76, 175, 80, 0.1);
            border-left: 3px solid #4caf50;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .summary-section.show {
            display: block;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 1rem 0;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #d4af37;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #b0b0b0;
            margin-top: 0.3rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .summary-stats {
                grid-template-columns: 1fr;
            }

            .dropzone {
                padding: 2rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="admin-title-section">
                <a href="index.php" class="back-link">← Back to Dashboard</a>
                <h1>Batch Upload Poems</h1>
            </div>
        </header>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <main class="admin-main batch-container">
            <!-- Upload Section -->
            <div id="uploadSection" class="upload-section">
                <h2 style="color: #d4af37; margin-bottom: 1rem;">Upload Multiple MP3 Files</h2>
                <p style="color: #b0b0b0; margin-bottom: 1.5rem;">
                    Select multiple MP3 files. We'll automatically extract lyrics, generate titles, and create poems for you.
                </p>

                <div id="dropzone" class="dropzone">
                    <p class="main-text">Drop MP3 files here</p>
                    <p class="sub-text">or click to select</p>
                    <p class="sub-text">Supported: MP3, M4A, WAV</p>
                </div>

                <input type="file" id="fileInput" class="file-input" accept=".mp3,.m4a,.wav" multiple>

                <!-- File List -->
                <div id="fileList" class="file-list">
                    <h3 style="color: #d4af37; margin-bottom: 1rem;">Files to Upload</h3>
                    <div id="fileListItems"></div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons" id="actionButtons" style="display: none;">
                    <button id="processBtn" class="btn btn-primary">Process Files</button>
                    <button id="clearBtn" class="btn btn-secondary">Clear Selection</button>
                </div>
            </div>

            <!-- Processing Section -->
            <div id="processingSection" class="processing-section">
                <div class="processing-header">Processing Files...</div>
                <div id="progressItems"></div>
            </div>

            <!-- Summary Section -->
            <div id="summarySection" class="summary-section">
                <h3 style="color: #4caf50; margin-bottom: 1rem;">✅ Batch Processing Complete</h3>
                <div class="summary-stats">
                    <div class="stat-box">
                        <div class="stat-number" id="statSuccessCount">0</div>
                        <div class="stat-label">Successful</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" id="statFailedCount">0</div>
                        <div class="stat-label">Failed</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" id="statTotalCount">0</div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
                <p style="color: #b0b0b0; text-align: center; margin-top: 1rem;">
                    You can now add cover photos to each poem from the dashboard.
                </p>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button id="dashboardBtn" class="btn btn-primary" style="flex: 1;">Go to Dashboard</button>
                    <button id="anotherBatchBtn" class="btn btn-secondary" style="flex: 1;">Upload More</button>
                </div>
            </div>
        </main>
    </div>

    <script>
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const fileListItems = document.getElementById('fileListItems');
        const actionButtons = document.getElementById('actionButtons');
        const processBtn = document.getElementById('processBtn');
        const clearBtn = document.getElementById('clearBtn');
        const uploadSection = document.getElementById('uploadSection');
        const processingSection = document.getElementById('processingSection');
        const summarySection = document.getElementById('summarySection');

        let selectedFiles = [];

        // Dropzone events
        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

        function handleFiles(files) {
            selectedFiles = Array.from(files);
            updateFileList();
        }

        function updateFileList() {
            if (selectedFiles.length === 0) {
                fileList.classList.remove('show');
                actionButtons.style.display = 'none';
                return;
            }

            fileListItems.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                const item = document.createElement('div');
                item.className = 'file-item';
                item.innerHTML = `
                    <div class="file-item-info">
                        <div class="file-item-name">${file.name}</div>
                        <div class="file-item-size">${sizeMB} MB</div>
                    </div>
                    <button type="button" class="file-item-remove" onclick="removeFile(${index})">Remove</button>
                `;
                fileListItems.appendChild(item);
            });

            fileList.classList.add('show');
            actionButtons.style.display = 'flex';
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFileList();
        }

        clearBtn.addEventListener('click', () => {
            selectedFiles = [];
            fileInput.value = '';
            updateFileList();
        });

        processBtn.addEventListener('click', () => {
            processBtn.disabled = true;
            uploadSection.style.display = 'none';
            processingSection.classList.add('show');
            processFiles();
        });

        async function processFiles() {
            const progressItems = document.getElementById('progressItems');
            let successCount = 0;
            let failedCount = 0;

            for (let i = 0; i < selectedFiles.length; i++) {
                const file = selectedFiles[i];
                const itemId = `progress-${i}`;

                // Create progress item
                const item = document.createElement('div');
                item.id = itemId;
                item.className = 'progress-item';
                item.innerHTML = `
                    <div class="progress-item-filename">${file.name}</div>
                    <div class="progress-item-status">
                        <div class="spinner-small"></div>
                        <div class="status-text">Extracting lyrics...</div>
                    </div>
                    <div class="result-details" style="display: none;"></div>
                `;
                progressItems.appendChild(item);

                try {
                    // Process file
                    const result = await processFile(file, item);
                    if (result.success) {
                        successCount++;
                    } else {
                        failedCount++;
                    }
                } catch (error) {
                    failedCount++;
                    updateProgressItem(item, 'error', `Error: ${error.message}`);
                }
            }

            // Show summary
            showSummary(successCount, failedCount, selectedFiles.length);
        }

        async function processFile(file, progressItem) {
            const formData = new FormData();
            formData.append('audio', file);

            try {
                // Step 1: Extract lyrics
                updateProgressItem(progressItem, 'processing', 'Extracting lyrics...');
                const lyricsResponse = await fetch('../api/generate-lyrics.php', {
                    method: 'POST',
                    body: formData
                });

                if (!lyricsResponse.ok) throw new Error('Failed to extract lyrics');
                const lyricsData = await lyricsResponse.json();
                if (!lyricsData.success) throw new Error(lyricsData.error || 'Extraction failed');

                const lyrics = lyricsData.lyrics;

                // Step 2: Generate title
                updateProgressItem(progressItem, 'processing', 'Generating title...');
                const titleResponse = await fetch('../api/generate-title.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lyrics: lyrics })
                });

                if (!titleResponse.ok) throw new Error('Failed to generate title');
                const titleData = await titleResponse.json();
                if (!titleData.success) throw new Error(titleData.error || 'Title generation failed');

                const title = titleData.title;

                // Step 3: Save to database
                updateProgressItem(progressItem, 'processing', 'Saving poem...');
                const saveFormData = new FormData();
                saveFormData.append('title', title);
                saveFormData.append('lyrics', lyrics);

                const saveResponse = await fetch('../api/save-poem.php', {
                    method: 'POST',
                    body: saveFormData
                });

                if (!saveResponse.ok) throw new Error('Failed to save poem');
                const saveData = await saveResponse.json();
                if (!saveData.success) throw new Error(saveData.error || 'Save failed');

                // Success
                updateProgressItem(progressItem, 'completed', 'Completed', {
                    title: title,
                    lyricsPreview: lyrics.split('\n')[0]
                });

                return { success: true };
            } catch (error) {
                updateProgressItem(progressItem, 'error', error.message);
                return { success: false };
            }
        }

        function updateProgressItem(item, status, message, details = null) {
            const statusEl = item.querySelector('.progress-item-status');
            const detailsEl = item.querySelector('.result-details');

            if (status === 'completed') {
                item.classList.add('completed');
                item.classList.remove('error');
                statusEl.innerHTML = `<span style="color: #4caf50; font-weight: 600;">✓ ${message}</span>`;
            } else if (status === 'error') {
                item.classList.add('error');
                item.classList.remove('completed');
                statusEl.innerHTML = `<span style="color: #f44336; font-weight: 600;">✗ ${message}</span>`;
            } else {
                statusEl.innerHTML = `
                    <div class="spinner-small"></div>
                    <div class="status-text">${message}</div>
                `;
            }

            if (details) {
                detailsEl.style.display = 'block';
                detailsEl.innerHTML = `
                    <div class="result-title">📖 Title: ${details.title}</div>
                    <div class="result-lyrics-preview">📝 First line: ${details.lyricsPreview}</div>
                `;
            }
        }

        function showSummary(success, failed, total) {
            document.getElementById('statSuccessCount').textContent = success;
            document.getElementById('statFailedCount').textContent = failed;
            document.getElementById('statTotalCount').textContent = total;

            processingSection.classList.remove('show');
            summarySection.classList.add('show');

            document.getElementById('dashboardBtn').addEventListener('click', () => {
                window.location.href = 'index.php';
            });

            document.getElementById('anotherBatchBtn').addEventListener('click', () => {
                location.reload();
            });
        }
    </script>
</body>
</html>
