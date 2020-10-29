<?php

namespace Middleware;

use App;
use Http\Redirect;
use Http\Request;
use Http\Response;

class Auth implements Middleware
{
    public function handle(Request $request, $next)
    {
        $token = $_SESSION['token'] ?? null;

        if (!$token || ($user = App::make(\Api::class)->getUserInfo($token)) === false) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
                strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == 'xmlhttprequest') {
                return Response::make('Login Expire', 401);
            } else {
                return Redirect::to('/auth/index');
            }
        }

        $_SESSION['user'] = $user;

        return $next($request);
    }
}