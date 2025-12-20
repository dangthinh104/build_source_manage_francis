<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    /**
     * Display the documentation page.
     */
    public function index(): Response
    {
        return Inertia::render('Documentation/Index');
    }
}
