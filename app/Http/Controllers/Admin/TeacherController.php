<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;

class TeacherController extends Controller
{
    // fungsi index
    public function index()
    {
        return view('admin.master-data.data-guru');
    }
}
