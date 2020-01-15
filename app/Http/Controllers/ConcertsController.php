<?php

namespace App\Http\Controllers;

use App\Concert;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ConcertsController extends Controller
{
    public function show(Concert $concert): View {
        return view('concerts.show', compact('concert'));
    }
}
