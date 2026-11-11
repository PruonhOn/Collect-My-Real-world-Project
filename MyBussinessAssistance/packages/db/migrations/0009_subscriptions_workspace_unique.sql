DROP INDEX IF EXISTS `subscriptions_workspace_idx`;--> statement-breakpoint
CREATE UNIQUE INDEX IF NOT EXISTS `subscriptions_workspace_unique` ON `subscriptions` (`workspace_id`);
