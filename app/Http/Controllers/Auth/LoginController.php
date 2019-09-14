<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Redirect;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\DB;
use App\User;
use App\UserSocialAccount;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function showLoginForm()
    {
        return view('auth.login');
    }


    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function redirectTo()
    {
        //dd( session()->get('previousUrl', '/'));
        return str_replace(url('/'), '', session()->get('previousUrl', '/'));
    }


    public function redirectToProvider (string $driver) {
        return Socialite::driver($driver)->redirect();
    }

    public function handleProviderCallback (string $driver) {
        if( ! request()->has('code') || request()->has('denied')) {
            session()->flash('message', ['danger', __("Inicio de sesión cancelado")]);
            return redirect()->route('landing-page');
        }

        $socialUser = Socialite::driver($driver)->user();

        $user = null;
        $success = true;
        $email = $socialUser->email;
        $check = User::whereEmail($email)->first();
        if($check) {
            $user = $check;
        } else {
            DB::beginTransaction();
            try {
                $user = User::create([
                    "name" => $socialUser->name,
                    "email" => $email
                ]);
                UserSocialAccount::create([
                    "user_id" => $user->id,
                    "provider" => $driver,
                    "provider_uid" => $socialUser->id
                ]);

            } catch (\Exception $exception) {
                $success = $exception->getMessage();
                DB::rollBack();
            }
        }

        if($success === true) {
            DB::commit();
            auth()->loginUsingId($user->id);
            return redirect(route('home'));
        }
        session()->flash('message', ['danger', $success]);
        return redirect()->route('landing-page');
    }
}
