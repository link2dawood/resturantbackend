<?php

namespace App\Http\Controllers;

use App\Helpers\USStates;
use App\Models\State;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Show all US states
     */
    public function states()
    {
        $states = State::orderBy('name')->get();
        $statesForSelect = USStates::getStatesFromDatabaseForSelect();
        
        return view('states.index', compact('states', 'statesForSelect'));
    }
}
