#!/usr/bin/env python3
"""
Batch processor for SoulWhispers poems.
Extracts lyrics from MP3 files, generates titles, creates thumbnails, and saves to DB.
"""

import os
import sys
import json
import subprocess
import re
from datetime import datetime
from pathlib import Path
import mysql.connector
from anthropic import Anthropic
from PIL import Image, ImageDraw, ImageFont

# Configuration (load from env vars for security)
DB_HOST = os.getenv("DB_HOST", "localhost")
DB_USER = os.getenv("DB_USER", "mcallpl")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")
DB_NAME = os.getenv("DB_NAME", "soulwhispers")
UPLOAD_DIR = "/Users/chipmcallister/Projects/soulwhispers/uploads"
AUDIO_DIR = os.path.join(UPLOAD_DIR, "audio")
COVER_DIR = os.path.join(UPLOAD_DIR, "covers")

# Create upload directories if they don't exist
os.makedirs(AUDIO_DIR, exist_ok=True)
os.makedirs(COVER_DIR, exist_ok=True)

def get_db_connection():
    """Connect to MySQL database."""
    return mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASSWORD,
        database=DB_NAME
    )

def extract_lyrics_with_whisper(audio_file):
    """Extract lyrics from audio using Whisper."""
    print(f"  Extracting lyrics from {os.path.basename(audio_file)}...")

    try:
        # Use whisper via python3 -m to extract subtitles in VTT format
        result = subprocess.run(
            ["python3", "-m", "whisper", audio_file, "--output_format", "vtt", "--model", "tiny", "--output_dir", "/tmp"],
            capture_output=True,
            text=True,
            timeout=300
        )

        if result.returncode != 0:
            print(f"    ❌ Whisper error: {result.stderr}")
            return None

        # Read the VTT file
        base_name = os.path.splitext(os.path.basename(audio_file))[0]
        vtt_file = f"/tmp/{base_name}.vtt"

        if not os.path.exists(vtt_file):
            print(f"    ❌ VTT file not generated")
            return None

        with open(vtt_file, 'r') as f:
            vtt_content = f.read()

        # Convert VTT to LRC format
        lrc_content = convert_vtt_to_lrc(vtt_content)

        # Cleanup
        try:
            os.remove(vtt_file)
        except:
            pass

        print(f"    ✅ Lyrics extracted ({len(lrc_content)} chars)")
        return lrc_content

    except subprocess.TimeoutExpired:
        print(f"    ❌ Whisper timeout")
        return None
    except Exception as e:
        print(f"    ❌ Error: {str(e)}")
        return None

def convert_vtt_to_lrc(vtt_content):
    """Convert VTT subtitle format to LRC format."""
    lrc_lines = []

    lines = vtt_content.split('\n')
    i = 0

    while i < len(lines):
        line = lines[i].strip()

        # Look for timestamp line (format: MM:SS.mmm --> MM:SS.mmm or HH:MM:SS.mmm --> HH:MM:SS.mmm)
        if '-->' in line:
            timestamps = line.split('-->')[0].strip()
            # Parse timestamp (MM:SS.mmm or HH:MM:SS.mmm)
            try:
                parts = timestamps.split(':')

                if len(parts) == 2:  # MM:SS.mmm format
                    minutes = int(parts[0])
                    seconds = int(float(parts[1]))
                    total_seconds = minutes * 60 + seconds

                elif len(parts) == 3:  # HH:MM:SS.mmm format
                    hours = int(parts[0])
                    minutes = int(parts[1])
                    seconds = int(float(parts[2]))
                    total_seconds = hours * 3600 + minutes * 60 + seconds

                else:
                    i += 1
                    continue

                # Calculate M:SS format
                m = total_seconds // 60
                s = total_seconds % 60

                # Get next non-empty line as text
                i += 1
                while i < len(lines) and not lines[i].strip():
                    i += 1

                if i < len(lines) and lines[i].strip() and '-->' not in lines[i]:
                    text = lines[i].strip()
                    lrc_lines.append(f"[{m}:{s:02d}] {text}")

            except Exception as e:
                pass

        i += 1

    return '\n'.join(lrc_lines)

def generate_title_with_claude(lyrics):
    """Generate a title for the poem based on its lyrics."""
    print(f"  Generating title...")

    # Extract first meaningful line as fallback title
    lines = [line[line.find('] ')+2:] if '] ' in line else line
             for line in lyrics.split('\n') if line.strip()]
    fallback_title = (lines[0][:50] if lines else "Untitled Poem").strip()

    try:
        client = Anthropic()

        # Extract first few lines for context
        sample_text = '\n'.join(lines[:10])

        response = client.messages.create(
            model="claude-opus-4-8",
            max_tokens=100,
            messages=[
                {
                    "role": "user",
                    "content": f"""Given the following poem lyrics, generate a short, poetic title (2-5 words) that captures the essence of the poem.
Return ONLY the title, nothing else.

Lyrics:
{sample_text}"""
                }
            ]
        )

        title = response.content[0].text.strip().strip('"').strip("'")
        print(f"    ✅ Title generated: '{title}'")
        return title

    except Exception as e:
        print(f"    ⚠️  Title generation skipped (API unavailable), using: '{fallback_title}'")
        return fallback_title

