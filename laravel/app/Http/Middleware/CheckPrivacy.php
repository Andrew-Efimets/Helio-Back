<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckPrivacy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $key): Response
    {
        $targetUser = $request->route('user');

        if (!$targetUser instanceof User) {
            $targetUser = User::find($targetUser);
        }

        if (!$targetUser) {
            return $next($request);
        }

        if (Gate::denies('viewParam', [$targetUser, $key])) {
            abort(403, 'У вас нет доступа к этому разделу');
        }

        return $next($request);
    }
}
