CREATE TABLE `categories` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`name` text NOT NULL,
	`description` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE UNIQUE INDEX `categories_workspace_name_unique` ON `categories` (`workspace_id`,`name`);--> statement-breakpoint
CREATE INDEX `categories_workspace_idx` ON `categories` (`workspace_id`);--> statement-breakpoint
CREATE TABLE `customers` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`name` text NOT NULL,
	`phone` text,
	`email` text,
	`address` text,
	`type` text DEFAULT 'individual' NOT NULL,
	`credit_limit_cents` integer DEFAULT 0 NOT NULL,
	`notes` text,
	`status` text DEFAULT 'active' NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE INDEX `customers_workspace_idx` ON `customers` (`workspace_id`);--> statement-breakpoint
CREATE INDEX `customers_workspace_phone_idx` ON `customers` (`workspace_id`,`phone`);--> statement-breakpoint
CREATE TABLE `expenses` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`branch_id` text,
	`category` text NOT NULL,
	`description` text NOT NULL,
	`amount_cents` integer NOT NULL,
	`currency` text DEFAULT 'USD' NOT NULL,
	`payment_method` text DEFAULT 'cash' NOT NULL,
	`vendor` text,
	`reference` text,
	`expense_date` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`status` text DEFAULT 'recorded' NOT NULL,
	`created_by_user_id` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON UPDATE no action ON DELETE set null,
	FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE INDEX `expenses_workspace_date_idx` ON `expenses` (`workspace_id`,`expense_date`);--> statement-breakpoint
CREATE INDEX `expenses_category_idx` ON `expenses` (`workspace_id`,`category`);--> statement-breakpoint
CREATE TABLE `inventory_levels` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`branch_id` text NOT NULL,
	`product_id` text NOT NULL,
	`quantity` integer DEFAULT 0 NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE UNIQUE INDEX `inventory_levels_branch_product_unique` ON `inventory_levels` (`branch_id`,`product_id`);--> statement-breakpoint
CREATE INDEX `inventory_levels_workspace_idx` ON `inventory_levels` (`workspace_id`);--> statement-breakpoint
CREATE TABLE `products` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`category_id` text,
	`name` text NOT NULL,
	`sku` text NOT NULL,
	`barcode` text,
	`description` text,
	`unit` text DEFAULT 'pcs' NOT NULL,
	`cost_price_cents` integer DEFAULT 0 NOT NULL,
	`selling_price_cents` integer DEFAULT 0 NOT NULL,
	`tax_rate_basis_points` integer,
	`track_inventory` integer DEFAULT true NOT NULL,
	`low_stock_threshold` integer DEFAULT 5 NOT NULL,
	`status` text DEFAULT 'active' NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE UNIQUE INDEX `products_workspace_sku_unique` ON `products` (`workspace_id`,`sku`);--> statement-breakpoint
CREATE UNIQUE INDEX `products_workspace_barcode_unique` ON `products` (`workspace_id`,`barcode`);--> statement-breakpoint
CREATE INDEX `products_workspace_status_idx` ON `products` (`workspace_id`,`status`);--> statement-breakpoint
CREATE INDEX `products_category_idx` ON `products` (`category_id`);--> statement-breakpoint
CREATE TABLE `purchase_items` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`purchase_id` text NOT NULL,
	`product_id` text,
	`product_name` text NOT NULL,
	`sku` text,
	`quantity` integer NOT NULL,
	`unit_cost_cents` integer NOT NULL,
	`subtotal_cents` integer DEFAULT 0 NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE INDEX `purchase_items_purchase_idx` ON `purchase_items` (`purchase_id`);--> statement-breakpoint
CREATE INDEX `purchase_items_product_idx` ON `purchase_items` (`product_id`);--> statement-breakpoint
CREATE TABLE `purchases` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`branch_id` text NOT NULL,
	`supplier_id` text,
	`supplier_name` text,
	`number` text NOT NULL,
	`status` text DEFAULT 'received' NOT NULL,
	`payment_status` text DEFAULT 'unpaid' NOT NULL,
	`payment_method` text DEFAULT 'cash' NOT NULL,
	`currency` text DEFAULT 'USD' NOT NULL,
	`subtotal_cents` integer DEFAULT 0 NOT NULL,
	`discount_cents` integer DEFAULT 0 NOT NULL,
	`tax_cents` integer DEFAULT 0 NOT NULL,
	`total_cents` integer DEFAULT 0 NOT NULL,
	`paid_cents` integer DEFAULT 0 NOT NULL,
	`balance_cents` integer DEFAULT 0 NOT NULL,
	`purchase_date` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`note` text,
	`stock_applied` integer DEFAULT true NOT NULL,
	`created_by_user_id` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON UPDATE no action ON DELETE no action,
	FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON UPDATE no action ON DELETE set null,
	FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE UNIQUE INDEX `purchases_workspace_number_unique` ON `purchases` (`workspace_id`,`number`);--> statement-breakpoint