def create_thumbnail_from_lyrics(lyrics, filename):
    """Create a 500x500px thumbnail based on the poem's lyrics."""
    print(f"  Creating thumbnail...")

    try:
        # Extract first few meaningful lines
        lines = lyrics.split('\n')
        text_lines = [line[line.find('] ')+2:] if '] ' in line else line
                      for line in lines if line.strip()][:3]

        # Create image
        img = Image.new('RGB', (500, 500), color=(15, 23, 42))  # Dark navy
        draw = ImageDraw.Draw(img)

        # Try to use a nice font, fall back to default
        try:
            title_font = ImageFont.truetype("/System/Library/Fonts/Georgia.ttf", 28)
            text_font = ImageFont.truetype("/System/Library/Fonts/Georgia.ttf", 18)
        except:
            title_font = ImageFont.load_default()
            text_font = ImageFont.load_default()

        # Draw decorative border and text
        draw.rectangle([(20, 20), (480, 480)], outline=(212, 175, 55), width=2)  # Gold border

        # Draw text with word wrapping
        y_pos = 100
        for line in text_lines[:3]:
            if not line.strip():
                continue

            # Word wrap
            words = line.split()
            current_line = ""

            for word in words:
                test_line = f"{current_line} {word}".strip()
                bbox = draw.textbbox((0, 0), test_line, font=text_font)
                width = bbox[2] - bbox[0]

                if width > 400:
                    if current_line:
                        draw.text((50, y_pos), current_line, fill=(212, 175, 55), font=text_font)
                        y_pos += 40
                    current_line = word
                else:
                    current_line = test_line

            if current_line:
                draw.text((50, y_pos), current_line, fill=(212, 175, 55), font=text_font)
                y_pos += 60

        # Save image
        output_path = os.path.join(COVER_DIR, filename)
        img.save(output_path, 'JPEG', quality=85)

        # Check file size
        file_size = os.path.getsize(output_path) / (1024 * 1024)  # MB
        print(f"    ✅ Thumbnail created ({file_size:.2f}MB)")

        return filename

    except Exception as e:
        print(f"    ⚠️  Thumbnail creation failed: {str(e)}")
        return None

def copy_audio_to_uploads(source_file):
    """Copy audio file to uploads directory with timestamped filename."""
    try:
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"{timestamp}_{os.path.basename(source_file)}"
        dest_path = os.path.join(AUDIO_DIR, filename)

        # Copy file
        with open(source_file, 'rb') as src:
            with open(dest_path, 'wb') as dst:
                dst.write(src.read())

        print(f"    ✅ Audio copied: {filename}")
        return filename

    except Exception as e:
        print(f"    ❌ Audio copy failed: {str(e)}")
        return None

def save_poem_to_db(title, lyrics, audio_filename, cover_filename):
    """Save poem to database."""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()

        # Get the current max sort_order
        cursor.execute("SELECT COALESCE(MAX(sort_order), 0) FROM poems")
        max_sort = cursor.fetchone()[0]
        next_sort = max_sort + 1

        query = """
            INSERT INTO poems (title, lyrics, audio_filename, cover_image, sort_order, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, NOW(), NOW())
        """

        cursor.execute(query, (title, lyrics, audio_filename, cover_filename, next_sort))
        conn.commit()

        poem_id = cursor.lastrowid
        cursor.close()
        conn.close()

        print(f"    ✅ Saved to DB (ID: {poem_id})")
        return poem_id

    except Exception as e:
        print(f"    ❌ Database save failed: {str(e)}")
        return None

def process_audio_file(audio_file):
    """Process a single audio file: extract lyrics, generate title, create thumbnail, save to DB."""
    basename = os.path.basename(audio_file)
    print(f"\n📄 Processing: {basename}")

    # Step 1: Extract lyrics
    lyrics = extract_lyrics_with_whisper(audio_file)
    if not lyrics:
        print("  ❌ Skipped (no lyrics extracted)")
        return False

    # Step 2: Generate title
    title = generate_title_with_claude(lyrics)

    # Step 3: Copy audio file
    audio_filename = copy_audio_to_uploads(audio_file)
    if not audio_filename:
        print("  ❌ Skipped (audio copy failed)")
        return False

    # Step 4: Create thumbnail
    cover_filename = create_thumbnail_from_lyrics(lyrics, f"{os.path.splitext(basename)[0]}_thumb.jpg")

    # Step 5: Save to database
    poem_id = save_poem_to_db(title, lyrics, audio_filename, cover_filename)

    if poem_id:
        print(f"✅ Complete: '{title}' (ID: {poem_id})")
        return True
    else:
        print("  ❌ Failed to save to database")
        return False

def main():
    """Main batch processing function."""
    # Get list of audio files
    downloads_dir = os.path.expanduser("~/Downloads")
    audio_files = [
        "8309521166200972668.mp3",
        "323219848102625630.mp3",
        "2999197712401156243.mp3",
        "9027166712439329916.mp3",
        "5750569782903967350.mp3",
        "5121429413058916257.mp3",
        "4501342367470152958.mp3",
        "3067430453418044072.mp3"
    ]

    audio_paths = [os.path.join(downloads_dir, f) for f in audio_files]

    # Verify files exist
    missing = [f for f in audio_paths if not os.path.exists(f)]
    if missing:
        print(f"❌ Missing files: {missing}")
        return False

    print(f"\n🎵 SoulWhispers Batch Processor")
    print(f"Processing {len(audio_paths)} poems...")
    print("=" * 60)

    # Process each file
    success_count = 0
    for audio_file in audio_paths:
        if process_audio_file(audio_file):
            success_count += 1

    print("\n" + "=" * 60)
    print(f"✅ Complete: {success_count}/{len(audio_paths)} poems processed")

    return True

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n⚠️  Interrupted by user")
    except Exception as e:
        print(f"❌ Fatal error: {str(e)}")
        import traceback
        traceback.print_exc()
