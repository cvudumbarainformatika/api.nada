<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthHelper
{

    public $email, $password;

    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public function validateInput()
    {
        $validator = Validator::make(
            ['email' => $this->email, 'password' => $this->password],
            [
                'email' => ['required', 'email:rfc,dns', 'unique:accounts'],
                'password' => ['required', Password::min(6)]
            ]
        );

        if ($validator->fails()) {
            return ['status' => false, 'messages' => $validator->errors()];
        }

        return ['status' => true, 'validator' => $validator->validate()];
    }

    public function login()
    {
        $validate = $this->validateInput();
        if ($validate['status'] == false) {
            return $validate;
        }

        if (!$token = JWTAuth::attempt($validate['validator'])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewToken($token);
    }

    protected function createNewToken($token)
    {
        $user = JWTAuth::authenticate($token);
        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    // public function ()
    // {

    // }
}
