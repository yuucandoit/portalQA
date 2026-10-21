<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ActiveUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::check()) {
            $user = Auth::user();
            if($user->status == "Inactive" || $user->status != "Active"){
                Auth::logout();
                
                // Invalidate session
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirect ke login dengan pesan error
                return redirect('login')->with('error', 'Your account is inactive. Please contact administrator.');
            }
        }
        return $next($request);
    }
}
