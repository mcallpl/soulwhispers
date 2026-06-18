#!/usr/bin/env python3
"""
Create favicon.ico and OG image for Soul Whispers
Matches the dark, elegant design theme with gold accents
"""

from PIL import Image, ImageDraw, ImageFont
import os

# Color scheme
DARK_PRIMARY = "#1a1035"  # Dark navy
GOLD_PRIMARY = "#c8a84b"  # Gold
BLUE_ACCENT = "#7b9fd4"   # Blue
TEXT_PRIMARY = "#f5f5f5"  # Light text

def create_favicon():
    """Create favicon.ico with elegant design"""
    # Create base image
    size = 512
    img = Image.new('RGB', (size, size), DARK_PRIMARY)
    draw = ImageDraw.Draw(img, 'RGBA')

    # Draw background gradient effect
    for y in range(size):
        ratio = y / size
        r = int(26 * (1 - ratio * 0.3))
        g = int(16 * (1 - ratio * 0.3))
        b = int(53 * (1 - ratio * 0.3))
        draw.line([(0, y), (size, y)], fill=(r, g, b, 255))

    # Draw a subtle moon/orb in center
    center = size // 2
    radius = 80

    # Outer glow circles
    for i in range(3):
        glow_radius = radius + (i * 20)
        alpha = int(100 - (i * 30))
        draw.ellipse(
            [(center - glow_radius, center - glow_radius),
             (center + glow_radius, center + glow_radius)],
            outline=(int(0xc8), int(0xa8), int(0x4b), alpha)
        )

    # Main moon/orb
    draw.ellipse(
        [(center - radius, center - radius),
         (center + radius, center + radius)],
        fill=(0xc8, 0xa8, 0x4b, 220)
    )

    # Inner highlight for depth
    inner_r = radius - 20
    draw.ellipse(
        [(center - inner_r + 15, center - inner_r + 15),
         (center + inner_r - 15, center + inner_r - 15)],
        fill=(0xe8, 0xc4, 0x7a, 150)
    )

    # Add some orbiting stars
    import math
    for i in range(5):
        angle = (i * 72) * math.pi / 180  # 5 stars evenly distributed
        orbit_radius = radius + 100
        star_x = center + int(orbit_radius * math.cos(angle))
        star_y = center + int(orbit_radius * math.sin(angle))
        star_radius = 8
        draw.ellipse(
            [(star_x - star_radius, star_y - star_radius),
             (star_x + star_radius, star_y + star_radius)],
            fill=(0xc8, 0xa8, 0x4b, 200)
        )

    # Save as different sizes for favicon
    assets_dir = "assets"
    os.makedirs(assets_dir, exist_ok=True)

    # Save PNG versions
    img.save(f"{assets_dir}/favicon-32.png")
    img.resize((64, 64)).save(f"{assets_dir}/favicon-64.png")
    img.resize((128, 128)).save(f"{assets_dir}/favicon-128.png")
    img.resize((256, 256)).save(f"{assets_dir}/favicon-256.png")

    # Create favicon.ico from 32x32 version
    favicon_img = img.resize((32, 32))
    favicon_img.save(f"{assets_dir}/favicon.ico")

    print("✓ Favicon files created successfully")