CREATE INDEX `purchases_workspace_date_idx` ON `purchases` (`workspace_id`,`purchase_date`);--> statement-breakpoint
CREATE INDEX `purchases_supplier_idx` ON `purchases` (`supplier_id`);--> statement-breakpoint
CREATE TABLE `sale_items` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`sale_id` text NOT NULL,
	`product_id` text,
	`product_name` text NOT NULL,
	`sku` text,
	`quantity` integer NOT NULL,
	`unit_price_cents` integer NOT NULL,
	`cost_price_cents` integer DEFAULT 0 NOT NULL,
	`discount_cents` integer DEFAULT 0 NOT NULL,
	`tax_rate_basis_points` integer DEFAULT 0 NOT NULL,
	`subtotal_cents` integer DEFAULT 0 NOT NULL,
	`tax_cents` integer DEFAULT 0 NOT NULL,
	`total_cents` integer DEFAULT 0 NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE INDEX `sale_items_sale_idx` ON `sale_items` (`sale_id`);--> statement-breakpoint
CREATE INDEX `sale_items_product_idx` ON `sale_items` (`product_id`);--> statement-breakpoint
CREATE TABLE `sales` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`branch_id` text NOT NULL,
	`customer_id` text,
	`customer_name` text,
	`number` text NOT NULL,
	`status` text DEFAULT 'completed' NOT NULL,
	`payment_status` text DEFAULT 'unpaid' NOT NULL,
	`payment_method` text DEFAULT 'cash' NOT NULL,
	`currency` text DEFAULT 'USD' NOT NULL,
	`exchange_rate_khr_per_usd` integer DEFAULT 4100 NOT NULL,
	`subtotal_cents` integer DEFAULT 0 NOT NULL,
	`discount_cents` integer DEFAULT 0 NOT NULL,
	`tax_cents` integer DEFAULT 0 NOT NULL,
	`total_cents` integer DEFAULT 0 NOT NULL,
	`paid_cents` integer DEFAULT 0 NOT NULL,
	`balance_cents` integer DEFAULT 0 NOT NULL,
	`sale_date` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`due_date` integer,
	`note` text,
	`created_by_user_id` text,
	`voided_at` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON UPDATE no action ON DELETE no action,
	FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON UPDATE no action ON DELETE set null,
	FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE UNIQUE INDEX `sales_workspace_number_unique` ON `sales` (`workspace_id`,`number`);--> statement-breakpoint
CREATE INDEX `sales_workspace_date_idx` ON `sales` (`workspace_id`,`sale_date`);--> statement-breakpoint
CREATE INDEX `sales_customer_idx` ON `sales` (`customer_id`);--> statement-breakpoint
CREATE INDEX `sales_branch_idx` ON `sales` (`branch_id`);--> statement-breakpoint
CREATE TABLE `stock_movements` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`branch_id` text NOT NULL,
	`product_id` text NOT NULL,
	`type` text NOT NULL,
	`quantity` integer NOT NULL,
	`unit_cost_cents` integer,
	`reference_type` text,
	`reference_id` text,
	`note` text,
	`created_by_user_id` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE INDEX `stock_movements_workspace_created_idx` ON `stock_movements` (`workspace_id`,`created_at`);--> statement-breakpoint
CREATE INDEX `stock_movements_branch_product_idx` ON `stock_movements` (`branch_id`,`product_id`);--> statement-breakpoint
CREATE TABLE `suppliers` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`name` text NOT NULL,
	`contact_name` text,
	`phone` text,
	`email` text,
	`address` text,
	`notes` text,
	`status` text DEFAULT 'active' NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE INDEX `suppliers_workspace_idx` ON `suppliers` (`workspace_id`);