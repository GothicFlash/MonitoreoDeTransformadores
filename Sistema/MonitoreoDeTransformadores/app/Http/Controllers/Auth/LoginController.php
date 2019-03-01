<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
//use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Auth;

class LoginController extends Controller
{
  function showLoginForm()
  {
      return view('auth.login');
  }

  public function login()
  {
    $credentials = $this->validate(request(), [
      'email' => 'email|required|string',
      'password' => 'required|string'
    ]);

    if(Auth::attempt($credentials)){
      return redirect('/home');
    }

    return back()
      ->withErrors(['email' => trans('auth.failed')])
      ->withInput(request(['email']));
  }

  public function logout()
  {
    Auth::logout();
    return redirect('/');
  }
}
