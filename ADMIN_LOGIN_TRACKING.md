# Admin Login Tracking & Analytics

## Overview
Added login tracking functionality to the Soul Whispers admin system with a super_admin-only analytics dashboard.

## Changes Made

### 1. Database Schema (Non-Destructive)
**File:** `migrations/001_add_login_tracking.sql`

Added two columns to the `admin_users` table:
- `last_login_at DATETIME NULL` - Timestamp of the most recent successful login
- `login_count INT NOT NULL DEFAULT 0` - Total count of successful logins

Indexes created for efficient queries:
- `idx_login_count` on `login_count DESC`
- `idx_last_login_at` on `last_login_at DESC`

**Previous schema** (unchanged):
```
id                (int, PRIMARY KEY, auto_increment)
username          (varchar(100), UNIQUE)
password_hash     (varchar(255))
full_name         (varchar(255), nullable)
role              (varchar(50), default 'admin')
created_at        (timestamp, DEFAULT CURRENT_TIMESTAMP)
updated_at        (timestamp, auto-updates on change)
```

**New schema**:
```
id                (int, PRIMARY KEY, auto_increment)
username          (varchar(100), UNIQUE)
password_hash     (varchar(255))
full_name         (varchar(255), nullable)
role              (varchar(50), default 'admin')
last_login_at     (datetime, nullable) ← NEW
login_count       (int, default 0)      ← NEW
created_at        (timestamp, DEFAULT CURRENT_TIMESTAMP)
updated_at        (timestamp, auto-updates on change)
```

### 2. Login Tracking Logic
**File:** `admin/login.php`

After successful password verification, the login handler now:
1. Updates `last_login_at` to the current timestamp
2. Increments `login_count` by 1

**Important:** Login tracking only happens on **successful** logins (after password verification). Failed login attempts do not increment the counter.

```php
// New code added after password_verify() succeeds:
$updateQuery = "UPDATE admin_users SET last_login_at = NOW(), login_count = login_count + 1 WHERE id = ?";
$updateStmt = $conn->prepare($updateQuery);
if ($updateStmt) {
    $updateStmt->bind_param('i', $user['id']);
    $updateStmt->execute();
    $updateStmt->close();
}
```

### 3. Admin Analytics Dashboard
**File:** `admin/analytics.php` (NEW)

A super_admin-only page that displays admin login statistics and activity.

**Access Control:**
- Page checks `is_super_admin()` and redirects to dashboard if user is not super_admin
- Non-super_admin users cannot access this page

**Statistics Displayed:**
- **Total Admins** - Count of all admin users in the system
- **Total Logins** - Sum of all login_count values across all admins
- **Active Today** - Count of admins who logged in today (since midnight)
- **Average Logins** - Total logins divided by number of admins

**Admin Activity Table:**
Displays all admin users with columns:
- Username
- Full Name
- Role (super_admin or admin, with color-coded badges)
- Last Login (time format for today, date+time for older)
- Login Count (bold, gold color)
- Account Created Date

### 4. Admin Dashboard Update
**File:** `admin/index.php`

Added analytics link in the admin header controls (super_admin only):
```html
<?php if (is_super_admin()): ?>
<a href="analytics.php" class="btn btn-primary">📊 Analytics</a>
<?php endif; ?>
```

The analytics button only appears for super_admin users.

## Testing Checklist

### ✅ Login Tracking Verification
```bash
# Check that login_count increments on successful login
mysql -u mcallpl -p"amazing123" soulwhispers -e "SELECT username, login_count, last_login_at FROM admin_users;"

# Before login: login_count = 0, last_login_at = NULL
# After login: login_count = 1, last_login_at = 2026-06-13 HH:MM:SS
```

### ✅ Access Control Verification

1. **Super_admin Access:**
   - Login as `mcallpl` (super_admin)
   - Navigate to `/admin/index.php`
   - Verify "📊 Analytics" button is visible
   - Click analytics button - should load the page
   - Verify login statistics and admin table display

2. **Regular Admin Access:**
   - Login as `lagunabuddha` (admin)
   - Navigate to `/admin/index.php`
   - Verify "📊 Analytics" button is NOT visible
   - Try direct access: `/admin/analytics.php`
   - Should redirect to `/admin/index.php` (no access)

### ✅ Data Accuracy
- Verify `last_login_at` shows correct timestamp
- Verify `login_count` increments by 1 on each login
- Verify failed logins don't increment counter
- Verify "Active Today" statistic correctly identifies recent logins

## Database Migration

Applied to local environment. To apply to production:

```bash
# SSH into production server
ssh root@64.227.108.128

# Run migration
mysql -u mcallpl -p"<password>" soulwhispers < /var/www/html/soulwhispers/migrations/001_add_login_tracking.sql

# Verify columns were added
mysql -u mcallpl -p"<password>" soulwhispers -e "DESCRIBE admin_users;"
```

## Files Modified

| File | Type | Changes |
|------|------|---------|
| `migrations/001_add_login_tracking.sql` | NEW | Database migration: add 2 columns, 2 indexes |
| `admin/login.php` | MODIFIED | Add login tracking after successful authentication |
| `admin/analytics.php` | NEW | Super_admin-only analytics dashboard (380 lines) |
| `admin/index.php` | MODIFIED | Add analytics button link (super_admin only) |

## Auth Pattern

Following existing Soul Whispers auth pattern:
- Uses `require_admin_login()` to check authentication
- Uses `is_super_admin()` to check role-based access
- Redirects unauthorized users to index.php
- Reuses `get_admin_username()` and other existing helpers
- No new authentication system created

## Design Consistency

Analytics page matches SoulWhispers design:
- Dark navy/black background (#0a0e27, #13192b)
- Gold accents (#d4af37)
- Playfair Display and EB Garamond fonts
- Responsive grid layout
- Existing admin.css styling patterns

## Security Notes

- Login tracking uses prepared statements to prevent SQL injection
- Analytics page restricted to super_admin role
- No sensitive data exposed (passwords not displayed)
- Login history is audit-friendly (tracks who logged in when)
- Failed logins are not tracked (no counter bloat from brute force attempts)

## No Breaking Changes

- All existing fields preserved
- All existing functionality unchanged
- Existing login process unaffected (just adds tracking)
- Regular admin users see no UI changes (analytics hidden from them)
- Non-super_admin users cannot access analytics even with direct URL

## Current Admin Users

Based on database inspection:

| Username | Full Name | Role | Last Login | Login Count |
|----------|-----------|------|------------|-------------|
| mcallpl | Chip McAllister | super_admin | — | 0 |
| lagunabuddha | Farid Tabrizy | admin | — | 0 |

After first login of each user, these values will populate with actual data.
