CREATE TABLE `payroll_runs` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`branch_id` text,
	`number` text NOT NULL,
	`period_start` integer NOT NULL,
	`period_end` integer NOT NULL,
	`status` text DEFAULT 'draft' NOT NULL,
	`total_cents` integer DEFAULT 0 NOT NULL,
	`paid_cents` integer DEFAULT 0 NOT NULL,
	`paid_at` integer,
	`note` text,
	`created_by_user_id` text,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON UPDATE no action ON DELETE set null,
	FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON UPDATE no action ON DELETE set null
);
--> statement-breakpoint
CREATE UNIQUE INDEX `payroll_runs_workspace_number_unique` ON `payroll_runs` (`workspace_id`,`number`);--> statement-breakpoint
CREATE INDEX `payroll_runs_workspace_idx` ON `payroll_runs` (`workspace_id`);--> statement-breakpoint
CREATE TABLE `payroll_items` (
	`id` text PRIMARY KEY NOT NULL,
	`workspace_id` text NOT NULL,
	`run_id` text NOT NULL,
	`employee_id` text NOT NULL,
	`salary_cents` integer DEFAULT 0 NOT NULL,
	`allowances_cents` integer DEFAULT 0 NOT NULL,
	`deductions_cents` integer DEFAULT 0 NOT NULL,
	`net_cents` integer DEFAULT 0 NOT NULL,
	`paid_at` integer,
	`created_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	`updated_at` integer DEFAULT (unixepoch() * 1000) NOT NULL,
	FOREIGN KEY (`workspace_id`) REFERENCES `workspaces`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`run_id`) REFERENCES `payroll_runs`(`id`) ON UPDATE no action ON DELETE cascade,
	FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON UPDATE no action ON DELETE cascade
);
--> statement-breakpoint
CREATE INDEX `payroll_items_run_idx` ON `payroll_items` (`run_id`);--> statement-breakpoint
CREATE INDEX `payroll_items_employee_idx` ON `payroll_items` (`employee_id`);
