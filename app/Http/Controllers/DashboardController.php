<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => 520,
            'positif_persen' => 68,
            'negatif_persen' => 20,
            'netral_persen' => 12,
        ];
        $lastUpdate = '27 Februari 2026, 22:45';

    return view('dashboard', compact('stats', 'lastUpdate'));
    }
}
