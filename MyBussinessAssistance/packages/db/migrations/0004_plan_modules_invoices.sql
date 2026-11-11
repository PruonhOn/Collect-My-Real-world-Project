UPDATE plan_entitlements
SET value = '["dashboard","products","inventory","sales","invoices","purchases","customers","suppliers","expenses","reports","employees","settings","subscription"]'
WHERE id = 'ent_free_modules';

UPDATE plan_entitlements
SET value = '["dashboard","products","inventory","sales","invoices","purchases","orders","customers","suppliers","expenses","reports","employees","branches","transfers","ai","settings","subscription"]'
WHERE id = 'ent_pro_modules';
