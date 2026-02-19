<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
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
        $parameter = $request->route('user');

        $targetUser = $parameter instanceof User
            ? $parameter
            : User::find($parameter);

        $authUser = auth()->user();

        if (!$targetUser) {
            return $next($request);
        }

        if ($authUser && $authUser->id === $targetUser->id) {
            return $next($request);
        }

        $privacy = $targetUser->profile->privacy ?? [];
        $status = $privacy[$key] ?? 'public';

        if ($status === 'public') {
            return $next($request);
        }

        if ($status === 'contacts_only') {
            if (!$authUser) {
                abort(403, 'Только для контактов');
            }

            $isContact = $targetUser->contacts()
                ->where('contact_id', $authUser->id)
                ->exists();

            if ($isContact) {
                return $next($request);
            }
        }

        abort(403, 'У вас нет доступа к этому разделу');
    }
}
