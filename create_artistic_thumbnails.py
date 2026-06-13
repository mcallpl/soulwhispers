#!/usr/bin/env python3
"""Create ARTISTIC graphic thumbnails inspired by poem content."""

import os
import mysql.connector
from PIL import Image, ImageDraw, ImageFont, ImageFilter
import random
import math

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

def draw_particles(draw, width, height, color, count=50, seed=None):
    """Draw artistic particle effects."""
    if seed is not None:
        random.seed(seed)

    for _ in range(count):
        x = random.randint(0, width)
        y = random.randint(0, height)
        size = random.randint(1, 3)
        opacity = random.randint(50, 200)
        draw.ellipse([(x, y), (x+size, y+size)], fill=color)

def draw_flowing_lines(draw, width, height, color, count=8):
    """Draw flowing, organic lines inspired by water/wind."""
    for i in range(count):
        points = []
        start_y = int(height * (i / count))
        for x in range(0, width + 50, 50):
            y = start_y + int(math.sin(x / 100) * 30)
            points.append((x, y))

        if len(points) > 1:
            draw.line(points, fill=color, width=2)

def draw_gradient_overlay(img, color1_hex, color2_hex, direction='diagonal'):
    """Apply a gradient overlay to the image."""
    width, height = img.size
    gradient = Image.new('RGBA', (width, height))
    grad_draw = ImageDraw.Draw(gradient)

    # Parse hex colors to RGB
    color1 = tuple(int(color1_hex.lstrip('#')[i:i+2], 16) for i in (0, 2, 4))
    color2 = tuple(int(color2_hex.lstrip('#')[i:i+2], 16) for i in (0, 2, 4))

    for i in range(width):
        ratio = i / width
        r = int(color1[0] * (1 - ratio) + color2[0] * ratio)
        g = int(color1[1] * (1 - ratio) + color2[1] * ratio)
        b = int(color1[2] * (1 - ratio) + color2[2] * ratio)
        grad_draw.rectangle([(i, 0), (i+1, height)], fill=(r, g, b, 80))

    img = Image.alpha_composite(img.convert('RGBA'), gradient).convert('RGB')
    return img

