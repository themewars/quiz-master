<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;

class LegalPageController extends Controller
{
    public function show(string $slug)
    {
        $page = LegalPage::where('status', true)->where('slug', $slug)->firstOrFail();
        return view('home.legal-page', compact('page'));
    }
}


