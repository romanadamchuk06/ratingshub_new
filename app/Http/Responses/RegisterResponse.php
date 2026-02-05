<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

/**
 * Custom Register Response
 *
 * Handhabt Redirects nach der Registrierung.
 * Prüft ob ein 'redirect' Parameter in der Session gespeichert wurde
 * und leitet dorthin weiter, sonst zum Dashboard.
 */
class RegisterResponse implements RegisterResponseContract
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
