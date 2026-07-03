<?php

return [
    'estimate_created' => 'Кошторис PDF створено.',
    'estimate_regenerated' => 'Кошторис PDF створено повторно.',

    'actions' => [
        'add_line' => 'Додати позицію',
        'cancel' => 'Скасувати',
        'complete' => 'Завершити',
        'create_estimate_pdf' => 'Створити PDF кошторису',
        'regenerate_estimate_pdf' => 'Повторити PDF кошторису',
        'delete' => 'Видалити',
        'download' => 'Завантажити',
        'edit' => 'Редагувати',
        'save' => 'Зберегти',
        'view_booking_request' => 'Переглянути заявку',
    ],

    'estimate_errors' => [
        'missing_lines' => 'Додайте щонайменше одну позицію замовлення на ремонт перед створенням кошторису.',
    ],

    'regenerate_errors' => [
        'no_estimate' => 'Немає кошторису для повторного створення для цього замовлення на ремонт.',
        'estimate_locked' => 'Цей кошторис більше не можна створити повторно, оскільки його вже затверджено.',
        'repair_order_locked' => 'Після затвердження замовлення на ремонт кошторис не можна створити повторно.',
    ],

    'fields' => [
        'actions' => 'Дії',
        'closed' => 'Закрито',
        'customer' => 'Клієнт',
        'description' => 'Опис',
        'generated' => 'Створено',
        'opened' => 'Відкрито',
        'order' => 'Порядок',
        'original_message' => 'Оригінальне повідомлення',
        'pdf' => 'PDF',
        'preferred_date' => 'Бажана дата',
        'problem' => 'Проблема',
        'quantity' => 'К-сть',
        'source' => 'Джерело',
        'source_request' => 'Вихідна заявка',
        'status' => 'Статус',
        'tax' => 'Податок',
        'total' => 'Разом',
        'type' => 'Тип',
        'unit' => 'Ціна',
        'unit_cents' => 'Ціна в центах',
        'vehicle' => 'Авто',
        'version' => 'Версія',
    ],

    'line_types' => [
        'labor' => 'Робота',
        'part' => 'Запчастина',
        'fee' => 'Плата',
        'discount' => 'Знижка',
    ],

    'messages' => [
        'manual_repair_order' => 'Ручне замовлення на ремонт',
        'no_estimate_pdfs' => 'PDF кошторису ще не створено.',
        'no_vehicle' => 'Авто не вказано',
        'no_working_lines' => 'Робочих позицій ще немає.',
    ],

    'navigation' => [
        'repair_orders' => 'Замовлення на ремонт',
    ],

    'pdf' => [
        'title' => 'Кошторис #:version',
        'heading' => 'Кошторис v:version',
        'generated' => 'Створено :date',
        'repair_order' => 'Замовлення на ремонт #:id',
        'customer' => 'Клієнт',
        'lines_heading' => 'Позиції кошторису',
        'columns' => [
            'type' => 'Тип',
            'description' => 'Опис',
            'quantity' => 'К-сть',
            'unit' => 'Ціна',
            'tax' => 'Податок',
            'total' => 'Разом',
        ],
        'totals' => [
            'subtotal' => 'Проміжний підсумок',
            'tax' => 'Податок',
            'total' => 'Разом',
        ],
    ],

    'repair_order_statuses' => [
        'draft' => 'Чернетка',
        'estimated' => 'Оцінено',
        'approved' => 'Схвалено',
        'in_progress' => 'У роботі',
        'completed' => 'Завершено',
        'cancelled' => 'Скасовано',
    ],

    'sections' => [
        'estimates' => 'Кошториси',
        'timeline' => 'Хронологія',
        'work_document' => 'Робочий документ',
        'working_lines' => 'Робочі позиції замовлення на ремонт',
        'working_totals' => 'Робочий підсумок',
    ],

    'totals' => [
        'subtotal' => 'Проміжний підсумок',
        'tax' => 'Податок',
        'total' => 'Разом',
    ],

    'units' => [
        'line_singular' => 'позиція',
        'line_plural' => 'позицій',
        'version_singular' => 'версія',
        'version_plural' => 'версій',
    ],
];
