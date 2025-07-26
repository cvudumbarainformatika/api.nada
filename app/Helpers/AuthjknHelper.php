<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthjknHelper
{
    public static $auth;
    public static $credential;

    public function __construct($auth, $credential)
    {
        $this->auth = $auth;
        $this->credential = $credential;
    }

    public static function authenticate(array $apy)
    {
        $id = $apy['sub'];
        return self::$credential = $id;
        // return $this->credential = $id;
    }


    public static function user()
    {
        self::$auth = User::find(self::$credential);
        return self::$auth;
    }
}
