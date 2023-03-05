<?php

namespace App\Http\Controllers\Driver\Profile;

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
use App\Models\Car;
use App\Models\Travel;
use App\Models\TravelPoint;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // public function getProfile(Request $request)
    // {
    //     $userId                 = Auth()->id();
    //     $driverId               = $request->get('user_id');
    //     $withLanguages          = $request->get('with_languages');
    //     $withDistricts          = $request->get('with_districts');
    //     $withCar                = $request->get('with_car');
    //     $withRate               = $request->get('with_rate');

    //     $driver = User::select([
    //         'users.id',
    //         'users.username',
    //         'users.first_name',
    //         'users.last_name',
    //         'users.gender',
    //         'users.avatar',
    //         'users.type',
    //         'users.status',
    //     ])
    //         ->where([
    //             'id'            => $driverId,
    //             'type'          => 'driver',
    //             'status'        => 'confirmed',
    //         ])
    //         ->first();

    //     if (!$driver) {
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => 'user_not_driver',
    //         ]);
    //     }

    //     if ($withLanguages) {
    //         $driver->languages = Language::select([
    //             'languages.id',
    //             'languages.title',
    //             DB::raw("1 as `is_selected`"), 
    //         ])
    //             ->where('driver_languages.user_id', $driver->id)
    //             ->join('driver_languages', 'driver_languages.language_id', '=', 'languages.id')
    //             ->get();
    //     }

    //     if ($withDistricts) {
    //         $driver->provinces = Province::select([
    //             'provinces.id',
    //             'provinces.title',
    //             DB::raw("1 as `is_selected`"),
    //         ])
    //             ->where('driver_provinces.user_id', $driver->id)
    //             ->join('driver_provinces', 'driver_provinces.province_id', '=', 'provinces.id')
    //             ->get();

    //         foreach ($driver->provinces as $province) {
    //             $province->cities = City::select([
    //                 'cities.id',
    //                 'cities.title',
    //                 DB::raw("1 as `is_selected`"),
    //             ])
    //                 ->where('driver_cities.user_id', $driver->id)
    //                 ->where('cities.province_id', $province->id)
    //                 ->join('driver_cities', 'driver_cities.city_id', '=', 'cities.id')
    //                 ->get();
    //         }
    //     }

    //     if ($withCar) {
    //         $driver->car = Car::select([
    //             'driver_cars.id',
    //             'driver_cars.user_id',
    //             'driver_cars.car_id',
    //             'cars.type as car_type',
    //             'cars.title',
    //             'cars.brand',
    //             'driver_cars.color',
    //             'driver_cars.image',
    //             'driver_cars.status',
    //         ])
    //             ->where([
    //                 ['driver_cars.user_id', '=', $driver->id],
    //                 ['status', '!=', 'canceled'],
    //             ])
    //             ->join('driver_cars', 'driver_cars.car_id', '=', 'cars.id')
    //             ->orderBy('driver_cars.created_at', 'desc')
    //             ->first();
    //     }

    //     if ($withRate) {
    //         $driver->rate = Rate::where([
    //             'travels.driver_id' => $userId,
    //             'made_by' => 'user',
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
        $user                   = Auth()->user();
        $withLanguages          = $request->get('with_languages');
        $withDistricts          = $request->get('with_districts');
        $withCar                = $request->get('with_car');
        $withActiveTravel       = $request->get('with_active_travel');

        if ($withLanguages) {
            $user->languages = Language::select([
                'languages.id',
                'languages.title',
                DB::raw("1 as `is_selected`"), 
            ])
                ->where('driver_languages.user_id', $user->id)
                ->join('driver_languages', 'driver_languages.language_id', '=', 'languages.id')
                ->get();
        }

        if ($withDistricts) {
            $user->provinces = Province::select([
                'provinces.id',
                'provinces.title',
                DB::raw("1 as `is_selected`"),
            ])
                ->where('driver_provinces.user_id', $user->id)
                ->join('driver_provinces', 'driver_provinces.province_id', '=', 'provinces.id')
                ->get();

            foreach ($user->provinces as $province) {
                $province->cities = City::select([
                    'cities.id',
                    'cities.title',
                    DB::raw("1 as `is_selected`"),
                ])
                    ->where('driver_cities.user_id', $user->id)
                    ->where('cities.province_id', $province->id)
                    ->join('driver_cities', 'driver_cities.city_id', '=', 'cities.id')
                    ->get();
            }
        }

        if ($withCar) {
            $user->car = Car::select([
                'driver_cars.id',
                'driver_cars.user_id',
                'driver_cars.car_id',
                'cars.type as car_type',
                'cars.title',
                'cars.brand',
                'driver_cars.plate_type',
                'driver_cars.plate_two_numbers',
                'driver_cars.plate_letter',
                'driver_cars.plate_three_numbers',
                'driver_cars.plate_city_code',
                'driver_cars.color',
                'driver_cars.image',
                'driver_cars.status',
            ])
                ->where([
                    ['driver_cars.user_id', '=', $user->id],
                    ['driver_cars.status', '!=', 'canceled'],
                ])
                ->join('driver_cars', 'driver_cars.car_id', '=', 'cars.id')
                ->orderBy('driver_cars.created_at', 'desc')
                ->first();
        }

        if ($withActiveTravel) {
            $user->active_travel = Travel::select([
                'travels.id',
                'travels.user_id',
                'travels.driver_id',
                'travels.driver_car_id',
                'users.username as user_username',
                'users.first_name as user_first_name',
                'users.last_name as user_last_name',
                'users.cellphone as user_cellphone',
                'users.gender as user_gender',
                'users.avatar as user_avatar',
                'travels.language_id',
                'languages.title as language_title',
                'travels.discount_id',
                'travels.canceled_by_id',
                'travels.price',
                'travels.discount_price',
                'travels.final_price',
                'travels.request_type',
                'travels.payment_type',
                'travels.payment_status',
                'travels.status',
                'travels.is_finished',
                'travels.travel_start_time',
                'travels.travel_end_time',
                'travels.is_reserve',
                'travels.reserve_time',
                'travels.car_type',
                'travels.travel_options',
                'travels.description',
                'travels.created_at',
                'travels.updated_at',
            ])
                ->join('users', 'users.id', '=', 'travels.user_id')
                ->leftJoin('languages', 'languages.id', '=', 'travels.language_id')
                ->where([
                    'travels.driver_id' => $user->id,
                    'travels.status' => 'started',
                ])
                ->first();

            if ($user->active_travel) {
                $user->active_travel->points = TravelPoint::select([
                    'travel_points.id',
                    'travel_points.travel_id',
                    'provinces.title as province',
                    'cities.title as city',
                    'travel_points.address',
                    'travel_points.latitude',
                    'travel_points.longitude',
                    'travel_points.order',
                ])
                    ->where([
                        'travel_points.travel_id' => $user->active_travel->id,
                    ])
                    ->join('cities', 'cities.id', '=', 'travel_points.city_id')
                    ->join('provinces', 'provinces.id', '=', 'travel_points.province_id')
                    ->orderBy('travel_points.order', 'ASC')
                    ->get();
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    public function getDistricts(Request $request)
    {
        $userId = Auth()->id();
        $provinceId = $request->get('province_id');

        if ($provinceId) {
            $cities = City::select([
                'cities.id',
                'cities.province_id',
                'cities.title',
                DB::raw('IF(`driver_cities`.`id`, 1, 0) as `is_selected`'),
            ])
                ->where([
                    ['cities.province_id', '=', $provinceId],
                    ['driver_cities.user_id', '=', $userId],
                ])
                ->orWhere([
                    ['cities.province_id', '=', $provinceId],
                    ['driver_cities.user_id', '=', null],
                ])
                ->leftJoin('driver_cities', 'driver_cities.city_id', '=', 'cities.id')
                ->groupBy(
                    'cities.id',
                    'cities.province_id',
                    'cities.title',
                    'driver_cities.id'
                )
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'cities' => $cities,
                ],
            ]);
        } else {
            $provinces = Province::select([
                'provinces.id',
                'provinces.title',
                DB::raw('IF(`driver_provinces`.`id`, 1, 0) as `is_selected`'),
            ])
                ->where([
                    ['driver_provinces.user_id', '=', $userId],
                ])
                ->orWhere([
                    ['driver_provinces.user_id', '=', null],
                ])
                ->leftJoin('driver_provinces', 'driver_provinces.province_id', '=', 'provinces.id')
                ->groupBy(
                    'provinces.id',
                    'provinces.title',
                    'driver_provinces.id'
                )
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'provinces' => $provinces,
                ],
            ]);
        }
    }

    public function getLanguages(Request $request)
    {
        $userId = Auth()->id();

        $languages = Language::select([
            'languages.id',
            'languages.title',
            DB::raw('IF(`driver_languages`.`id`, 1, 0) as `is_selected`'),
        ])
            ->leftJoin('driver_languages', 'driver_languages.language_id', '=', 'languages.id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'languages' => $languages,
            ],
        ]);
    }

    public function getCars(Request $request)
    {
        $userId                         = Auth()->id();
        $search                         = $request->get('search');
        $type                           = $request->get('type');

        $validator = Validator::make($request->all(), [
            'search'                    => 'nullable|max:100',
            'type'                      => 'required|in:samand,peugeot,lux,all|max:100',
        ], [
            'nullable' => 'nullable',
            'max' => 'max',
            'required' => 'required',
            'in' => 'in',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        $cars = Car::select([
            'cars.id',
            'cars.title',
            'cars.brand',
            'cars.type',
        ])
            ->when($search, function ($query) use ($search) {
                $query->whereRaw(
                    "`cars`.`title` LIKE ? OR `cars`.`brand` LIKE ?",
                    ["$search%", "$search%"]
                );
            })
            ->when($type != 'all', function ($query) use ($type) {
                $query->where('cars.type', $type);
            })
            ->paginate(12);

        return response()->json([
            'status' => 'success',
            'data' => [
                'cars' => $cars,
            ],
        ]);
    }

    public function updateUser(Request $request)
    {
        $user = Auth()->user();

        if ($user->status == 'confirmed') {
            return response()->json([
                'status' => 'failed',
                'message' => 'user_already_confirmed',
            ]);
        }

        if ($user->email != $request->get('email')) {
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
            'required' => 'required',
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

        if ($user->type != 'driver') {
            return response()->json([
                'status' => 'failed',
                'message' => 'user_not_driver',
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

    public function createDriverDistricts(Request $request)
    {
        $userId                     = Auth()->id();
        $provinceIds                = $request->get('province_ids') ?? [];
        $cityIds                    = $request->get('city_ids') ?? [];

        $validator = Validator::make($request->all(), [
            'province_ids' => 'nullable|array',
            'city_ids' => 'nullable|array',
        ], [
            'nullable' => 'nullable',
            'array' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        $index = 0;
        foreach ($provinceIds as $provinceId) {
            $index++;
            $driverProvince = DriverProvince::where([
                'user_id' => $userId,
                'province_id' => $provinceId,
            ])->first();

            if (!$driverProvince) {
                $province = Province::find($provinceId);

                if ($province) {
                    $driverProvince                     = new DriverProvince;
                    $driverProvince->user_id            = $userId;
                    $driverProvince->province_id        = $provinceId;
                    $driverProvince->status             = 'confirmed';
                    $driverProvince->save();
                }
            }
        }

        foreach ($cityIds as $cityId) {
            $index++;
            $driverCity = DriverCity::where([
                'user_id' => $userId,
                'city_id' => $cityId,
            ])->first();

            if (!$driverCity) {
                $city = City::find($cityId);

                if ($city) {
                    $driverProvinceExists = DriverProvince::where([
                        'user_id' => $userId,
                        'province_id' => $city->province_id,
                    ])->exists();

                    if (!$driverProvinceExists) {
                        $driverProvince                 = new DriverProvince;
                        $driverProvince->user_id        = $userId;
                        $driverProvince->province_id    = $city->province_id;
                        $driverProvince->status         = 'confirmed';
                        $driverProvince->save();
                    }

                    $driverCity                     = new DriverCity;
                    $driverCity->user_id            = $userId;
                    $driverCity->city_id            = $cityId;
                    $driverCity->status             = 'confirmed';
                    $driverCity->save();
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [],
        ]);
    }

    public function removeDriverDistricts(Request $request)
    {
        $userId         = Auth()->id();
        $provinceIds    = $request->get('province_ids') ?? [];
        $cityIds        = $request->get('city_ids') ?? [];

        $validator = Validator::make($request->all(), [
            'province_ids'  => 'nullable|array',
            'city_ids'      => 'nullable|array',
        ], [
            'nullable' => 'nullable',
            'array' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        foreach ($provinceIds as $provinceId) {
            DriverProvince::where([
                'user_id' => $userId,
                'province_id' => $provinceId,
            ])->delete();

            DriverCity::where([
                'driver_cities.user_id' => $userId,
                'cities.province_id' => $provinceId,
            ])
                ->join('cities', 'cities.id', '=', 'driver_cities.city_id')
                ->delete();
        }

        foreach ($cityIds as $cityId) {
            DriverCity::where([
                'user_id' => $userId,
                'city_id' => $cityId,
            ])->delete();
        }

        return response()->json([
            'status' => 'success',
            'data' => [],
        ]);
    }

    public function createDriverLanguages(Request $request)
    {
        $userId         = Auth()->id();
        $languageIds    = $request->get('language_ids') ?? [];

        $validator = Validator::make($request->all(), [
            'language_ids'  => 'required|array',
        ], [
            'required' => 'required',
            'array' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        foreach ($languageIds as $languageId) {
            $driverLanguage = DriverLanguage::where([
                'user_id' => $userId,
                'language_id' => $languageId,
            ])->first();

            if (!$driverLanguage) {
                $language = Language::find($languageId);

                if ($language) {
                    $driverLanguage = new DriverLanguage;
                    $driverLanguage->user_id = $userId;
                    $driverLanguage->language_id = $languageId;
                    $driverLanguage->status = 'confirmed';
                    $driverLanguage->save();
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [],
        ]);
    }

    public function removeDriverLanguages(Request $request)
    {
        $userId         = Auth()->id();
        $languageIds    = $request->get('language_ids') ?? [];

        $validator = Validator::make($request->all(), [
            'language_ids'  => 'required|array',
        ], [
            'required' => 'required',
            'array' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        foreach ($languageIds as $languageId) {
            DriverLanguage::where([
                'user_id' => $userId,
                'language_id' => $languageId,
            ])->delete();
        }

        return response()->json([
            'status' => 'success',
            'data' => [],
        ]);
    }

    public function createDriverCar(Request $request)
    {
        $user = Auth()->user();

        if ($user->status == 'confirmed') {
            return response()->json([
                'status' => 'failed',
                'message' => 'car_already_confirmed',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'car_id'                => 'required|numeric',
            'plate_type'            => 'required|in:private,taxi,public',
            'plate_two_numbers'     => 'required|numeric|max:99|min:11',
            'plate_letter'          => 'required|alpha|max:1',
            'plate_three_numbers'   => 'required|numeric|max:999|min:111',
            'plate_city_code'       => 'required|numeric|max:99|min:11',
            'color'                 => 'required|alpha_num|max:100',
            'image'                 => 'nullable|image',
        ], [
            'alpha' => 'alpha',
            'numeric' => 'numeric',
            'nullable' => 'nullable',
            'max' => 'max',
            'required' => 'required',
            'in' => 'in',
            'image' => 'image',
            'alpha_num' => 'alpha_num',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        if ($user->type != 'driver') {
            return response()->json([
                'status' => 'failed',
                'message' => 'user_not_driver',
            ]);
        }

        DriverCar::where([
            'user_id' => $user->id,
        ])->update([
            'status' => 'canceled',
        ]);

        $driverCar                        = new DriverCar;
        $driverCar->user_id               = $user->id;
        $driverCar->car_id                = $request->get('car_id');
        $driverCar->plate_type            = $request->get('plate_type');
        $driverCar->plate_two_numbers     = $request->get('plate_two_numbers');
        $driverCar->plate_letter          = $request->get('plate_letter');
        $driverCar->plate_three_numbers   = $request->get('plate_three_numbers');
        $driverCar->plate_city_code       = $request->get('plate_city_code');
        $driverCar->color                 = $request->get('color');

        if ($request->file('image')) {
            $driverCar->image = Storage::putFile('public/cars', $request->file('image'));
        } else {
            $driverCar->image = '';
        }

        $driverCar->status = 'pending';
        $driverCar->save();

        $user->status = 'pending';
        $user->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'driver_car' => $driverCar,
            ],
        ]);
    }
}
