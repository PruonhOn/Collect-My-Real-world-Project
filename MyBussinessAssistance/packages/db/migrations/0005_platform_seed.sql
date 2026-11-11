INSERT OR IGNORE INTO plans (id, name, slug, description, monthly_price_cents, yearly_price_cents, currency, trial_days, is_active, sort_order) VALUES
  ('plan_free', 'Free', 'free', 'Essential tools to get a small business organized.', 0, 0, 'USD', 0, 1, 1),
  ('plan_pro', 'Pro', 'pro', 'Advanced analytics, multi-branch, and the AI assistant.', 1900, 19000, 'USD', 14, 1, 2),
  ('plan_enterprise', 'Enterprise', 'enterprise', 'Custom limits, advanced controls, and priority support.', 4900, 49000, 'USD', 14, 1, 3);

INSERT OR IGNORE INTO plan_entitlements (id, plan_id, key, value) VALUES
  ('ent_free_max_products', 'plan_free', 'max_products', '100'),
  ('ent_free_max_employees', 'plan_free', 'max_employees', '3'),
  ('ent_free_max_branches', 'plan_free', 'max_branches', '1'),
  ('ent_free_max_transactions_per_month', 'plan_free', 'max_transactions_per_month', '300'),
  ('ent_free_max_workspaces', 'plan_free', 'max_workspaces', '1'),
  ('ent_free_storage_mb', 'plan_free', 'storage_mb', '200'),
  ('ent_free_ai_credits_per_month', 'plan_free', 'ai_credits_per_month', '0'),
  ('ent_free_modules', 'plan_free', 'modules', '["dashboard","products","inventory","sales","invoices","purchases","customers","suppliers","expenses","reports","employees","settings","subscription"]'),
  ('ent_pro_max_products', 'plan_pro', 'max_products', '5000'),
  ('ent_pro_max_employees', 'plan_pro', 'max_employees', '25'),
  ('ent_pro_max_branches', 'plan_pro', 'max_branches', '5'),
  ('ent_pro_max_transactions_per_month', 'plan_pro', 'max_transactions_per_month', '10000'),
  ('ent_pro_max_workspaces', 'plan_pro', 'max_workspaces', '3'),
  ('ent_pro_storage_mb', 'plan_pro', 'storage_mb', '5000'),
  ('ent_pro_ai_credits_per_month', 'plan_pro', 'ai_credits_per_month', '500'),
  ('ent_pro_modules', 'plan_pro', 'modules', '["dashboard","products","inventory","sales","invoices","purchases","orders","customers","suppliers","expenses","reports","employees","branches","transfers","ai","settings","subscription"]'),
  ('ent_ent_max_products', 'plan_enterprise', 'max_products', '1000000'),
  ('ent_ent_max_employees', 'plan_enterprise', 'max_employees', '1000'),
  ('ent_ent_max_branches', 'plan_enterprise', 'max_branches', '100'),
  ('ent_ent_max_transactions_per_month', 'plan_enterprise', 'max_transactions_per_month', '1000000'),
  ('ent_ent_max_workspaces', 'plan_enterprise', 'max_workspaces', '100'),
  ('ent_ent_storage_mb', 'plan_enterprise', 'storage_mb', '100000'),
  ('ent_ent_ai_credits_per_month', 'plan_enterprise', 'ai_credits_per_month', '10000'),
  ('ent_ent_modules', 'plan_enterprise', 'modules', '["dashboard","products","inventory","sales","purchases","orders","customers","suppliers","invoices","expenses","reports","employees","branches","transfers","ai","settings","subscription","audit","api"]');

