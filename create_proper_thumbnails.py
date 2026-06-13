#!/usr/bin/env python3
"""Create proper photo-style thumbnail images for poems."""

import os
import mysql.connector
from PIL import Image, ImageDraw, ImageFont
import colorsys
import random

DB_HOST = os.getenv("DB_HOST", "localhost")
DB_USER = os.getenv("DB_USER", "mcallpl")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")
DB_NAME = os.getenv("DB_NAME", "soulwhispers")
COVER_DIR = "/Users/chipmcallister/Projects/soulwhispers/uploads/covers"

def get_db_connection():
    return mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASSWORD,
        database=DB_NAME
    )

def create_elegant_thumbnail(poem_id, title, lyrics, filename):
    """Create an elegant, minimal photo-style thumbnail."""

    # Color palette - different for each poem
    colors = [
        {'bg': '#0f0c22', 'accent': '#c8a84b', 'text': '#e8c47a'},  # Gold
        {'bg': '#1a0f2e', 'accent': '#7b9fd4', 'text': '#a8c5e0'},  # Blue
        {'bg': '#0a1428', 'accent': '#6c9fd4', 'text': '#9eb8d0'},  # Slate
        {'bg': '#220f26', 'accent': '#d4847b', 'text': '#e0a89f'},  # Rose
        {'bg': '#0f1a18', 'accent': '#7ba888', 'text': '#a8d0b8'},  # Sage
        {'bg': '#251810', 'accent': '#c89060', 'text': '#e0b090'},  # Copper
        {'bg': '#0a0f20', 'accent': '#8b7fa8', 'text': '#b8a8d0'},  # Lavender
        {'bg': '#1a0f0a', 'accent': '#b88070', 'text': '#d0a090'},  # Rust
    ]

    color = colors[poem_id % len(colors)]

    # Create image
    img = Image.new('RGB', (500, 500), color=color['bg'])
    draw = ImageDraw.Draw(img)

    # Try to load fonts
    try:
        title_font = ImageFont.truetype("/System/Library/Fonts/Helvetica.ttc", 36)
        subtitle_font = ImageFont.truetype("/System/Library/Fonts/Helvetica.ttc", 18)
    except:
        title_font = ImageFont.load_default()
        subtitle_font = ImageFont.load_default()

    # Draw decorative elements
    # Top accent bar
    draw.rectangle([(0, 0), (500, 60)], fill=color['accent'])

    # Extract first meaningful word from title for display
    words = title.split()
    display_title = ' '.join(words[:2]) if len(words) >= 2 else title

    # Draw title in center
    bbox = draw.textbbox((0, 0), display_title, font=title_font)
    title_width = bbox[2] - bbox[0]
    title_height = bbox[3] - bbox[1]

    title_x = (500 - title_width) // 2
    title_y = 200 - title_height // 2

    draw.text((title_x, title_y), display_title, fill=color['text'], font=title_font)

    # Draw decorative bottom accent
    draw.rectangle([(0, 440), (500, 500)], fill=color['accent'])

    # Draw corner accents
    accent_size = 30
    draw.polygon([(0, 0), (accent_size, 0), (0, accent_size)], fill=color['text'])
    draw.polygon([(500, 500), (500-accent_size, 500), (500, 500-accent_size)], fill=color['text'])

    # Draw subtle border
    border_width = 2
    draw.rectangle([(border_width, border_width), (500-border_width, 500-border_width)],
                   outline=color['accent'], width=border_width)

    # Save
    output_path = os.path.join(COVER_DIR, filename)
    img.save(output_path, 'JPEG', quality=90)

    return output_path

def main():
    conn = get_db_connection()
    cursor = conn.cursor()

    cursor.execute("SELECT id, title, lyrics FROM poems ORDER BY id")
    poems = cursor.fetchall()

    print("\n🎨 Creating Proper Thumbnails\n")
    print("=" * 60)

    for poem_id, title, lyrics in poems:
        # Get existing filename
        cursor.execute("SELECT cover_image FROM poems WHERE id = %s", (poem_id,))
        result = cursor.fetchone()
        cover_filename = result[0] if result else f"poem_{poem_id}_cover.jpg"

        print(f"\n📄 Poem {poem_id}: {title[:40]}")
        print(f"   Creating thumbnail...")

        create_elegant_thumbnail(poem_id, title, lyrics, cover_filename)
        print(f"   ✅ Saved: {cover_filename}")

    print("\n" + "=" * 60)
    print("✅ Thumbnail generation complete!")

    cursor.close()
    conn.close()

if __name__ == "__main__":
    main()
