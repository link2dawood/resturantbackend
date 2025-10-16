<?php

namespace App\Http\Controllers;

class ReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_reports');
    }

    /**
     * Display the reports index page.
     */
    public function index()
    {
        return view('reports.index');
    }
}
