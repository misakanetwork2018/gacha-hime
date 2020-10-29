<?php

namespace Middleware;

use Http\Request;
use Http\Response;

class Api implements Middleware
{
    public function handle(Request $request, $next)
    {
        if ($request->get('key') !== \App::config('key'))
            return Response::make('Incorrect access key', 403);

        return $next($request);
    }
}