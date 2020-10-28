<?php

namespace Middleware;

use App;
use Http\Redirect;
use Http\Request;

class Auth implements Middleware
{
    public function handle(Request $request, $next)
    {
        $token = $_SESSION['token'] ?? null;

        if (!$token || ($user = App::make(\Api::class)->getUserInfo($token)) === false)
            return Redirect::to('/auth/index');

        $_SESSION['user'] = $user;

        return $next($request);
    }
}