<?php

namespace App\Http\Middleware;

use Closure;

class Admin
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
        if($request->user()->type != "admin"){
          return redirect('/home')->with('error', 'No tienes permisos para acceder a esta ruta!');
        }
        return $next($request);
    }
}
