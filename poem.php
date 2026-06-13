<?php
require_once 'config.php';

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: index.php');
    exit;
}

$query = "SELECT * FROM poems WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$poem = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($poem['title']); ?> - Soul Whispers</title>
    <meta name="description" content="<?php echo htmlspecialchars($poem['subtitle'] ?? 'A poem by Farid Tabrizy'); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="64x64" href="assets/favicon-64.png">
    <link rel="icon" type="image/png" sizes="128x128" href="assets/favicon-128.png">
    <link rel="apple-touch-icon" href="assets/favicon-128.png">

    <!-- Open Graph Meta Tags for Social Media -->
    <meta property="og:title" content="<?php echo htmlspecialchars($poem['title']); ?> - Soul Whispers">
    <meta property="og:description" content="<?php echo htmlspecialchars($poem['subtitle'] ?? 'A spiritual poem by Farid Tabrizy'); ?>">
    <meta property="og:image" content="https://soulwhispers.peoplestar.com/uploads/covers/<?php echo htmlspecialchars($poem['cover_image']); ?>">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:url" content="https://soulwhispers.peoplestar.com/poem.php?id=<?php echo $poem['id']; ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Soul Whispers">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($poem['title']); ?> - Soul Whispers">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($poem['subtitle'] ?? 'A poem by Farid Tabrizy'); ?>">
    <meta name="twitter:image" content="https://soulwhispers.peoplestar.com/uploads/covers/<?php echo htmlspecialchars($poem['cover_image']); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Star field background -->
    <div class="stars"></div>

    <!-- Glowing moon/orb with orbiting stars -->
    <div class="moon">
        <div class="moon-glow"></div>
        <div class="moon-core"></div>
        <div class="orbital-ring">
            <div class="orbiting-star star-1"></div>
            <div class="orbiting-star star-2"></div>
            <div class="orbiting-star star-3"></div>
            <div class="orbiting-star star-4"></div>
            <div class="orbiting-star star-5"></div>
        </div>
    </div>

    <!-- Player Page -->
    <main class="poem-player-page">
        <div class="player-page-container">
            <div class="poem-player-content">
                <div class="player-header">
                    <h1 id="player-title" class="player-title"><?php echo htmlspecialchars($poem['title']); ?></h1>
                    <a href="index.php" class="back-link">Back</a>
                </div>
                <p id="player-subtitle" class="player-subtitle"><?php echo htmlspecialchars($poem['subtitle'] ?? ''); ?></p>

                <div class="player-waveform-container">
                    <input type="range" id="progress-bar" class="progress-bar" min="0" max="100" value="0">
                    <div class="time-info">
                        <span id="current-time">0:00</span>
                        <span id="duration-time">0:00</span>
                    </div>
                </div>

                <div class="player-controls">
                    <button id="play-button" class="control-button" title="Play">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="5 3 19 12 5 21"></polygon>
                        </svg>
                    </button>
                    <button id="pause-button" class="control-button" style="display:none;" title="Pause">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <rect x="6" y="4" width="4" height="16"></rect>
                            <rect x="14" y="4" width="4" height="16"></rect>
                        </svg>
                    </button>
                </div>

                <div class="lyrics-section" id="lyrics-section" style="display:none;">
                    <div class="lyrics-container" id="lyrics-container">
                        <div id="lyrics-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-decoration"></div>
        <a href="admin/index.php" class="admin-link" title="Admin Dashboard">⚙</a>
    </footer>

    <!-- Hidden audio element -->
    <audio id="audio-player"></audio>

    <script>
        class SoulWhispersPlayer {
            constructor() {
                this.wavesurfer = null;
                this.currentLyrics = [];
                this.analyser = null;
                this.dataArray = null;
                this.lastUpdateTime = 0;
                this.updateThrottle = 100; // Update every 100ms max
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.loadPoem();
            }

            loadPoem() {
                const poemData = {
                    title: document.getElementById('player-title').textContent,
                    subtitle: document.getElementById('player-subtitle').textContent,
                    audio: '<?php echo htmlspecialchars($poem['audio_filename']); ?>',
                    lyrics: <?php echo json_encode($poem['lyrics'] ?? ''); ?>
                };

                const audioPath = `uploads/audio/${poemData.audio}`;
                const audio = document.getElementById('audio-player');
                audio.src = audioPath;

                this.parseLyrics(poemData.lyrics);
                this.renderLyrics();

                this.initWavesurfer(audio);
                this.play();
            }

            setupEventListeners() {
                document.getElementById('play-button').addEventListener('click', () => this.play());
                document.getElementById('pause-button').addEventListener('click', () => this.pause());
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') window.history.back();
                });
            }

            initWavesurfer(audioElement) {
                this.setupAudioAnalyser(audioElement);
                const progressBar = document.getElementById('progress-bar');

                audioElement.addEventListener('play', () => {
                    document.getElementById('play-button').style.display = 'none';
                    document.getElementById('pause-button').style.display = 'flex';
                });

                audioElement.addEventListener('pause', () => {
                    document.getElementById('play-button').style.display = 'flex';
                    document.getElementById('pause-button').style.display = 'none';
                });

                audioElement.addEventListener('timeupdate', () => {
                    const currentTime = audioElement.currentTime;
                    document.getElementById('current-time').textContent = this.formatTime(currentTime);
                    progressBar.value = (currentTime / audioElement.duration) * 100;
                    this.updateLyricSync(currentTime);
                });

                audioElement.addEventListener('loadedmetadata', () => {
                    document.getElementById('duration-time').textContent = this.formatTime(audioElement.duration);
                    progressBar.max = 100;
                });

                audioElement.addEventListener('ended', () => {
                    document.getElementById('play-button').style.display = 'flex';
                    document.getElementById('pause-button').style.display = 'none';
                });

                progressBar.addEventListener('input', () => {
                    audioElement.currentTime = (progressBar.value / 100) * audioElement.duration;
                });
            }

            setupAudioAnalyser(audioElement) {
                try {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    const source = audioContext.createMediaElementAudioSource(audioElement);
                    this.analyser = audioContext.createAnalyser();
                    this.analyser.fftSize = 256;
                    source.connect(this.analyser);
                    this.analyser.connect(audioContext.destination);
                    this.dataArray = new Uint8Array(this.analyser.frequencyBinCount);
                } catch (e) {
                    console.warn('Audio analyser not available');
                }
            }

            parseLyrics(lyricsText) {
                this.currentLyrics = [];
                if (!lyricsText) return;

                const lines = lyricsText.split('\n');

                lines.forEach(line => {
                    const trimmed = line.trim();
                    if (!trimmed) return;

                    // Try VTT format: [MM:SS.mmm --> MM:SS.mmm] Text
                    let match = trimmed.match(/^\[(\d{1,2}):(\d{2})\.?(\d*)\s*-->\s*(\d{1,2}):(\d{2})\.?(\d*)\](.*)/);
                    if (match) {
                        const startMin = parseInt(match[1]);
                        const startSec = parseInt(match[2]);
                        const startMs = match[3] ? parseInt(match[3].padEnd(3, '0')) : 0;
                        const start = startMin * 60 + startSec + startMs / 1000;

                        const endMin = parseInt(match[4]);
                        const endSec = parseInt(match[5]);
                        const endMs = match[6] ? parseInt(match[6].padEnd(3, '0')) : 0;
                        const end = endMin * 60 + endSec + endMs / 1000;

                        const text = match[7].trim();
                        if (text) {
                            this.currentLyrics.push({ time: start, endTime: end, text });
                        }
                        return;
                    }

                    // Try HH:MM:SS.mmm format
                    match = trimmed.match(/^\[(\d{2}):(\d{2}):(\d{2})\.?(\d*)\s*-->\s*(\d{2}):(\d{2}):(\d{2})\.?(\d*)\](.*)/);
                    if (match) {
                        const start = parseInt(match[1]) * 3600 + parseInt(match[2]) * 60 + parseInt(match[3]) + (match[4] ? parseInt(match[4].padEnd(3, '0')) / 1000 : 0);
                        const end = parseInt(match[5]) * 3600 + parseInt(match[6]) * 60 + parseInt(match[7]) + (match[8] ? parseInt(match[8].padEnd(3, '0')) / 1000 : 0);
                        const text = match[9].trim();
                        if (text) {
                            this.currentLyrics.push({ time: start, endTime: end, text });
                        }
                        return;
                    }

                    // Try simple LRC format: [M:SS] Text (no end time)
                    match = trimmed.match(/^\[(\d{1,2}):(\d{2})\.?(\d*)\](.*)/);
                    if (match) {
                        const startMin = parseInt(match[1]);
                        const startSec = parseInt(match[2]);
                        const startMs = match[3] ? parseInt(match[3].padEnd(3, '0')) : 0;
                        const start = startMin * 60 + startSec + startMs / 1000;
                        const text = match[4].trim();
                        if (text) {
                            this.currentLyrics.push({ time: start, endTime: null, text });
                        }
                        return;
                    }

                    // Add as plain text
                    if (trimmed && !trimmed.startsWith('[')) {
                        this.currentLyrics.push({ time: -1, text: trimmed });
                    }
                });

                // Calculate end times for lyrics that don't have them
                for (let i = 0; i < this.currentLyrics.length; i++) {
                    if (this.currentLyrics[i].endTime === null && this.currentLyrics[i].time >= 0) {
                        // End time is when the next lyric starts, or 3 seconds later if it's the last one
                        if (i < this.currentLyrics.length - 1 && this.currentLyrics[i + 1].time >= 0) {
                            this.currentLyrics[i].endTime = this.currentLyrics[i + 1].time;
                        } else {
                            this.currentLyrics[i].endTime = this.currentLyrics[i].time + 3;
                        }
                    }
                }
            }

            renderLyrics() {
                const container = document.getElementById('lyrics-content');
                const section = document.getElementById('lyrics-section');

                if (this.currentLyrics.length === 0) {
                    section.style.display = 'none';
                    return;
                }

                section.style.display = 'block';
                container.innerHTML = this.currentLyrics
                    .map((line, idx) => `<div class="lyric-line" data-index="${idx}" data-time="${line.time || 0}">${this.escapeHtml(line.text)}</div>`)
                    .join('');

                container.querySelectorAll('.lyric-line').forEach(el => {
                    el.addEventListener('click', () => {
                        const time = parseFloat(el.dataset.time);
                        if (time >= 0 && this.wavesurfer) {
                            this.wavesurfer.seekTo(time / this.wavesurfer.getDuration());
                        }
                    });
                });
            }

            updateLyricSync(currentTime) {
                if (!this.currentLyrics.length) return;

                let currentLineIdx = -1;

                // Find the current line based on time range
                for (let i = 0; i < this.currentLyrics.length; i++) {
                    const lyric = this.currentLyrics[i];
                    if (lyric.time >= 0 && lyric.time <= currentTime &&
                        (!lyric.endTime || lyric.endTime >= currentTime)) {
                        currentLineIdx = i;
                        break;
                    }
                }

                document.querySelectorAll('.lyric-line').forEach((el, idx) => {
                    el.classList.remove('past', 'current', 'future');

                    if (idx < currentLineIdx) {
                        el.classList.add('past');
                    } else if (idx === currentLineIdx && this.currentLyrics[idx].time >= 0) {
                        el.classList.add('current');
                    } else {
                        el.classList.add('future');
                    }
                });

                if (currentLineIdx >= 0) {
                    const currentEl = document.querySelector(`[data-index="${currentLineIdx}"]`);
                    if (currentEl) {
                        currentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }

            play() {
                const audio = document.getElementById('audio-player');
                audio.play();
            }

            pause() {
                const audio = document.getElementById('audio-player');
                audio.pause();
            }

            formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return '0:00';
                const mins = Math.floor(seconds / 60);
                const secs = Math.floor(seconds % 60);
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            }

            escapeHtml(text) {
                const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                return text.replace(/[&<>"']/g, m => map[m]);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            new SoulWhispersPlayer();
        });
    </script>
</body>
</html>
