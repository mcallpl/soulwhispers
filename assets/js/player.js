class SoulWhispersPlayer {
    constructor() {
        this.wavesurfer = null;
        this.currentLyrics = [];
        this.animationCanvas = null;
        this.animationFrame = null;
        this.particles = [];
        this.analyser = null;
        this.dataArray = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupAnimationCanvas();
    }

    setupAnimationCanvas() {
        const container = document.querySelector('.player-container');
        if (!container) return;

        const existingCanvas = document.getElementById('player-animation-canvas');
        if (existingCanvas) existingCanvas.remove();

        const canvas = document.createElement('canvas');
        canvas.id = 'player-animation-canvas';
        container.style.position = 'relative';
        container.style.overflow = 'hidden';
        container.insertBefore(canvas, container.firstChild);
        this.animationCanvas = canvas;
    }

    setupEventListeners() {
        document.querySelectorAll('.play-button').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openPlayer(btn.dataset.poemId);
            });
        });

        document.getElementById('player-close').addEventListener('click', () => this.closePlayer());
        document.getElementById('player-modal').addEventListener('click', (e) => {
            if (e.target.id === 'player-modal') this.closePlayer();
        });

        document.getElementById('play-button').addEventListener('click', () => this.play());
        document.getElementById('pause-button').addEventListener('click', () => this.pause());

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closePlayer();
        });
    }

    openPlayer(poemId) {
        const card = document.querySelector(`[data-poem-id="${poemId}"]`);
        if (!card) return;

        const poemData = {
            title: card.dataset.title,
            subtitle: card.dataset.subtitle,
            audio: card.dataset.audio,
            lyrics: JSON.parse(card.dataset.lyrics || '""')
        };

        document.getElementById('player-title').textContent = poemData.title;
        document.getElementById('player-subtitle').textContent = poemData.subtitle;

        const audioPath = `uploads/audio/${poemData.audio}`;
        const audio = document.getElementById('audio-player');
        audio.src = audioPath;

        this.parseLyrics(poemData.lyrics);
        this.renderLyrics();

        if (!this.wavesurfer) {
            this.initWavesurfer(audio);
        } else {
            this.wavesurfer.load(audioPath);
        }

        const modal = document.getElementById('player-modal');
        modal.classList.add('active');
        this.updateLyricSync(0);
        this.startAnimation();

        setTimeout(() => {
            if (this.wavesurfer) this.wavesurfer.play();
        }, 100);
    }

    initWavesurfer(audioElement) {
        this.wavesurfer = WaveSurfer.create({
            container: '#waveform',
            waveColor: 'var(--gold-primary)',
            progressColor: 'var(--gold-light)',
            barWidth: 2,
            barGap: 1,
            barRadius: 2,
            height: 60,
            media: audioElement,
            dragToSeek: true
        });

        this.setupAudioAnalyser(audioElement);

        this.wavesurfer.on('play', () => {
            document.getElementById('play-button').style.display = 'none';
            document.getElementById('pause-button').style.display = 'flex';
        });

        this.wavesurfer.on('pause', () => {
            document.getElementById('play-button').style.display = 'flex';
            document.getElementById('pause-button').style.display = 'none';
        });

        this.wavesurfer.on('audioprocess', () => {
            const currentTime = this.wavesurfer.getCurrentTime();
            document.getElementById('current-time').textContent = this.formatTime(currentTime);
            this.updateLyricSync(currentTime);
        });

        this.wavesurfer.on('ready', () => {
            const duration = this.wavesurfer.getDuration();
            document.getElementById('duration-time').textContent = this.formatTime(duration);
        });

        this.wavesurfer.on('finish', () => {
            document.getElementById('play-button').style.display = 'flex';
            document.getElementById('pause-button').style.display = 'none';
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

            // Try HH:MM:SS.mmm format (legacy)
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

            // Try simple LRC format: [M:SS] Text or [MM:SS] Text (no end time)
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
        const hasTimestamps = this.currentLyrics.some(l => l.time >= 0);

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
        if (this.wavesurfer) this.wavesurfer.play();
    }

    pause() {
        if (this.wavesurfer) this.wavesurfer.pause();
    }

    closePlayer() {
        if (this.wavesurfer) this.wavesurfer.pause();
        document.getElementById('player-modal').classList.remove('active');
        this.stopAnimation();
    }

    startAnimation() {
        this.particles = [];
        this.animateBackground();
    }

    stopAnimation() {
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
    }

    animateBackground() {
        if (!this.animationCanvas) return;

        const canvas = this.animationCanvas;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        let audioIntensity = 0.3;
        if (this.analyser && this.dataArray) {
            try {
                this.analyser.getByteFrequencyData(this.dataArray);
                const sum = this.dataArray.reduce((a, b) => a + b, 0);
                audioIntensity = Math.min(1, (sum / this.dataArray.length) / 128);
            } catch (e) {
                // Analyser might not be ready
            }
        }

        const particleRate = 0.3 + (audioIntensity * 0.4);
        if (Math.random() < particleRate) {
            const hue = 30 + (audioIntensity * 30);
            this.particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                vx: (Math.random() - 0.5) * (2 + audioIntensity * 2),
                vy: (Math.random() - 0.5) * (2 + audioIntensity * 2),
                life: 1,
                size: Math.random() * 3 + 1 + (audioIntensity * 2),
                color: `hsla(${hue}, 100%, 60%, `
            });
        }

        ctx.fillStyle = 'rgba(255, 255, 255, 0.02)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        this.particles = this.particles.filter(p => {
            p.vx += (Math.random() - 0.5) * 0.1 * (1 + audioIntensity);
            p.vy += 0.02 + (audioIntensity * 0.05);
            p.x += p.vx;
            p.y += p.vy;
            p.life -= 0.01 + (audioIntensity * 0.01);

            if (p.x < 0 || p.x > canvas.width) p.vx *= -0.8;
            if (p.y < 0 || p.y > canvas.height) p.vy *= -0.8;

            if (p.life > 0 && p.y < canvas.height) {
                const audioBoost = 1 + (audioIntensity * 0.5);
                ctx.fillStyle = p.color + p.life + ')';
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size * audioBoost, 0, Math.PI * 2);
                ctx.fill();
                return true;
            }
            return false;
        });

        ctx.strokeStyle = `rgba(200, 150, 100, ${0.1 + audioIntensity * 0.2})`;
        ctx.lineWidth = 0.5 + (audioIntensity * 1.5);
        for (let i = 0; i < this.particles.length; i++) {
            for (let j = i + 1; j < this.particles.length; j++) {
                const dx = this.particles[i].x - this.particles[j].x;
                const dy = this.particles[i].y - this.particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                const connectionDist = 150 + (audioIntensity * 100);
                if (dist < connectionDist) {
                    ctx.beginPath();
                    ctx.moveTo(this.particles[i].x, this.particles[i].y);
                    ctx.lineTo(this.particles[j].x, this.particles[j].y);
                    ctx.stroke();
                }
            }
        }

        this.animationFrame = requestAnimationFrame(() => this.animateBackground());
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
