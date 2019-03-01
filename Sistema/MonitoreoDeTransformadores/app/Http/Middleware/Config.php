<?php

namespace App\Http\Middleware;

use Closure;

class Config
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->user()->type != "config"){
          return redirect('/home')->with('error', 'No tienes permisos para acceder a esta ruta!');
        }
        return $next($request);
    }
}
