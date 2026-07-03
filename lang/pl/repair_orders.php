<?php

return [
    'estimate_created' => 'Utworzono PDF kosztorysu.',

    'actions' => [
        'add_line' => 'Dodaj pozycję',
        'cancel' => 'Anuluj',
        'complete' => 'Zakończ',
        'create_estimate_pdf' => 'Utwórz PDF kosztorysu',
        'delete' => 'Usuń',
        'download' => 'Pobierz',
        'edit' => 'Edytuj',
        'save' => 'Zapisz',
        'view_booking_request' => 'Zobacz zgłoszenie',
    ],

    'estimate_errors' => [
        'missing_lines' => 'Dodaj co najmniej jedną pozycję zlecenia naprawy przed utworzeniem kosztorysu.',
        'repair_order_locked' => 'Po zatwierdzeniu tego zlecenia naprawy nie można już wygenerować PDF kosztorysu.',
    ],

    'fields' => [
        'actions' => 'Akcje',
        'closed' => 'Zamknięto',
        'customer' => 'Klient',
        'description' => 'Opis',
        'filename' => 'Nazwa pliku',
        'generated' => 'Wygenerowano',
        'opened' => 'Otwarto',
        'order' => 'Kolejność',
        'original_message' => 'Oryginalna wiadomość',
        'pdf' => 'PDF',
        'preferred_date' => 'Preferowana data',
        'problem' => 'Problem',
        'quantity' => 'Ilość',
        'source' => 'Źródło',
        'source_request' => 'Zgłoszenie źródłowe',
        'status' => 'Status',
        'tax' => 'Podatek',
        'total' => 'Razem',
        'type' => 'Typ',
        'unit' => 'Cena jedn.',
        'unit_price' => 'Cena jedn.',
        'vehicle' => 'Pojazd',
        'version' => 'Wersja',
    ],

    'line_types' => [
        'labor' => 'Robocizna',
        'part' => 'Część',
        'fee' => 'Opłata',
        'discount' => 'Rabat',
    ],

    'messages' => [
        'booking_request_source' => 'Zgłoszenie',
        'manual_repair_order' => 'Ręczne zlecenie naprawy',
        'no_documents' => 'Brak dostępnych dokumentów.',
        'no_estimate_pdfs' => 'Nie utworzono jeszcze PDF-ów kosztorysu.',
        'no_estimate_summary' => 'Nie utworzono jeszcze kosztorysu.',
        'no_vehicle' => 'Brak pojazdu',
        'no_working_lines' => 'Brak pozycji roboczych.',
    ],

    'navigation' => [
        'repair_orders' => 'Zlecenia naprawy',
    ],

    'pdf' => [
        'title' => 'Kosztorys #:version',
        'heading' => 'Kosztorys v:version',
        'generated' => 'Wygenerowano :date',
        'repair_order' => 'Zlecenie naprawy #:id',
        'customer' => 'Klient',
        'lines_heading' => 'Pozycje kosztorysu',
        'columns' => [
            'type' => 'Typ',
            'description' => 'Opis',
            'quantity' => 'Ilość',
            'unit' => 'Cena jedn.',
            'tax' => 'Podatek',
            'total' => 'Razem',
        ],
        'totals' => [
            'subtotal' => 'Suma częściowa',
            'tax' => 'Podatek',
            'total' => 'Razem',
        ],
    ],

    'repair_order_statuses' => [
        'draft' => 'Wersja robocza',
        'estimated' => 'Wyceniono',
        'approved' => 'Zatwierdzono',
        'in_progress' => 'W trakcie',
        'completed' => 'Zakończono',
        'cancelled' => 'Anulowano',
    ],

    'sections' => [
        'estimates' => 'Kosztorysy',
        'latest_estimate' => 'Najnowszy kosztorys',
        'timeline' => 'Oś czasu',
        'work_document' => 'Dokument roboczy',
        'working_lines' => 'Robocze pozycje zlecenia naprawy',
        'working_totals' => 'Suma robocza',
    ],

    'tabs' => [
        'documents' => 'Dokumenty',
        'estimates' => 'Kosztorysy',
        'lines' => 'Pozycje',
        'overview' => 'Przegląd',
        'timeline' => 'Oś czasu',
    ],

    'totals' => [
        'subtotal' => 'Suma częściowa',
        'tax' => 'Podatek',
        'total' => 'Razem',
    ],

    'units' => [
        'document_singular' => 'dokument',
        'document_plural' => 'dokumentów',
        'line_singular' => 'pozycja',
        'line_plural' => 'pozycji',
        'version_singular' => 'wersja',
        'version_plural' => 'wersji',
    ],
];
