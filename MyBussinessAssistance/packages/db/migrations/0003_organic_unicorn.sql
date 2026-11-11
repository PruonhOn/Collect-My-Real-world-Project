CREATE TABLE `employees` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`branch_id` text,
	`name` text NOT NULL,
	`phone` text,
	`email` text,
	`position` text,
	`department` text,
	`salary_cents` integer DEFAULT 0 NOT NULL,
	`currency` text DEFAULT 'USD' NOT NULL,
	`hire_date` integer,
	`status` text DEFAULT 'active' NOT NULL,
	`notes` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE INDEX `employees_workspace_idx` ON `employees` (`workspace_id`);--> statement-breakpoint
CREATE INDEX `employees_branch_idx` ON `employees` (`branch_id`);--> statement-breakpoint
CREATE INDEX `employees_workspace_status_idx` ON `employees` (`workspace_id`,`status`);--> statement-breakpoint
CREATE TABLE `order_items` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`order_id` text NOT NULL,
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
	FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE INDEX `order_items_order_idx` ON `order_items` (`order_id`);--> statement-breakpoint
CREATE INDEX `order_items_product_idx` ON `order_items` (`product_id`);--> statement-breakpoint
CREATE TABLE `orders` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`branch_id` text NOT NULL,
	`customer_id` text,
	`customer_name` text,
	`number` text NOT NULL,
	`status` text DEFAULT 'pending' NOT NULL,
	`payment_method` text DEFAULT 'cash' NOT NULL,
	`currency` text DEFAULT 'USD' NOT NULL,
	`subtotal_cents` integer DEFAULT 0 NOT NULL,
	`discount_cents` integer DEFAULT 0 NOT NULL,
	`tax_cents` integer DEFAULT 0 NOT NULL,
	`total_cents` integer DEFAULT 0 NOT NULL,
	`paid_cents` integer DEFAULT 0 NOT NULL,
	`order_date` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`expected_date` integer,
	`note` text,
	`sale_id` text,
	`created_by_user_id` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON UPDATE no action ON DELETE no action,
	FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON UPDATE no action ON DELETE set null,
	FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON UPDATE no action ON DELETE set null,
	FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE UNIQUE INDEX `orders_workspace_number_unique` ON `orders` (`workspace_id`,`number`);--> statement-breakpoint
CREATE INDEX `orders_workspace_date_idx` ON `orders` (`workspace_id`,`order_date`);--> statement-breakpoint
CREATE INDEX `orders_customer_idx` ON `orders` (`customer_id`);--> statement-breakpoint
CREATE INDEX `orders_branch_idx` ON `orders` (`branch_id`);--> statement-breakpoint
CREATE INDEX `orders_workspace_status_idx` ON `orders` (`workspace_id`,`status`);--> statement-breakpoint
CREATE TABLE `stock_transfer_items` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`transfer_id` text NOT NULL,
	`product_id` text,
	`product_name` text NOT NULL,
	`sku` text,
	`quantity` integer NOT NULL,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE INDEX `stock_transfer_items_transfer_idx` ON `stock_transfer_items` (`transfer_id`);--> statement-breakpoint
CREATE INDEX `stock_transfer_items_product_idx` ON `stock_transfer_items` (`product_id`);--> statement-breakpoint
CREATE TABLE `stock_transfers` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`from_branch_id` text NOT NULL,
	`to_branch_id` text NOT NULL,
	`number` text NOT NULL,
	`status` text DEFAULT 'pending' NOT NULL,
	`note` text,
	`created_by_user_id` text,
	`completed_at` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`from_branch_id`) REFERENCES `branches`(`id`) ON UPDATE no action ON DELETE no action,
	FOREIGN KEY (`to_branch_id`) REFERENCES `branches`(`id`) ON UPDATE no action ON DELETE no action,
	FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE UNIQUE INDEX `stock_transfers_workspace_number_unique` ON `stock_transfers` (`workspace_id`,`number`);--> statement-breakpoint
CREATE INDEX `stock_transfers_workspace_created_idx` ON `stock_transfers` (`workspace_id`,`created_at`);--> statement-breakpoint
CREATE INDEX `stock_transfers_from_idx` ON `stock_transfers` (`from_branch_id`);--> statement-breakpoint
CREATE INDEX `stock_transfers_to_idx` ON `stock_transfers` (`to_branch_id`);