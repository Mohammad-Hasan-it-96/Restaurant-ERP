<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $spaPath = public_path('spa/index.html');

        if (file_exists($spaPath)) {
            return response(file_get_contents($spaPath), 200)
                ->header('Content-Type', 'text/html');
        }

        // Fallback to legacy blade view if SPA is not built yet
        return response(view('public.home')->render(), 200)
            ->header('Content-Type', 'text/html');
    }
}

