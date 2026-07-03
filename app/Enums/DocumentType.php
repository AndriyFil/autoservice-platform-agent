<?php

namespace App\Enums;

enum DocumentType: string
{
    case EstimatePdf = 'estimate_pdf';
    case InvoicePdf = 'invoice_pdf';

    public function label(): string
    {
        return match ($this) {
            self::EstimatePdf => 'Estimate PDF',
            self::InvoicePdf => 'Invoice PDF',
        };
    }
}
