<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ApiSession;
use Validator;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Http\Resources\ApiSessionResource;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
                'password' => 'required',
                'name' => 'required',
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors(), 'code' => 401]);
        }
        // TODO create a user with OTP

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => $request->name,
        ]);
        return response()->json(
            ['status' => 'success',
            'message' => 'User created successfully',
            'code' => 200,
            'data' => $user
        ]);

    }
    public function login(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
                'password' => 'required',
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors(), 'code' => 401]);
        }

        $password = $request->input('password');
        $user = User::where('email', $request->input('email'))->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized wrong password or email', 'code' => 401]);
        }

        $api_session = $user->sessions()
            ->whereNull('expired_at')
            ->with('user')
            ->first();
        if (!$api_session) {
            $api_session = $user->sessions()->create(
                [
                    'token' => $this->generateToken(),
                    'expired_at' => null,
                    'otp_code' => $this->generateOtpCode(),
                    'otp_confirmed' => false,
                    'otp_expire' => Carbon::now()->addMinutes(5),
                    
                ]
            );
        }
        return new ApiSessionResource($api_session);
    }

    public function confirmOtp(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'otp_code' => 'required',
                'token' => 'required',
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors(), 'code' => 401]);
        }
        $api_session = ApiSession::where('token', $request->input('token'))
            ->whereNull('expired_at')
            ->whereNotNull('otp_code')->first();
        if (!$api_session) {
            return response()->json(['status' => 'error', 'message' => 'Session not found', 'code' => 401]);
        }
        if($api_session->otp_expire < Carbon::now()){
            return response()->json(['status' => 'error', 'message' => 'Otp code expired', 'code' => 401]);
        }
        
        $api_session->token = $this->generateToken();
        $api_session->otp_confirmed = true;
        $api_session->otp_code = null;
        $api_session->otp_expire = null;
        $api_session->save();

        return new ApiSessionResource($api_session);
    }
   
    public static function getApiSession($request, $withUser = false)
    {
        $query = ApiSession::where('token', $request->input('token'))
            ->whereNull(
                'expired_at'
            )
            ->where(
                'otp_confirmed'
            );

        if ($withUser) {
            $query = $query->with('user');
        }
        return $query->first();
    }

    public static function getLogedInUser($request)
    {
        $api_session = self::getApiSession($request, true);
        // ApiSession::where('token', $token)->whereNull('expired_at')->with('user')->first();
        if ($api_session) {
            return $api_session->user;
        } else
            return null;
    }

    private function generateToken()
    {
        return md5(rand(1, 10) . microtime());
    }

    private function generateOtpCode($digits = 4)
    {
        $i = 0;
        $pin = "";

        while ($i < $digits) {
            $pin .= mt_rand(0, 9);
            $i++;
        }

        return $pin;
    }

    public function logout(Request $request)
    {

        $api_session = self::getApiSession($request);
        // ApiSession::where('token', $token)->whereNull('expired_at')->first();
        if ($api_session) {
            $api_session->expired_at = now();
            $api_session->save();
            return response()->json(['status' => 'success', 'message' => 'logged out successful', 'code' => 200]);
        }
        return response()->json(['status' => 'error', 'message' => 'you are already logged out, no session active', 'code' => 400]);
    }
}