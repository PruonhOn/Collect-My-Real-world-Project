<?php

return [
    'name' => 'Cambodia Logistics System',
    'version' => '1.0.0',
    'currencies' => ['USD', 'KHR'],
    'default_currency' => 'USD',
    'shipment_types' => ['local_delivery', 'import', 'export', 'container', 'air_freight', 'sea_freight'],
    'shipment_statuses' => ['created', 'pending', 'warehouse_received', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled'],
    'delivery_statuses' => ['pending', 'assigned', 'in_transit', 'delivered', 'failed', 'returned'],
    'vehicle_statuses' => ['available', 'in_use', 'maintenance', 'out_of_service'],
    'driver_statuses' => ['available', 'on_trip', 'off_duty', 'suspended'],
    'invoice_statuses' => ['paid', 'unpaid', 'partial', 'cancelled', 'overdue'],
    'payment_methods' => ['cash', 'bank_transfer', 'cheque', 'credit_card', 'other'],
    'expense_categories' => ['fuel', 'maintenance', 'salary', 'warehouse', 'utilities', 'office', 'travel', 'other'],
    'transaction_categories' => [
        'income' => ['shipping_revenue', 'service_revenue', 'other_income'],
        'expense' => ['fuel', 'maintenance', 'salary', 'warehouse_cost', 'utilities', 'other_expense'],
    ],
    'per_page' => 15,
];
