<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // fungsi index
    public function index()
    {
        return view('login');
    }

    // fungsi login
    public function login(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // jika validasi gagal
        if ($validate->fails()){
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $credentials = $request->only('email','password');

        // penanganan login
        if(Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            if($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard.index');
            }

            return redirect()->route('admin.dashboard.index');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    // fungsi logout
    public function logout(Request $request)
    {
        Auth::logout();
        // menghapus validasi
        $request->session()->invalidate();

        // membuat ulang token
        $request->session()->regenerateToken();

        // penanganan logout
        return redirect()->route('login.index')->with('success', 'Anda berhasil logout.');
    }
}
