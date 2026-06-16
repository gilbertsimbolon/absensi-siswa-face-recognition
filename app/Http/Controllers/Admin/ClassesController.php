<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
// use App\Models\Teacher;
// use Illuminate\Http\Request;

class ClassesController extends Controller
{
    // fungsi index
    public function index(string $id)
    {
        $classes = Classes::with('teacher')->get();

        return view('admin.master-data.classes', compact('classes'));
    }
}
