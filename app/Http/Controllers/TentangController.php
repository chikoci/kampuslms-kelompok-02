<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TentangController extends Controller
{
    public function index(): View
    {
        return view('tentang');
    }
}