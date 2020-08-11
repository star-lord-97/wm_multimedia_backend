<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\PayloadFactory;
use Tymon\JWTAuth\JWTManager as JWT;
use phpseclib\Crypt\RSA;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $rsa = new RSA();
        extract($rsa->createKey());

        $validator = Validator::make($request->json()->all(), [
            'name' => 'required|string|min:5',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response("email taken"); //->json($validator->errors()->toJson(), 400);
        }

        $user = User::create([
            'name' => $request->json()->get('name'),
            'email' => $request->json()->get('email'),
            'password' => Hash::make($request->json()->get('password')),
            'public_key' => $publickey,
            'private_key' => $privatekey
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json($user);
    }

    public function login(Request $request)
    {
        $credentials = $request->json()->all();

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response("invalid credentials");
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $user = User::firstWhere('email', $request->json()->get('email'));

        return response()->json($user);
    }
}