def create_og_image():
    """Create Open Graph image for social media sharing"""
    width, height = 1200, 630
    img = Image.new('RGB', (width, height), DARK_PRIMARY)
    draw = ImageDraw.Draw(img, 'RGBA')

    # Background gradient
    for y in range(height):
        ratio = y / height
        r = int(26 * (1 - ratio * 0.3))
        g = int(16 * (1 - ratio * 0.3))
        b = int(53 * (1 - ratio * 0.3))
        draw.line([(0, y), (width, y)], fill=(r, g, b, 255))

    # Draw decorative moon/orb on right side
    moon_x = width - 150
    moon_y = height // 2
    moon_r = 120

    # Moon glow
    for i in range(3):
        glow_r = moon_r + (i * 30)
        alpha = int(80 - (i * 20))
        draw.ellipse(
            [(moon_x - glow_r, moon_y - glow_r),
             (moon_x + glow_r, moon_y + glow_r)],
            outline=(0xc8, 0xa8, 0x4b, alpha)
        )

    # Moon core
    draw.ellipse(
        [(moon_x - moon_r, moon_y - moon_r),
         (moon_x + moon_r, moon_y + moon_r)],
        fill=(0xc8, 0xa8, 0x4b, 200)
    )

    # Stars on left side
    import math
    star_positions = [
        (100, 100), (200, 150), (150, 250),
        (250, 80), (120, 400), (280, 350),
        (100, 500), (200, 520)
    ]
    for x, y in star_positions:
        draw.ellipse([(x-6, y-6), (x+6, y+6)], fill=(0xc8, 0xa8, 0x4b, 180))

    # Add text
    try:
        # Try to use a nice serif font if available
        title_font = ImageFont.truetype("/System/Library/Fonts/Garamond.ttc", 80)
        subtitle_font = ImageFont.truetype("/System/Library/Fonts/Garamond.ttc", 40)
        tagline_font = ImageFont.truetype("/System/Library/Fonts/Garamond.ttc", 28)
    except:
        # Fallback to default font
        title_font = ImageFont.load_default()
        subtitle_font = ImageFont.load_default()
        tagline_font = ImageFont.load_default()

    # Main title "Soul Whispers"
    title_text = "Soul Whispers"
    title_bbox = draw.textbbox((0, 0), title_text, font=title_font)
    title_width = title_bbox[2] - title_bbox[0]
    title_x = (width - title_width - 200) // 2
    title_y = height // 2 - 100
    draw.text((title_x, title_y), title_text, fill=(0xc8, 0xa8, 0x4b, 255), font=title_font)

    # Subtitle "Farid Tabrizy"
    subtitle_text = "Farid Tabrizy"
    subtitle_bbox = draw.textbbox((0, 0), subtitle_text, font=subtitle_font)
    subtitle_width = subtitle_bbox[2] - subtitle_bbox[0]
    subtitle_x = (width - subtitle_width - 200) // 2
    subtitle_y = title_y + 100
    draw.text((subtitle_x, subtitle_y), subtitle_text, fill=(0x7b, 0x9f, 0xd4, 255), font=subtitle_font)

    # Tagline "Poetry that breathes"
    tagline_text = "Poetry that breathes"
    tagline_bbox = draw.textbbox((0, 0), tagline_text, font=tagline_font)
    tagline_width = tagline_bbox[2] - tagline_bbox[0]
    tagline_x = (width - tagline_width - 200) // 2
    tagline_y = subtitle_y + 70
    draw.text((tagline_x, tagline_y), tagline_text, fill=(0xb0, 0xb0, 0xb0, 255), font=tagline_font)

    # Save
    assets_dir = "assets"
    os.makedirs(assets_dir, exist_ok=True)
    img.save(f"{assets_dir}/soul_whispers_og.png")

    print("✓ Open Graph image created successfully")

def create_favicon_svg():
    """Create SVG favicon version"""
    svg_content = '''<?xml version="1.0" encoding="UTF-8"?>
<svg width="512" height="512" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="bgGradient" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" style="stop-color:#1a1035;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#0a0718;stop-opacity:1" />
        </linearGradient>
        <radialGradient id="moonGlow" cx="35%" cy="35%">
            <stop offset="0%" style="stop-color:#e8c47a;stop-opacity:0.8" />
            <stop offset="100%" style="stop-color:#c8a84b;stop-opacity:1" />
        </radialGradient>
        <filter id="glow">
            <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
            <feMerge>
                <feMergeNode in="coloredBlur"/>
                <feMergeNode in="SourceGraphic"/>
            </feMerge>
        </filter>
    </defs>

    <!-- Background -->
    <rect width="512" height="512" fill="url(#bgGradient)"/>

    <!-- Outer glow circles -->
    <circle cx="256" cy="256" r="180" fill="none" stroke="#c8a84b" stroke-width="2" opacity="0.3"/>
    <circle cx="256" cy="256" r="160" fill="none" stroke="#c8a84b" stroke-width="1" opacity="0.2"/>

    <!-- Main orb/moon -->
    <circle cx="256" cy="256" r="100" fill="url(#moonGlow)" filter="url(#glow)"/>

    <!-- Inner highlight -->
    <circle cx="275" cy="235" r="35" fill="#e8c47a" opacity="0.6"/>

    <!-- Orbiting stars -->
    <g id="stars">
        <circle cx="256" cy="120" r="8" fill="#c8a84b"/>
        <circle cx="370" cy="175" r="8" fill="#c8a84b"/>
        <circle cx="345" cy="310" r="8" fill="#c8a84b"/>
        <circle cx="167" cy="310" r="8" fill="#c8a84b"/>
        <circle cx="142" cy="175" r="8" fill="#c8a84b"/>
    </g>
</svg>'''

    assets_dir = "assets"
    os.makedirs(assets_dir, exist_ok=True)
    with open(f"{assets_dir}/favicon.svg", "w") as f:
        f.write(svg_content)

    print("✓ SVG favicon created successfully")

if __name__ == "__main__":
    print("Creating favicon and OG image for Soul Whispers...")
    create_favicon_svg()
    create_favicon()
    create_og_image()
    print("\n✓ All images created successfully!")
    print("  - favicon.svg")
    print("  - favicon.ico (32x32)")
    print("  - favicon-32.png")
    print("  - favicon-64.png")
    print("  - favicon-128.png")
    print("  - favicon-256.png")
    print("  - soul_whispers_og.png (1200x630)")
