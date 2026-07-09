<?php

namespace App\Http\Controllers;

use App\Support\Urls\AppUrl;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class PublicHomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'adminLoginUrl' => AppUrl::adminPath('/login'),
            'adminRegisterUrl' => AppUrl::adminPath('/register'),
        ]);
    }
}
