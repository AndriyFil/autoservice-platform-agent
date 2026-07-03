<?php

return [
    'estimate_created' => 'Estimate PDF created.',

    'actions' => [
        'add_line' => 'Add line',
        'cancel' => 'Cancel',
        'complete' => 'Complete',
        'create_estimate_pdf' => 'Create estimate PDF',
        'delete' => 'Delete',
        'download' => 'Download',
        'edit' => 'Edit',
        'save' => 'Save',
        'view_booking_request' => 'View booking request',
    ],

    'estimate_errors' => [
        'missing_lines' => 'Add at least one repair order line before creating an estimate.',
    ],

    'estimate_statuses' => [
        'draft' => 'Draft',
        'generated' => 'Generated',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'superseded' => 'Superseded',
        'cancelled' => 'Cancelled',
    ],

    'fields' => [
        'actions' => 'Actions',
        'closed' => 'Closed',
        'customer' => 'Customer',
        'description' => 'Description',
        'generated' => 'Generated',
        'opened' => 'Opened',
        'order' => 'Order',
        'original_message' => 'Original message',
        'pdf' => 'PDF',
        'preferred_date' => 'Preferred date',
        'problem' => 'Problem',
        'quantity' => 'Qty',
        'source' => 'Source',
        'source_request' => 'Source request',
        'status' => 'Status',
        'tax' => 'Tax',
        'total' => 'Total',
        'type' => 'Type',
        'unit' => 'Unit',
        'unit_cents' => 'Unit cents',
        'vehicle' => 'Vehicle',
        'version' => 'Version',
    ],

    'line_types' => [
        'labor' => 'Labor',
        'part' => 'Part',
        'fee' => 'Fee',
        'discount' => 'Discount',
    ],

    'messages' => [
        'manual_repair_order' => 'Manual repair order',
        'no_estimate_pdfs' => 'No estimate PDFs created yet.',
        'no_vehicle' => 'No vehicle',
        'no_working_lines' => 'No working lines yet.',
    ],

    'navigation' => [
        'repair_orders' => 'Repair orders',
    ],

    'pdf' => [
        'title' => 'Estimate #:version',
        'heading' => 'Estimate v:version',
        'generated' => 'Generated :date',
        'repair_order' => 'Repair order #:id',
        'customer' => 'Customer',
        'lines_heading' => 'Estimate lines',
        'columns' => [
            'type' => 'Type',
            'description' => 'Description',
            'quantity' => 'Qty',
            'unit' => 'Unit',
            'tax' => 'Tax',
            'total' => 'Total',
        ],
        'totals' => [
            'subtotal' => 'Subtotal',
            'tax' => 'Tax',
            'total' => 'Total',
        ],
    ],

    'repair_order_statuses' => [
        'draft' => 'Draft',
        'estimated' => 'Estimated',
        'approved' => 'Approved',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'sections' => [
        'estimates' => 'Estimates',
        'timeline' => 'Timeline',
        'work_document' => 'Work document',
        'working_lines' => 'Working repair order lines',
        'working_totals' => 'Working totals',
    ],

    'totals' => [
        'subtotal' => 'Subtotal',
        'tax' => 'Tax',
        'total' => 'Total',
    ],

    'units' => [
        'line_singular' => 'line',
        'line_plural' => 'lines',
        'version_singular' => 'version',
        'version_plural' => 'versions',
    ],
];
