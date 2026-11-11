-- Seed test super-admin user for local development.
-- Email: mybussiness@123   Password: admin123
-- Hash verified against apps/api/src/lib/crypto.ts verifyPassword.

DELETE FROM workspace_members WHERE user_id = 'usr_test_admin';
DELETE FROM platform_admins WHERE user_id = 'usr_test_admin';
DELETE FROM sessions WHERE user_id = 'usr_test_admin';
DELETE FROM auth_tokens WHERE user_id = 'usr_test_admin';
DELETE FROM workspaces WHERE owner_user_id = 'usr_test_admin';
DELETE FROM users WHERE id = 'usr_test_admin';

INSERT INTO users (id, email, password_hash, name, locale, status, email_verified_at)
VALUES (
  'usr_test_admin',
  'mybussiness@123',
  'pbkdf2$600000$v+2kEK3VSKS5DjEl9hbbEA==$kLiRju1Fd6xNuy5MjfnP6N7KI2QX1mOE7PZqyC0A9KA=',
  'Test Admin',
  'en',
  'active',
  unixepoch() * 1000
);

INSERT OR IGNORE INTO platform_admins (id, user_id, role)
VALUES ('pa_test_admin', 'usr_test_admin', 'super_admin');

INSERT OR IGNORE INTO workspaces (id, name, slug, owner_user_id, default_currency, locale, timezone, status)
VALUES (
  'ws_test_admin',
  'Test Business',
  'test-business',
  'usr_test_admin',
  'USD',
  'en',
  'Asia/Phnom_Penh',
  'active'
);

INSERT OR IGNORE INTO roles (id, workspace_id, key, name, is_system)
VALUES ('role_test_admin_owner', 'ws_test_admin', 'owner', 'Business Owner', 1);

INSERT OR IGNORE INTO workspace_members (id, workspace_id, user_id, role_id, status, joined_at)
VALUES ('wm_test_admin', 'ws_test_admin', 'usr_test_admin', 'role_test_admin_owner', 'active', unixepoch() * 1000);