<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ReviewQueueViewController extends Controller
{
    public function index()
    {
        return view('admin.review-queue.index');
    }
}
