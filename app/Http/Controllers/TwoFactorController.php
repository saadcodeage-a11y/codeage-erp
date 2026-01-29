<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Support\Facades\Mail;

class TwoFactorController extends Controller
{
    public function index()
    {
        return view('auth.two-factor');
    }

    public function store(Request $request)
    {
        $request->validate([
            'two_factor_code' => 'required|integer',
        ]);

        $user = User::find(Session::get('user_2fa_id'));

        if (!$user) {
             return redirect()->route('login');
        }

        if ($request->two_factor_code == $user->two_factor_code) {
             
             if ($user->two_factor_expires_at->lt(now())) {
                 return redirect()->back()->withErrors(['two_factor_code' => 'The two factor code has expired. Please login again.']);
             }

            $user->resetTwoFactorCode();
            Auth::login($user);
            Session::forget('user_2fa_id');

            return redirect()->route('dashboard');
        }

        return redirect()->back()->withErrors(['two_factor_code' => 'The two factor code you entered does not match.']);
    }

    public function resend()
    {
        $user = User::find(Session::get('user_2fa_id'));

        if (!$user) {
             return redirect()->route('login');
        }

        $user->generateTwoFactorCode();
        Mail::to($user->email)->send(new TwoFactorCodeMail($user->two_factor_code));

        return redirect()->back()->with('message', 'The two factor code has been resent again');
    }
}
