<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
// use Illuminate\Http\Request;

class ClassesController extends Controller
{
    // fungsi index
    public function index()
    {
        $classes = Classes::all();

        return view('admin.master-data.classes', compact('classes'));
    }
}
