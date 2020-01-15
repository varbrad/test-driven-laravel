<?php

namespace App\Http\Controllers;

use App\Concert;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConcertsController extends Controller
{
    public function show($concert): View {
        $concert = Concert::whereNotNull('published_at')->findOrFail($concert);
        return view('concerts.show', compact('concert'));
    }
}
