<?php

namespace App\Http\Controllers\User\Auth;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Sms;
use App\Models\User;

class AuthController extends Controller
{
    public function sendPhone(Request $request)
    {
        $cellphone = $request->get('cellphone');

        $validator = Validator::make($request->all(), [
            'cellphone' => 'required|regex:/^(09)[0-9]{9}$/',
        ], [
            'required' => 'required',
            'regex' => 'regex',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        $user = User::where(['cellphone' => $cellphone])->first();

        if (!$user) {
            $user = new User;
            $user->username                 = '';
            $user->email                    = '';
            $user->first_name               = '';
            $user->last_name                = '';
            $user->father_name              = '';
            $user->emergency_phone          = '';
            $user->address                  = '';
            $user->melli_code               = '';
            $user->avatar                   = '';
            $user->license_number           = '';
            $user->password                 = '';
            $user->cellphone                = $cellphone;
            $user->status                   = 'pending';
            $user->type                     = 'user';
        }

        if ($user->type != 'user' && $user->type != 'driver') {
            return response()->json([
                'status' => 'failed',
                'message' => 'not_user_nor_driver',
            ]);
        }

        $user->confirmation_code            = rand(11111, 99999);
        $user->confirmation_expire_at       = date('Y-m-d H:i:s', strtotime('+2 minutes'));
        $user->save();

        Sms::send($cellphone, $user->confirmation_code);

        return response()->json([
            'status' => 'success',
            'data' => [],
        ]);
    }

    public function phoneVerification(Request $request)
    {
        $cellphone          = $request->get('cellphone');
        $confirmationCode   = $request->get('confirmation_code');

        $user = User::where(['cellphone' => $cellphone])->first();

        if ($user) {
            if (strtotime($user->confirmation_expire_at) < time()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'code_expired',
                ]);
            }

            if ($user->confirmation_code != $confirmationCode) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'core_wrong',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $user,
                    'token' => $user->createToken('user')->accessToken,
                ],
            ]);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'user_not_exist',
            ]);
        }
    }

    public function logout(Request $request)
    {
        $user = Auth()->user();

        if ($user) {
            $user->token()->revoke();
        }

        return response()->json([
            'status' => 'success',
            'data' => [],
        ]);
    }
}
