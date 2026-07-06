<?php

return [
    'estimate_created' => 'Кошторис PDF створено.',

    'actions' => [
        'add_line' => 'Додати позицію',
        'cancel' => 'Скасувати',
        'complete' => 'Завершити',
        'create_estimate_pdf' => 'Створити PDF кошторису',
        'delete' => 'Видалити',
        'download' => 'Завантажити',
        'edit' => 'Редагувати',
        'save' => 'Зберегти',
        'regenerate_estimate_pdf' => 'Повторно створити PDF кошторису',
        'start_work' => 'Почати роботу',
        'view_booking_request' => 'Переглянути заявку',
    ],

    'estimate_errors' => [
        'missing_lines' => 'Додайте щонайменше одну позицію замовлення на ремонт перед створенням кошторису.',
        'repair_order_locked' => 'PDF кошторису більше не можна створити для цього замовлення на ремонт.',
    ],

    'fields' => [
        'actions' => 'Дії',
        'closed' => 'Закрито',
        'customer' => 'Клієнт',
        'description' => 'Опис',
        'filename' => 'Назва файлу',
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
        'unit_price' => 'Ціна',
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
        'booking_request_source' => 'Заявка',
        'manual_repair_order' => 'Ручне замовлення на ремонт',
        'no_documents' => 'Документів ще немає.',
        'no_estimate_pdfs' => 'PDF кошторису ще не створено.',
        'no_estimate_summary' => 'Кошторис ще не створено.',
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
        'in_progress' => 'У роботі',
        'completed' => 'Завершено',
        'cancelled' => 'Скасовано',
    ],

    'sections' => [
        'estimates' => 'Кошториси',
        'latest_estimate' => 'Останній кошторис',
        'timeline' => 'Хронологія',
        'work_document' => 'Робочий документ',
        'working_lines' => 'Робочі позиції замовлення на ремонт',
        'working_totals' => 'Робочий підсумок',
    ],

    'tabs' => [
        'documents' => 'Документи',
        'estimates' => 'Кошториси',
        'lines' => 'Позиції',
        'overview' => 'Огляд',
        'timeline' => 'Хронологія',
    ],

    'totals' => [
        'subtotal' => 'Проміжний підсумок',
        'tax' => 'Податок',
        'total' => 'Разом',
    ],

    'units' => [
        'document_singular' => 'документ',
        'document_plural' => 'документів',
        'line_singular' => 'позиція',
        'line_plural' => 'позицій',
        'version_singular' => 'версія',
        'version_plural' => 'версій',
    ],
];
