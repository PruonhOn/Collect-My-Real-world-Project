INSERT OR IGNORE INTO permissions (id, key, category, description) VALUES
  ('perm_payroll_read', 'payroll:read', 'payroll', 'View payroll runs'),
  ('perm_payroll_create', 'payroll:create', 'payroll', 'Create payroll runs'),
  ('perm_payroll_update', 'payroll:update', 'payroll', 'Edit payroll runs'),
  ('perm_payroll_delete', 'payroll:delete', 'payroll', 'Cancel payroll runs');

UPDATE plan_entitlements
SET value = '["dashboard","products","inventory","sales","invoices","purchases","customers","suppliers","expenses","reports","employees","payroll","settings","subscription"]'
WHERE id = 'ent_free_modules';

UPDATE plan_entitlements
SET value = '["dashboard","products","inventory","sales","invoices","purchases","orders","customers","suppliers","expenses","reports","employees","branches","transfers","payroll","ai","settings","subscription"]'
WHERE id = 'ent_pro_modules';

UPDATE plan_entitlements
SET value = '["dashboard","products","inventory","sales","purchases","orders","customers","suppliers","invoices","expenses","reports","employees","branches","transfers","payroll","ai","settings","subscription","audit","api"]'
WHERE id = 'ent_ent_modules';

INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 'role_owner', id FROM permissions WHERE key LIKE 'payroll:%';

INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 'role_admin', id FROM permissions WHERE key LIKE 'payroll:%';

INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 'role_manager', id FROM permissions WHERE key IN ('payroll:read');

INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 'role_accountant', id FROM permissions WHERE key IN ('payroll:read', 'payroll:create');
