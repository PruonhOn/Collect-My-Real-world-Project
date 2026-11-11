<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Feature catalogue. Every entitlement in the product is registered here
     * as a database row so that plan assignments are fully data-driven.
     *
     * type:
     *   boolean -> on/off capability
     *   limit   -> numeric ceiling (null limit == unlimited)
     *   quota   -> externally metered quota
     */
    public const FEATURES = [
        // key, name, description, type, default_enabled, default_limit
        ['multi_branch', 'Multi-branch management', 'Manage several physical branches for one business.', 'boolean', false, null],
        ['advanced_reports', 'Advanced reports', 'Date-range analytics, sales trends and exports.', 'boolean', false, null],
        ['profit_reports', 'Profit reporting', 'Profit analysis based on product cost data.', 'boolean', false, null],
        ['best_selling_reports', 'Best-selling product reports', 'Rank products by units sold and revenue.', 'boolean', false, null],
        ['custom_roles', 'Custom roles', 'Create bespoke roles with granular permissions.', 'boolean', false, null],
        ['branded_invoices', 'Branded invoices', 'Issue invoices with your own business branding.', 'boolean', false, null],
        ['api_access', 'API access', 'Programmatic access through revocable API tokens.', 'boolean', false, null],
        ['inventory_management', 'Inventory management', 'Track stock levels and movements.', 'boolean', false, null],
        ['low_stock_alerts', 'Low-stock alerts', 'Alerts when stock falls below a threshold.', 'boolean', false, null],
        ['stock_history', 'Stock history', 'Full audit trail of stock movements.', 'boolean', false, null],
        ['pdf_excel_exports', 'PDF and Excel exports', 'Export data as PDF or Excel workbooks.', 'boolean', false, null],
        ['discounts_coupons', 'Discounts and coupons', 'Apply coupons and business discounts.', 'boolean', false, null],
        ['tax_settings', 'Tax settings', 'Configure tax rates applied to orders and invoices.', 'boolean', false, null],
        ['business_activity_history', 'Business activity history', 'Review who did what across the workspace.', 'boolean', false, null],
        ['priority_support', 'Priority support', 'Priority response from platform support.', 'boolean', false, null],
        ['basic_dashboard', 'Basic dashboard', 'Sales, order and customer overview.', 'boolean', true, null],
        ['limited_exports', 'Limited CSV exports', 'Export basic lists as CSV.', 'boolean', true, null],
        ['core_settings', 'Core business settings', 'Profile, currency and time zone configuration.', 'boolean', true, null],

        ['max_businesses', 'Businesses per user', 'Number of businesses a user may own.', 'limit', true, 1],
        ['max_products', 'Products', 'Maximum number of products and services.', 'limit', true, 50],
        ['max_customers', 'Customers', 'Maximum number of customer records.', 'limit', true, 100],
        ['max_members', 'Team members', 'Maximum number of active members.', 'limit', true, 2],
        ['max_orders_monthly', 'Orders per month', 'Maximum number of orders per calendar month.', 'limit', true, 100],
        ['max_branches', 'Branches', 'Maximum number of branches.', 'limit', true, 0],
    ];

    /**
     * Plan catalogue. Prices are stored in minor units (cents).
     */
    public const PLANS = [
        [
            'name' => 'Free',
            'slug' => 'free',
            'type' => 'free',
            'description' => 'Essential tools to run a single business.',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'currency' => 'USD',
            'sort_order' => 1,
            'trial_enabled' => false,
            'trial_days' => 0,
            'features' => [
                ['max_businesses', true, 1],
                ['max_products', true, 50],
                ['max_customers', true, 100],
                ['max_members', true, 2],
                ['max_orders_monthly', true, 100],
                ['max_branches', true, 0],
                ['basic_dashboard', true, null],
                ['limited_exports', true, null],
                ['core_settings', true, null],
            ],
        ],
        [
            'name' => 'Pro Monthly',
            'slug' => 'pro-monthly',
            'type' => 'pro',
            'description' => 'Everything in Free plus advanced operations, reports and automation.',
            'price_monthly' => 2900,
            'price_yearly' => 29000,
            'currency' => 'USD',
            'sort_order' => 2,
            'trial_enabled' => true,
            'trial_days' => 14,
            'features' => [
                ['max_businesses', true, 1],
                ['max_products', true, null],
                ['max_customers', true, null],
                ['max_members', true, 20],
                ['max_orders_monthly', true, null],
                ['max_branches', true, null],
                ['basic_dashboard', true, null],
                ['limited_exports', true, null],
                ['core_settings', true, null],
                ['multi_branch', true, null],
                ['advanced_reports', true, null],
                ['profit_reports', true, null],
                ['best_selling_reports', true, null],
                ['custom_roles', true, null],
                ['branded_invoices', true, null],
                ['api_access', true, null],
                ['inventory_management', true, null],
                ['low_stock_alerts', true, null],
                ['stock_history', true, null],
                ['pdf_excel_exports', true, null],
                ['discounts_coupons', true, null],
                ['tax_settings', true, null],
                ['business_activity_history', true, null],
                ['priority_support', true, null],
            ],
        ],
        [
            'name' => 'Pro Yearly',
            'slug' => 'pro-yearly',
            'type' => 'pro',
            'description' => 'Everything in Pro, billed once per year at a reduced rate.',
            'price_monthly' => 2900,
            'price_yearly' => 27840,
            'currency' => 'USD',
            'sort_order' => 3,
            'trial_enabled' => true,
            'trial_days' => 14,
            'features' => [
                ['max_businesses', true, 1],
                ['max_products', true, null],
                ['max_customers', true, null],
                ['max_members', true, 20],
                ['max_orders_monthly', true, null],
                ['max_branches', true, null],
                ['basic_dashboard', true, null],
                ['limited_exports', true, null],
                ['core_settings', true, null],
                ['multi_branch', true, null],
                ['advanced_reports', true, null],
                ['profit_reports', true, null],
                ['best_selling_reports', true, null],
                ['custom_roles', true, null],
                ['branded_invoices', true, null],
                ['api_access', true, null],
                ['inventory_management', true, null],
                ['low_stock_alerts', true, null],
                ['stock_history', true, null],
                ['pdf_excel_exports', true, null],
                ['discounts_coupons', true, null],
                ['tax_settings', true, null],
                ['business_activity_history', true, null],
                ['priority_support', true, null],
            ],
        ],
    ];

    public function run(): void
    {
        $featureIds = [];

        foreach (self::FEATURES as [$key, $name, $description, $type, $defaultEnabled, $defaultLimit]) {
            $feature = Feature::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'description' => $description,
                    'type' => $type,
                    'default_enabled' => $defaultEnabled,
                    'default_limit' => $defaultLimit,
                    'metadata' => null,
                ]
            );

            $featureIds[$key] = $feature->id;
        }

        foreach (self::PLANS as $planData) {
            $features = $planData['features'];
            unset($planData['features']);

            $plan = Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );

            $sync = [];
            foreach ($features as [$featureKey, $enabled, $limit]) {
                $sync[$featureIds[$featureKey]] = [
                    'enabled' => $enabled,
                    'limit_value' => $limit,
                ];
            }

            $plan->features()->sync($sync);
        }
    }
}
