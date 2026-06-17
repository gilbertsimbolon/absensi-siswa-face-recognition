<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    // fungsi index
    public function index()
    {
        $teachers = Teacher::all();

        return view('admin.master-data.data-guru', compact('teachers'));
    }

    // fungsi create
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'nip' => 'required|unique:teachers,nip',
            'phone' => 'required',
        ]);

        if ($validate->fails()) {
            return back()->withErrors($validate)->withInput();
        }

        DB::transaction(function () use ($request) {
            // buat data user terlebih dahulu
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            // assignrole karena menggunakan spatie
            $user->assignRole('teacher');

            // setelah itu buat data teacher
            Teacher::create([
                'user_id' => $user->id,
                'nip' => $request->nip,
                'phone' => $request->phone,
            ]);

        });

        // return
        return redirect()->route('admin.teacher.index')->with('success', 'Data guru berhasil ditambahkan');
    }
}
