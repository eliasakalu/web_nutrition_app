-- ============================================================
--  Smart Meal Planner & Health Nutrition System
--  Person 1: Authentication & User Profile — Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS nutriplan
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nutriplan;

-- ── users table ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  name        VARCHAR(120)     NOT NULL,
  email       VARCHAR(191)     NOT NULL,
  password    VARCHAR(255)     NOT NULL,          -- bcrypt hash (cost 12)
  age         TINYINT UNSIGNED     NULL,
  weight      DECIMAL(5,2)         NULL,          -- kilograms
  height      DECIMAL(5,2)         NULL,          -- centimetres
  gender      ENUM(
                'male',
                'female',
                'prefer_not'
              )                    NULL,
  goal        ENUM(
                'lose_weight',
                'maintain',
                'gain_muscle',
                'improve_health'
              )                    NULL,
  created_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE  KEY uq_users_email (email)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ── sessions (optional — use if switching to DB sessions) ──
-- Uncomment if you need PHP database-backed sessions
-- CREATE TABLE IF NOT EXISTS sessions (
--   id         VARCHAR(128) NOT NULL PRIMARY KEY,
--   user_id    INT UNSIGNED     NULL,
--   payload    TEXT         NOT NULL,
--   last_activity INT UNSIGNED NOT NULL,
--   INDEX idx_sessions_user (user_id)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
