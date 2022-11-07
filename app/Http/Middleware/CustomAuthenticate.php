<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiSession;
use Carbon\Carbon;

class CustomAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized, need auth token', 'code' => 401]);
        }

        $token = str_replace('Bearer ', '', $token);
        $apiSession = ApiSession::where('token', $token)->first();

        if (!$apiSession) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized, session not found', 'code' => 401]);
        }

        if ($apiSession->expired_at && $apiSession->expired_at < Carbon::now()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized, session expired', 'code' => 401]);
        }

        if ($apiSession->otp_confirmed == false) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized, need to authenticate with two factors', 'code' => 401]);
        }

        $request->merge(['user' => $apiSession->user]);
        return $next($request);
    }
}