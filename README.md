# Soul Whispers - Poetry Audio Web App

A beautiful, elegant web application for showcasing poetry with synchronized audio playback. Built with PHP, MySQL, and vanilla JavaScript.

## Features

- **Public Poetry Gallery**: Dark, elegant interface showcasing Farid Tabrizy's poetry collection
- **Audio Playback**: Integration with wavesurfer.js for beautiful waveform visualization
- **Lyric Synchronization**: LRC-format lyric support with automatic line highlighting during playback
- **Admin Dashboard**: Simple interface for uploading, editing, and managing poems
- **Responsive Design**: Beautiful on desktop, tablet, and mobile devices
- **Cover Images**: Optional cover art for each poem
- **Beautiful UI**: Dark theme with gold accents, serif fonts, smooth animations

## Project Structure

```
soulwhispers/
├── index.php                # Public viewer (gallery)
├── config.php              # Database configuration
├── database.sql            # Database schema
│
├── admin/
│   ├── index.php          # Dashboard (list poems)
│   ├── upload.php         # Upload new poem
│   └── edit.php           # Edit existing poem
│
├── api/
│   └── poems.php          # JSON API endpoint
│
├── assets/
│   ├── css/
│   │   ├── style.css      # Public viewer styles
│   │   └── admin.css      # Admin interface styles
│   └── js/
│       └── player.js      # Audio player & lyric sync logic
│
├── uploads/
│   ├── audio/            # Audio files (.mp3, .m4a, .wav)
│   └── covers/           # Cover images (.jpg, .png)
│
├── README.md
└── DEPLOYMENT.md
```

## Local Development

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern browser (Chrome, Firefox, Safari, Edge)

### Setup

1. **Clone/copy the project**:
   ```bash
   cd /Users/chipmcallister/Projects/soulwhispers
   ```

2. **Create database**:
   ```bash
   mysql -u mcallpl -pamazing123 < database.sql
   ```

3. **Start PHP server**:
   ```bash
   php -S localhost:8000
   ```

4. **Access the app**:
   - Public: http://localhost:8000
   - Admin: http://localhost:8000/admin

## Usage

### Adding a Poem

1. Go to **Admin Dashboard** → http://localhost:8000/admin
2. Click **"+ Upload New Poem"**
3. Fill in:
   - **Title**: Poem name (required)
   - **Subtitle**: Optional subtitle or artist info
   - **Lyrics**: Paste the full lyrics (optional)
     - For synchronized playback, use LRC format: `[0:15] First line of lyrics`
     - Plain text also works—lyrics display while audio plays
   - **Audio File**: MP3, M4A, or WAV (required)
   - **Cover Image**: JPG or PNG (optional)
   - **Sort Order**: 0 = first in gallery
4. Click **"Upload Poem"**

### LRC Format (Lyric Synchronization)

For word-by-word lyric sync during playback:

```
[0:00] First line of the poem
[0:05] Second line appears here
[0:10] Third line of poetry
[0:15] And so on...
```

Format: `[MM:SS] Lyric text`

Without timestamps, all lyrics display as plain text while audio plays.

### Editing a Poem

1. Admin Dashboard → Find the poem
2. Click **"Edit"**
3. Update any field and save
4. You can replace the audio file or cover image without replacing both

### Deleting a Poem

1. Admin Dashboard → Find the poem
2. Click **"Delete"** and confirm
3. Both the database record and associated files are deleted

## Technical Details

### Database Schema

```sql
CREATE TABLE poems (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  subtitle VARCHAR(255),
  lyrics LONGTEXT,
  audio_filename VARCHAR(255) NOT NULL,
  cover_image VARCHAR(255),
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Audio Player

- **Library**: wavesurfer.js (loaded from CDN)
- **Features**:
  - Waveform visualization
  - Play/pause controls
  - Current time / total duration display
  - Click-to-seek on waveform
  - Lyric sync with automatic highlighting

### Design System

- **Colors**:
  - Dark background: `#0a0e27` / `#050810`
  - Accent: `#d4af37` (gold)
  - Text: `#f5f5f5` (light)

- **Typography**:
  - Headings: Playfair Display (serif)
  - Body: EB Garamond (serif)
  - UI: Inter (sans-serif)

- **Responsive Breakpoints**:
  - Desktop: 1200px+
  - Tablet: 768px
  - Mobile: 480px

## Configuration

Edit `config.php` to change database credentials. For production, use environment variables or a separate config file.

Default values (from vault/secrets.php):
```php
$db_host = 'localhost'
$db_user = 'mcallpl'
$db_pass = 'amazing123'
$db_name = 'soulwhispers'
```

## Production Deployment

See **DEPLOYMENT.md** for complete setup instructions including:
- Nginx configuration for soulwhispers.peoplestar.com
- SSL setup with Let's Encrypt
- File permissions and directory setup
- Database creation on production server
- Rsync deployment commands

## Security Notes

- **No authentication** on admin panel (local/private use only)
- Filenames are sanitized to prevent path traversal
- Database queries use prepared statements
- Uploaded files are stored outside the web root (not accessible directly)
- Configuration with secrets is never committed to git

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

Mobile browsers with ES6 support.

## Performance Notes

- Waveform visualization disabled on slow connections (auto-detects)
- CSS animations use GPU-accelerated transforms
- Static assets cached with 1-year expiry on production
- Gzip compression enabled on production

## Future Enhancements

Potential features (not currently implemented):
- User authentication for multi-admin setup
- Advanced search/filtering
- Playlist functionality
- Social sharing buttons
- Comment/rating system
- Audio visualization presets
- Batch upload functionality

## License

Built for Farid Tabrizy's poetry collection.

## Support

For issues or questions, check:
- DEPLOYMENT.md for setup problems
- Browser console for JavaScript errors
- MySQL error logs for database issues
