<?php

namespace App\Http\Controllers\User\Profile;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\DriverLanguage;
use App\Models\DriverProvince;
use App\Models\DriverCity;
use App\Models\DriverCar;
use App\Models\Language;
use App\Models\Province;
use App\Models\City;
use App\Models\Address;
use App\Models\Car;
use App\Models\Discount;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // public function getProfile(Request $request)
    // {
    //     $userId = $request->get('user_id');

    //     $profile = User::select([
    //         'users.id',
    //         'users.username',
    //         'users.first_name',
    //         'users.last_name',
    //         'users.gender',
    //         'users.avatar',
    //         'users.type',
    //         'users.status',
    //     ])
    //         ->where('id', $userId)
    //         ->first();

    //     if ($withRate) {
    //         $profile->rate = Rate::where([
    //             'travels.user_id' => $profile->id,
    //             'made_by' => 'driver',
    //         ])
    //             ->join('travels', 'travels.id', '=', 'rate.travel_id')
    //             ->avg('value');
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //             'profile' => $profile,
    //         ],
    //     ]);
    // }

    public function getUser(Request $request)
    {
        $user = Auth()->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    public function getDiscounts(Request $request)
    {
        $userId = Auth()->id();

        $discounts = Discount::select([
            'discounts.id',
            'discounts.user_id',
            'discounts.code',
            'discounts.percentage',
            'discounts.max_discount',
            'discounts.max_usage_number',
            'discounts.used_times_number',
            'discounts.has_finished',
            'discounts.expire_at',
            DB::raw("IF(`expire_at` > NOW(), `has_finished`, 1) as `has_finished`"),
        ])
            ->where('user_id', $userId)
            ->orderBy('has_finished', 'asc')
            ->orderBy('expire_at', 'asc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => [
                'discounts' => $discounts,
            ],
        ]);
    }

    public function getAddresses(Request $request)
    {
        $userId = Auth()->id();

        $addresses = Address::select([
            'addresses.id',
            'addresses.user_id',
            'addresses.province_id',
            'addresses.city_id',
            'provinces.title as province_title',
            'cities.title as city_title',
            'addresses.title',
            'addresses.address',
            'addresses.latitude',
            'addresses.longitude',
            'addresses.created_at',
            'addresses.updated_at',
        ])
            ->where('addresses.user_id', $userId)
            ->join('provinces', 'provinces.id', '=', 'addresses.province_id')
            ->join('cities', 'cities.id', '=', 'addresses.city_id')
            ->paginate(12);

        return response()->json([
            'status' => 'success',
            'data' => [
                'addresses' => $addresses
            ],
        ]);
    }

    public function createAddress(Request $request)
    {
        $userId                         = Auth()->id();
        $cityId                         = $request->get('city_id');
        $title                          = $request->get('title');
        $address                        = $request->get('address');
        $latitude                       = $request->get('latitude');
        $longitude                      = $request->get('longitude');

        $validator = Validator::make($request->all(), [
            'city_id'                   => 'required|exists:cities,id',
            'title'                     => 'required|max:100',
            'address'                   => 'required|max:255',
            'latitude'                  => [
                'required',
                'regex:/^[+-]?([0-9]+\.?[0-9]*|\.[0-9]+)$/',
            ],
            'longitude'                 => [
                'required',
                'regex:/^[+-]?([0-9]+\.?[0-9]*|\.[0-9]+)$/',
            ],
        ], [
            'exists' => 'exists',
            'max' => 'max',
            'required' => 'required',
            'regex' => 'regex',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        $city = City::find($cityId);

        if (!$city) {
            return response()->json([
                'status' => 'failed',
                'message' => 'city_not_exist',
            ]);
        }

        $addressItem                    = new Address;
        $addressItem->user_id           = $userId;
        $addressItem->province_id       = $city->province_id;
        $addressItem->city_id           = $city->id;
        $addressItem->title             = $title;
        $addressItem->address           = $address;
        $addressItem->latitude          = $latitude;
        $addressItem->longitude         = $longitude;
        $addressItem->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'address' => $addressItem,
            ],
        ]);
    }

    public function removeAddress(Request $request)
    {
        $userId = Auth()->id();
        $addressId = $request->get('address_id');

        $address = Address::where([
            'id' => $addressId,
            'user_id' => $userId,
        ])->first();

        if (!$address) {
            return response()->json([
                'status' => 'failed',
                'message' => 'address_not_exist',
            ]);
        }

        $address->delete();

        return response()->json([
            'status' => 'success',
            'data' => [],
        ]);
    }

    public function updateUser(Request $request)
    {
        $user = Auth()->user();

        if ($user->email != $request->get('email')){
            $user->email_verified_at = null;
        }

        $validator = Validator::make($request->all(), [
            'first_name'                => 'nullable|max:100',
            'last_name'                 => 'nullable|max:100',
            'father_name'               => 'nullable|max:100',
            'birth'                     => 'nullable|date',
            'gender'                    => 'in:unknown,male,female',
            'emergency_phone'           => 'nullable|regex:/^(09)[0-9]{9}$/',
            'address'                   => 'nullable|max:255',
            'license_expire_at'         => 'nullable|date',
            'avatar'                    => 'nullable|image',
            'username'                  => [
                Rule::unique('users')->ignore($user->id),
                'nullable',
                'alpha_num',
                'max:100',
            ],
            'email'                     => [
                Rule::unique('users')->ignore($user->id),
                'nullable',
                'email',
            ],
            'melli_code'                => [
                Rule::unique('users')->ignore($user->id),
                'nullable',
                'alpha_num',
                'max:100',
            ],
            'license_number'            => [
                Rule::unique('users')->ignore($user->id),
                'nullable',
                'alpha_num',
                'max:100',
            ],
        ], [
            'nullable' => 'nullable',
            'max' => 'max',
            'in' => 'in',
            'regex' => 'regex',
            'date' => 'date',
            'image' => 'image',
            'alpha_num' => 'alpha_num',
            'email' => 'email',
            'unique' => 'unique',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        if ($user->type != 'user') {
            return response()->json([
                'status' => 'failed',
                'message' => 'user_not_user',
            ]);
        }

        $user->username                = $request->get('username') ?? '';
        $user->email                   = $request->get('email') ?? '';
        $user->first_name              = $request->get('first_name') ?? '';
        $user->last_name               = $request->get('last_name') ?? '';
        $user->father_name             = $request->get('father_name') ?? '';
        $user->birth                   = $request->get('birth');
        $user->gender                  = $request->get('gender');
        $user->emergency_phone         = $request->get('emergency_phone') ?? '';
        $user->address                 = $request->get('address') ?? '';
        $user->melli_code              = $request->get('melli_code') ?? '';
        $user->license_number          = $request->get('license_number') ?? '';
        $user->license_expire_at       = $request->get('license_expire_at');

        $user->status = 'pending';
        $user->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    public function createAvatar(Request $request)
    {
        $user = Auth()->user();

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image'
        ], [
            'required' => 'required',
            'image' => 'image',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        if ($request->file('avatar')) {
            if (Storage::exists($user->avatar)) {
                Storage::delete($user->avatar);
            }

            $user->avatar = Storage::putFile('public/avatars', $request->file('avatar'));
            $user->save();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'avatar' => $user->avatar,
            ],
        ]);
    }

    public function removeAvatar(Request $request)
    {
        $user = Auth()->user();

        if (Storage::exists($user->avatar)) {
            Storage::delete($user->avatar);
        }

        $user->avatar = '';
        $user->save();

        return response()->json([
            'status' => 'success',
            'data' => [],
        ]);
    }
}