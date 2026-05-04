<?php

namespace App\Http\Controllers\API;

use App\Helpers\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class DashboardController extends BaseController
{
    public function dashboard(Request $request)
    {
        return view('dashboard');
    }

    public function welcome(Request $request)
    {
        if (auth()->user())
            return view('dashboard');
        else
            return view('auth.login');
    }
}
