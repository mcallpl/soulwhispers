-- Migration: Add login tracking to admin_users table
-- Date: 2026-06-13
-- Description: Add last_login_at and login_count columns to track admin user logins

USE soulwhispers;

-- Add columns to admin_users table
ALTER TABLE admin_users
ADD COLUMN last_login_at DATETIME NULL AFTER role,
ADD COLUMN login_count INT NOT NULL DEFAULT 0 AFTER last_login_at;

-- Create index on login_count for easy sorting
CREATE INDEX idx_login_count ON admin_users(login_count DESC);
CREATE INDEX idx_last_login_at ON admin_users(last_login_at DESC);