INSERT OR IGNORE INTO permissions (id, key, category, description) VALUES
  ('perm_workspace_manage', 'workspace:manage', 'workspace', 'Manage workspace profile and danger-zone actions'),
  ('perm_member_read', 'member:read', 'workspace', 'View workspace members'),
  ('perm_member_invite', 'member:invite', 'workspace', 'Invite new members'),
  ('perm_member_manage', 'member:manage', 'workspace', 'Change member roles and remove members'),
  ('perm_branch_read', 'branch:read', 'branches', 'View branches'),
  ('perm_branch_manage', 'branch:manage', 'branches', 'Create and edit branches'),
  ('perm_product_read', 'product:read', 'products', 'View products'),
  ('perm_product_create', 'product:create', 'products', 'Create products'),
  ('perm_product_update', 'product:update', 'products', 'Edit products'),
  ('perm_product_delete', 'product:delete', 'products', 'Delete products'),
  ('perm_inventory_read', 'inventory:read', 'inventory', 'View stock levels'),
  ('perm_inventory_update', 'inventory:update', 'inventory', 'Adjust stock levels'),
  ('perm_inventory_transfer', 'inventory:transfer', 'inventory', 'Create and approve stock transfers'),
  ('perm_sale_read', 'sale:read', 'sales', 'View sales'),
  ('perm_sale_create', 'sale:create', 'sales', 'Create sales'),
  ('perm_sale_update', 'sale:update', 'sales', 'Edit sales'),
  ('perm_sale_delete', 'sale:delete', 'sales', 'Void sales'),
  ('perm_sale_approve', 'sale:approve', 'sales', 'Approve sales and payments'),
  ('perm_sale_refund', 'sale:refund', 'sales', 'Refund sales'),
  ('perm_purchase_read', 'purchase:read', 'purchases', 'View purchases'),
  ('perm_purchase_create', 'purchase:create', 'purchases', 'Create purchases'),
  ('perm_purchase_update', 'purchase:update', 'purchases', 'Edit purchases'),
  ('perm_purchase_approve', 'purchase:approve', 'purchases', 'Approve purchases'),
  ('perm_customer_read', 'customer:read', 'customers', 'View customers'),
  ('perm_customer_create', 'customer:create', 'customers', 'Create customers'),
  ('perm_customer_update', 'customer:update', 'customers', 'Edit customers'),
  ('perm_customer_delete', 'customer:delete', 'customers', 'Delete customers'),
  ('perm_supplier_read', 'supplier:read', 'suppliers', 'View suppliers'),
  ('perm_supplier_create', 'supplier:create', 'suppliers', 'Create suppliers'),
  ('perm_supplier_update', 'supplier:update', 'suppliers', 'Edit suppliers'),
  ('perm_supplier_delete', 'supplier:delete', 'suppliers', 'Delete suppliers'),
  ('perm_expense_read', 'expense:read', 'expenses', 'View expenses'),
  ('perm_expense_create', 'expense:create', 'expenses', 'Create expenses'),
  ('perm_expense_update', 'expense:update', 'expenses', 'Edit expenses'),
  ('perm_expense_delete', 'expense:delete', 'expenses', 'Delete expenses'),
  ('perm_expense_approve', 'expense:approve', 'expenses', 'Approve expenses'),
  ('perm_employee_read', 'employee:read', 'employees', 'View employees'),
  ('perm_employee_create', 'employee:create', 'employees', 'Create employees'),
  ('perm_employee_update', 'employee:update', 'employees', 'Edit employees'),
  ('perm_employee_delete', 'employee:delete', 'employees', 'Delete employees'),
  ('perm_report_read', 'report:read', 'reports', 'View reports'),
  ('perm_report_export', 'report:export', 'reports', 'Export reports'),
  ('perm_settings_read', 'settings:read', 'settings', 'View business settings'),
  ('perm_settings_manage', 'settings:manage', 'settings', 'Edit business settings'),
  ('perm_notification_read', 'notification:read', 'notifications', 'View notifications'),
  ('perm_ai_use', 'ai:use', 'ai', 'Use the AI business assistant'),
  ('perm_subscription_read', 'subscription:read', 'subscription', 'View subscription and usage'),
  ('perm_subscription_manage', 'subscription:manage', 'subscription', 'Change subscription plan');

INSERT OR IGNORE INTO roles (id, workspace_id, key, name, is_system) VALUES
  ('role_owner', NULL, 'owner', 'Business Owner', 1),
  ('role_admin', NULL, 'admin', 'Business Admin', 1),
  ('role_manager', NULL, 'manager', 'Manager', 1),
  ('role_accountant', NULL, 'accountant', 'Accountant', 1),
  ('role_inventory_manager', NULL, 'inventory_manager', 'Inventory Manager', 1),
  ('role_sales_staff', NULL, 'sales_staff', 'Sales Staff', 1);

INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 'role_owner', id FROM permissions;

INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 'role_admin', id FROM permissions WHERE key <> 'workspace:manage';

INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 'role_manager', id FROM permissions WHERE key IN (
    'member:read', 'branch:read', 'branch:manage',
    'product:read', 'product:create', 'product:update', 'product:delete',
    'inventory:read', 'inventory:update', 'inventory:transfer',
    'sale:read', 'sale:create', 'sale:update', 'sale:delete', 'sale:approve', 'sale:refund',
    'purchase:read', 'purchase:create', 'purchase:update', 'purchase:approve',
    'customer:read', 'customer:create', 'customer:update', 'customer:delete',
    'supplier:read', 'supplier:create', 'supplier:update', 'supplier:delete',
    'expense:read', 'expense:create', 'expense:update', 'expense:approve',
    'employee:read', 'employee:create', 'employee:update',
    'report:read', 'report:export', 'notification:read', 'ai:use',
    'settings:read', 'subscription:read'
  );

INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 'role_accountant', id FROM permissions WHERE key IN (
    'branch:read', 'product:read', 'inventory:read',
    'sale:read', 'sale:approve', 'purchase:read', 'purchase:approve',
    'customer:read', 'supplier:read',
    'expense:read', 'expense:create', 'expense:update', 'expense:approve',
    'employee:read', 'report:read', 'report:export', 'notification:read',
    'settings:read', 'subscription:read'
  );

INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 'role_inventory_manager', id FROM permissions WHERE key IN (
    'branch:read', 'product:read', 'product:create', 'product:update',
    'inventory:read', 'inventory:update', 'inventory:transfer',
    'purchase:read', 'purchase:create', 'purchase:update',
    'supplier:read', 'supplier:create', 'supplier:update',
    'report:read', 'report:export', 'notification:read', 'settings:read'
  );

INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
  SELECT 'role_sales_staff', id FROM permissions WHERE key IN (
    'product:read', 'inventory:read',
    'sale:read', 'sale:create',
    'customer:read', 'customer:create',
    'notification:read'
  );