def create_artistic_thumbnail(poem_id, title, lyrics, filename):
    """Create an artistic, visually stunning thumbnail inspired by the poem."""

    # Define artistic themes based on poem content
    themes = {
        1: {  # "The whisper grows..."
            'name': 'whisper',
            'colors': ['#1a1035', '#c8a84b', '#e8c47a', '#7b9fd4'],
            'accent': '#c8a84b',
            'accent_light': '#e8c47a',
            'style': 'flowing'
        },
        2: {  # "The whisper grows, a yearning..."
            'name': 'yearning',
            'colors': ['#1a0f2e', '#7b9fd4', '#a8c5e0', '#c8a84b'],
            'accent': '#7b9fd4',
            'accent_light': '#a8c5e0',
            'style': 'waves'
        },
        4: {  # "music"
            'name': 'music',
            'colors': ['#0a1428', '#6c9fd4', '#9eb8d0', '#c8a84b'],
            'accent': '#6c9fd4',
            'accent_light': '#9eb8d0',
            'style': 'rhythmic'
        },
        6: {  # "This time, too, passes..."
            'name': 'time',
            'colors': ['#0f1a18', '#7ba888', '#a8d0b8', '#c8a84b'],
            'accent': '#7ba888',
            'accent_light': '#a8d0b8',
            'style': 'flowing'
        },
        8: {  # "The tide pulled out..."
            'name': 'tide',
            'colors': ['#0a0f20', '#5b8f9f', '#7ba8b8', '#c8a84b'],
            'accent': '#5b8f9f',
            'accent_light': '#7ba8b8',
            'style': 'waves'
        },
        9: {  # "Until death, all defeat..."
            'name': 'resilience',
            'colors': ['#220f26', '#d4847b', '#e0a89f', '#c8a84b'],
            'accent': '#d4847b',
            'accent_light': '#e0a89f',
            'style': 'rising'
        },
        11: {  # "There is a tree..."
            'name': 'growth',
            'colors': ['#0a1428', '#7ba888', '#a8d0b8', '#c8a84b'],
            'accent': '#7ba888',
            'accent_light': '#a8d0b8',
            'style': 'rising'
        },
        12: {  # "Until death, all defeat..."
            'name': 'resilience',
            'colors': ['#220f26', '#d4847b', '#e0a89f', '#c8a84b'],
            'accent': '#d4847b',
            'accent_light': '#e0a89f',
            'style': 'rising'
        },
    }

    theme = themes.get(poem_id, {
        'name': 'default',
        'colors': ['#0f0c22', '#c8a84b', '#e8c47a', '#7b9fd4'],
        'accent': '#c8a84b',
        'accent_light': '#e8c47a',
        'style': 'flowing'
    })

    # Create high-quality image
    width, height = 500, 500
    img = Image.new('RGB', (width, height), color=theme['colors'][0])
    draw = ImageDraw.Draw(img, 'RGBA')

    # Create artistic background based on theme
    if theme['style'] == 'flowing':
        # Draw flowing, organic waves
        for i in range(0, height, 40):
            points = []
            for x in range(0, width + 50, 25):
                y = i + int(math.sin(x / 50 + i / 100) * 20)
                points.append((x, y))
            draw.line(points, fill=theme['accent'], width=1)

        # Add particle effects
        draw_particles(draw, width, height, theme['accent_light'], count=100, seed=poem_id)

    elif theme['style'] == 'waves':
        # Draw wave patterns
        for i in range(0, width + 100, 60):
            points = []
            for y in range(0, height + 50, 25):
                x = i + int(math.cos(y / 50 + i / 100) * 25)
                points.append((x, y))
            draw.line(points, fill=theme['accent'], width=2)

        # Add flowing accent particles
        for _ in range(150):
            x = random.randint(0, width)
            y = random.randint(0, height)
            size = random.randint(1, 2)
            draw.ellipse([(x, y), (x+size, y+size)], fill=theme['accent_light'])

    elif theme['style'] == 'rhythmic':
        # Draw musical/rhythmic patterns
        for i in range(5):
            for j in range(5):
                x = 50 + i * 100
                y = 50 + j * 100
                size = random.randint(20, 50)
                opacity = random.randint(80, 200)
                draw.ellipse([(x-size//2, y-size//2), (x+size//2, y+size//2)],
                           outline=theme['accent_light'], width=2)

    elif theme['style'] == 'rising':
        # Draw ascending/rising patterns
        for i in range(0, height, 30):
            draw.line([(0, i + (i//50)*10), (width, i - (i//50)*10)],
                     fill=theme['accent'], width=2)

        # Add upward-flowing particles
        for _ in range(100):
            x = random.randint(0, width)
            y = random.randint(0, height)
            draw.ellipse([(x, y), (x+2, y+2)], fill=theme['accent_light'])

    # Draw decorative border with gradient
    border_width = 3
    draw.rectangle([(border_width//2, border_width//2),
                   (width-border_width//2, height-border_width//2)],
                  outline=theme['accent_light'], width=border_width)

    # Draw accent corners
    corner_size = 40
    draw.polygon([(0, 0), (corner_size, 0), (0, corner_size)],
                fill=theme['accent_light'])
    draw.polygon([(width, height), (width-corner_size, height), (width, height-corner_size)],
                fill=theme['accent_light'])

    # Add subtle gradient overlay
    img = draw_gradient_overlay(img, theme['colors'][0], theme['accent'], 'diagonal')

    # Draw title with artistic styling
    try:
        title_font = ImageFont.truetype("/System/Library/Fonts/Helvetica.ttc", 32)
    except:
        title_font = ImageFont.load_default()

    # Extract key words from title
    words = title.split()[:3]
    display_text = ' '.join(words)

    # Draw title with glow effect
    bbox = draw.textbbox((0, 0), display_text, font=title_font)
    text_width = bbox[2] - bbox[0]
    text_x = (width - text_width) // 2
    text_y = height // 2 - 30

    # Draw text with subtle shadow/glow
    # Parse accent light color
    accent_light_rgb = tuple(int(theme['accent_light'].lstrip('#')[i:i+2], 16) for i in (0, 2, 4))

    for offset in range(4, 0, -1):
        shadow_color = accent_light_rgb + (offset * 50,)
        draw.text((text_x, text_y + offset), display_text,
                 font=title_font, fill=shadow_color)

    # Main text
    draw.text((text_x, text_y), display_text,
             font=title_font, fill=theme['accent_light'])

    # Save as high-quality JPEG
    output_path = os.path.join(COVER_DIR, filename)
    img = img.convert('RGB')
    img.save(output_path, 'JPEG', quality=95)

    return output_path

def main():
    conn = get_db_connection()
    cursor = conn.cursor()

    cursor.execute("SELECT id, title, lyrics FROM poems ORDER BY id")
    poems = cursor.fetchall()

    print("\n🎨 Creating ARTISTIC Graphic Thumbnails\n")
    print("=" * 60)

    for poem_id, title, lyrics in poems:
        cursor.execute("SELECT cover_image FROM poems WHERE id = %s", (poem_id,))
        result = cursor.fetchone()
        cover_filename = result[0] if result else f"poem_{poem_id}_cover.jpg"

        print(f"\n🖼️  Poem {poem_id}: {title[:40]}")
        print(f"   Creating artistic graphic...")

        create_artistic_thumbnail(poem_id, title, lyrics, cover_filename)
        print(f"   ✅ Saved: {cover_filename}")

    print("\n" + "=" * 60)
    print("✅ Artistic thumbnail generation complete!")

    cursor.close()
    conn.close()

if __name__ == "__main__":
    main()
