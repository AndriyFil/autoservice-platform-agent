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
        'create_new_estimate_pdf' => 'Create new estimate PDF',
        'start_work' => 'Start work',
        'view_booking_request' => 'View booking request',
    ],

    'estimate_errors' => [
        'missing_lines' => 'Add at least one repair order line before creating an estimate.',
        'repair_order_locked' => 'This repair order can no longer generate estimate PDFs.',
    ],

    'fields' => [
        'actions' => 'Actions',
        'closed' => 'Closed',
        'customer' => 'Customer',
        'description' => 'Description',
        'filename' => 'Filename',
        'generated' => 'Generated',
        'opened' => 'Opened',
        'order' => 'Order',
        'original_message' => 'Original message',
        'pdf' => 'PDF',
        'preferred_date' => 'Preferred date',
        'problem' => 'Problem',
        'quantity' => 'Qty',
        'requires_estimate_approval' => 'Require customer approval for estimate',
        'source' => 'Source',
        'source_request' => 'Source request',
        'status' => 'Status',
        'tax' => 'Tax',
        'total' => 'Total',
        'type' => 'Type',
        'unit' => 'Unit',
        'unit_price' => 'Unit price',
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
        'booking_request_source' => 'Booking request',
        'estimate_approval_behavior' => 'Estimate approval behavior for this repair order.',
        'estimate_approval_locked' => 'This setting cannot be changed after the repair order is completed or cancelled.',
        'manual_repair_order' => 'Manual repair order',
        'no_documents' => 'No documents available yet.',
        'no_estimate_pdfs' => 'No estimate PDFs created yet.',
        'no_estimate_summary' => 'No estimate created yet.',
        'no_vehicle' => 'No vehicle',
        'no_working_lines' => 'No working lines yet.',
        'requires_estimate_approval_help' => 'If enabled, the estimate should be approved by the customer before work starts. Disable for simple jobs confirmed outside the system.',
        'saved' => 'Saved.',
        'saving' => 'Saving...',
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
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'status_actions' => [
        'draft' => 'Move to draft',
        'in_progress' => 'Start work',
        'completed' => 'Complete order',
        'cancelled' => 'Cancel order',
    ],

    'sections' => [
        'estimates' => 'Estimates',
        'latest_estimate' => 'Latest estimate',
        'order_settings' => 'Order settings',
        'timeline' => 'Timeline',
        'work_document' => 'Work document',
        'working_lines' => 'Working repair order lines',
        'working_totals' => 'Working totals',
    ],

    'tabs' => [
        'documents' => 'Documents',
        'estimates' => 'Estimates',
        'lines' => 'Lines',
        'overview' => 'Overview',
        'timeline' => 'Timeline',
    ],

    'totals' => [
        'subtotal' => 'Subtotal',
        'tax' => 'Tax',
        'total' => 'Total',
    ],

    'units' => [
        'document_singular' => 'document',
        'document_plural' => 'documents',
        'line_singular' => 'line',
        'line_plural' => 'lines',
        'version_singular' => 'version',
        'version_plural' => 'versions',
    ],
];
