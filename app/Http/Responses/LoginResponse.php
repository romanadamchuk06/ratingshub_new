<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Custom Login Response
 *
 * Handhabt Redirects nach dem Login.
 * Prüft ob ein 'redirect' Parameter in der Session gespeichert wurde
 * und leitet dorthin weiter, sonst zum Dashboard.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Prüfe ob ein Redirect in der Session gespeichert wurde
        $redirect = session()->pull('url.intended', config('fortify.home'));

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->to($redirect);
    }
}
