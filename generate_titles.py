#!/usr/bin/env python3
"""Generate AI titles for poems in the database."""

import os
import mysql.connector
from anthropic import Anthropic

# API key should be set via environment variable ANTHROPIC_API_KEY

def get_db_connection():
    """Connect to MySQL database."""
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USER", "mcallpl"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME", "soulwhispers")
    )

def generate_title(lyrics):
    """Generate a title for the poem using Claude."""
    try:
        client = Anthropic()

        # Extract first few lines for context
        lines = [line[line.find('] ')+2:] if '] ' in line else line
                 for line in lyrics.split('\n') if line.strip()]
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
        return title

    except Exception as e:
        print(f"  ❌ Error: {str(e)}")
        return None

def main():
    """Generate titles for all poems in the database."""
    conn = get_db_connection()
    cursor = conn.cursor()

    # Get all poems
    cursor.execute("SELECT id, title, lyrics FROM poems ORDER BY id")
    poems = cursor.fetchall()

    print(f"🎨 Generating AI Titles for {len(poems)} Poems\n")
    print("=" * 60)

    for poem_id, current_title, lyrics in poems:
        print(f"\n📄 Poem ID {poem_id}")
        print(f"  Current: '{current_title}'")

        # Generate new title
        new_title = generate_title(lyrics)

        if new_title:
            # Update in database
            cursor.execute("UPDATE poems SET title = %s WHERE id = %s", (new_title, poem_id))
            conn.commit()
            print(f"  ✅ New: '{new_title}'")
        else:
            print(f"  ⚠️  Skipped (generation failed)")

    print("\n" + "=" * 60)
    print("✅ Title generation complete!")

    cursor.close()
    conn.close()

if __name__ == "__main__":
    main()
