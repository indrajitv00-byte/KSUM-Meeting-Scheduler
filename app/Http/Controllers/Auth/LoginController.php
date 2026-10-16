<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as RulesPassword;

class LoginController extends Controller
{

    public function logout(Request $request)
    {
      Auth::logout();

      $request->session()->invalidate();

      $request->session()->regenerateToken();

      if($request->redirect){
          return redirect($request->redirect);
      }
      else{
          return redirect()->route('login');
      }
    }

}
