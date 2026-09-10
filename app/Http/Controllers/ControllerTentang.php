<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ControllerTentang extends Controller
{
    public function index(): View
    {
        return view('tentang');
    }
}