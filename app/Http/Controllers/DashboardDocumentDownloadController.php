<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardDocumentDownloadController extends Controller
{
    public function show(Request $request, Document $document): StreamedResponse
    {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');

        $document = Document::query()
            ->whereKey($document->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->filename,
            ['Content-Type' => $document->mime_type],
        );
    }
}
