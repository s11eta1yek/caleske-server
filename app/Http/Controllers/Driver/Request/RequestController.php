<?php

namespace App\Http\Controllers\Driver\Request;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Car;
use App\Models\User;
use App\Models\DriverCity;
use App\Models\Travel;
use App\Models\TravelPoint;
use App\Models\TravelRequest;
use App\Models\TravelData;
use App\Events\TravelStarted;

class RequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getPublicTravels(Request $request)
    {
        $user                           = Auth()->user();
        $cityId                         = $request->get('city_id');
        // $isReserve                      = $request->get('is_reserve');
        $latitude                       = (float) $request->get('latitude');
        $longitude                      = (float) $request->get('longitude');

        $validator = Validator::make($request->all(), [
            'city_id'                   => 'required|exists:cities,id',
            // 'is_reserve'                => 'required|boolean',
            'latitude'                  => [
                'required',
                'regex:/^[+-]?([0-9]+\.?[0-9]*|\.[0-9]+)$/',
            ],
            'longitude'                 => [
                'required',
                'regex:/^[+-]?([0-9]+\.?[0-9]*|\.[0-9]+)$/',
            ],
        ], [
            'boolean' => 'boolean',
            'exists' => 'exists',
            'required' => 'required',
            'regex' => 'regex',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        if ($user->type != 'driver' || $user->status != 'confirmed') {
            return response()->json([
                'status' => 'failed',
                'message' => 'driver_not_valid',
            ]);
        }

        $driver = $user;
        $driverCar = Car::select([
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
            'driver_cars.updated_at',
        ])
            ->where([
                ['driver_cars.user_id', '=', $driver->id],
                ['driver_cars.status', '=', 'confirmed'],
            ])
            ->join('driver_cars', 'driver_cars.car_id', '=', 'cars.id')
            ->orderBy('driver_cars.created_at', 'desc')
            ->first();

        if (!$driverCar) {
            return response()->json([
                'status' => 'failed',
                'message' => 'car_not_valid',
            ]);
        }

        $driverCities = DriverCity::where([
            'driver_cities.user_id' => $driver->id,
        ])
            ->join('cities', 'cities.id', '=', 'driver_cities.city_id')
            ->get();

        $travels = Travel::select([
            'travels.id',
            'travels.user_id',
            // 'users.username as user_username',
            'users.first_name as user_first_name',
            'users.last_name as user_last_name',
            // 'users.cellphone as user_cellphone',
            // 'users.gender as user_gender',
            // 'users.avatar as user_avatar',
            'travels.driver_id',
            'travels.driver_car_id',
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
            DB::raw("
                (SELECT
                    (
                        ROUND(111.111 *
                        DEGREES(ACOS(LEAST(1.0, COS(RADIANS($latitude))
                        * COS(RADIANS(`tp`.`latitude`))
                        * COS(RADIANS($longitude - `tp`.`longitude`))
                        + SIN(RADIANS($latitude))
                        * SIN(RADIANS(`tp`.`latitude`))))))
                    ) AS `distance_in_km`
                    FROM `travel_points` as `tp`
                    WHERE `tp`.`travel_id` = `travels`.`id` AND `tp`.`order` = 0
                ) AS `distance_in_km`
            "),
        ])
            ->where([
                'driver_cities.user_id' => $driver->id,
                'travels.request_type' => 'public',
                'travels.status' => 'no_driver',
                'travel_points.order' => 0,
                'travel_points.city_id' => $cityId,
                'travels.is_reserve' => 0, // replace with bellow lines
            ])
            // ->when($isReserve, function ($query) {
            //     $query->where('travels.is_reserve', 1);
            // }, function ($query) {
            //     $query->where('travels.is_reserve', 0);
            // })
            ->where(function ($query) use ($cityId) {
                $query->where([
                    ['travel_points.order', '=', 0],
                    ['travel_points.city_id', '=', $cityId],
                ]);
            })
            ->where(function ($query) use ($driverCar) {
                $query->where([
                    ['travels.car_type', '=', $driverCar->car_type],
                ])->orWhere([
                    ['travels.car_type', '=', 'all'],
                ]);
            })
            ->join('users', 'users.id', '=', 'travels.user_id')
            ->join('travel_points', 'travel_points.travel_id', '=', 'travels.id')
            ->leftJoin('driver_cities', 'driver_cities.city_id', '=', 'travel_points.city_id')
            ->leftJoin('languages', 'languages.id', '=', 'travels.language_id')
            ->groupBy(
                'travels.id',
                'travels.user_id',
                'users.first_name',
                'users.last_name',
                'users.cellphone',
                'users.avatar',
                'travels.driver_id',
                'travels.driver_car_id',
                'travels.language_id',
                'languages.title',
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
            )
            ->orderBy('distance_in_km', 'ASC') // replace with code bellow
            // ->when($isReserve, function ($query) {
            //     $query->orderBy('travels.reserve_time', 'ASC');
            // }, function ($query) {
            //     $query->orderBy('distance_in_km', 'ASC');
            // })
            ->paginate(12);

        foreach ($travels as $travel) {
            $travel->points = TravelPoint::select([
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
                    'travel_points.travel_id' => $travel->id,
                ])
                ->join('cities', 'cities.id', '=', 'travel_points.city_id')
                ->join('provinces', 'provinces.id', '=', 'travel_points.province_id')
                ->orderBy('order', 'ASC')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'travels' => $travels,
            ],
        ]);
    }

    // public function getPrivateTravels(Request $request)
    // {
    //     $user = Auth()->user();

    //     if ($user->type != 'driver' || $user->status != 'confirmed') {
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => 'driver_not_valid',
    //         ]);
    //     }

    //     $travels = TravelRequest::select([
    //         'travels.id',
    //         'travels.user_id',
    //         'travels.driver_id',
    //         'travels.driver_car_id',
    //         'travels.language_id',
    //         'languages.title as language_title',
    //         'travels.discount_id',
    //         'travels.canceled_by_id',
    //         'travels.price',
    //         'travels.discount_price',
    //         'travels.final_price',
    //         'travels.request_type',
    //         'travels.payment_type',
    //         'travels.payment_status',
    //         'travels.status',
    //         'travels.is_finished',
    //         'travels.travel_start_time',
    //         'travels.travel_end_time',
    //         'travels.is_reserve',
    //         'travels.reserve_time',
    //         'travels.car_type',
    //         'travels.travel_options',
    //         'travels.description',
    //         'travels.created_at',
    //         'travels.updated_at',
    //     ])
    //         ->where([
    //             'driver_id' => $user->id,
    //             'status' => 'pending',
    //         ])
    //         ->join('travels', 'travels.id', '=', 'travel_requests.travel_id')
    //         ->leftJoin('languages', 'languages.id', '=', 'travels.language_id')
    //         ->paginate(12);

    //     foreach ($travels as $travel) {
    //         $travel->points = TravelPoint::select([
    //             'travel_points.id',
    //             'travel_points.travel_id',
    //             'provinces.title as province',
    //             'cities.title as city',
    //             'travel_points.address',
    //             'travel_points.latitude',
    //             'travel_points.longitude',
    //             'travel_points.order',
    //         ])
    //             ->where([
    //                 'travel_points.travel_id' => $travel->id,
    //             ])
    //             ->join('cities', 'cities.id', '=', 'travel_points.city_id')
    //             ->join('provinces', 'provinces.id', '=', 'travel_points.province_id')
    //             ->orderBy('order', 'ASC')
    //             ->get();
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //             'travels' => $travels,
    //         ],
    //     ]);
    // }

    public function sendRequest(Request $request)
    {
        $user = Auth()->user();
        $travelId = $request->get('travel_id');

        if ($user->type != 'driver' || $user->status != 'confirmed') {
            return response()->json([
                'status' => 'failed',
                'message' => 'driver_not_valid',
            ]);
        }

        $preTravel = Travel::where([
            'driver_id' => $user->id,
            'status' => 'started',
        ])->first();

        if ($preTravel) {
            return response()->json([
                'status' => 'failed',
                'message' => 'driver_has_active_travel',
            ]);
        }

        $travel = Travel::find($travelId);

        if (!$travel) {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_not_exist',
            ]);
        }

        if ($travel->request_type != 'public' || $travel->status != 'no_driver') {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_has_driver',
            ]);
        }

        $travelRequest                  = new TravelRequest;
        $travelRequest->travel_id       = $travel->id;
        $travelRequest->user_id         = $travel->user_id;
        $travelRequest->driver_id       = $user->id;
        $travelRequest->made_by         = 'driver';
        $travelRequest->status          = 'accepted'; // 'pending';
        $travelRequest->save();

        // User Accept Request

        $driver = [
            'id'                        => $user->id,
            'username'                  => $user->username,
            'first_name'                => $user->first_name,
            'last_name'                 => $user->last_name,
            'gender'                    => $user->gender,
            'avatar'                    => $user->avatar,
            'type'                      => $user->type,
            'status'                    => $user->status,
        ];

        $driverCar = Car::select([
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
            'driver_cars.updated_at',
        ])
            ->where([
                ['driver_cars.user_id', '=', $user->id],
                ['driver_cars.status', '=', 'confirmed'],
            ])
            ->join('driver_cars', 'driver_cars.car_id', '=', 'cars.id')
            ->orderBy('driver_cars.created_at', 'desc')
            ->first();

        $travel->driver_id              = $user->id;
        $travel->driver_car_id          = $driverCar->id;
        $travel->travel_start_time      = date("Y-m-d H:i:s");
        $travel->status                 = 'started';
        $travel->save();

        $travelData = TravelData::where([
            'travel_id' => $travel->id,
        ])->first();

        if ($travelData) {
            $travelData->driver                 = json_encode($driver);
            $travelData->driver_car             = json_encode($driverCar);
            $travelData->save();
        }

        // User Accept Request

        broadcast(new TravelStarted($travel));

        return response()->json([
            'status' => 'success',
            'data' => [
                'travel_request' => $travelRequest,
            ],
        ]);
    }

    // public function acceptRequest(Request $request)
    // {
    //     $user                   = Auth()->user();
    //     $travelRequestId        = $request->get('travel_request_id');

    //     $travelRequest = TravelReqeuest::where([
    //         'id'                => $travelRequestId,
    //         'driver_id'         => $user->id,
    //         'made_by'           => 'user',
    //         'status'            => 'pending',
    //     ])->first();

    //     if (!$travelRequest) {
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => 'travel_request_not_exist',
    //         ]);
    //     }

    //     $travel = Travel::find($travelRequest->travel_id);

    //     if ($travel->request_type != 'private' || $travel->status != 'no_driver') {
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'travel_not_valid',
    //         ]);
    //     }

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
    //         ->where('id', $travelRequest->driver_id)
    //         ->first();

    //     $driverCar = Car::select([
    //         'driver_cars.id',
    //         'driver_cars.user_id',
    //         'driver_cars.car_id',
    //         'cars.type as car_type',
    //         'cars.title',
    //         'cars.brand',
    //         'driver_cars.plate_type',
    //         'driver_cars.plate_two_numbers',
    //         'driver_cars.plate_letter',
    //         'driver_cars.plate_three_numbers',
    //         'driver_cars.plate_city_code',
    //         'driver_cars.color',
    //         'driver_cars.image',
    //         'driver_cars.status',
    //         'driver_cars.updated_at',
    //     ])
    //         ->where([
    //             ['driver_cars.user_id', '=', $user->id],
    //             ['driver_cars.status', '=', 'confirmed'],
    //         ])
    //         ->join('driver_cars', 'driver_cars.car_id', '=', 'cars.id')
    //         ->orderBy('driver_cars.created_at', 'desc')
    //         ->first();
        
    //     if (!$travel->is_reserve) {
    //         $travel->travel_start_time      = date("Y-m-d H:i:s");
    //         $travel->status                 = 'started';
    //     } else {
    //         $travel->status                 = 'not_started';
    //     }

    //     $travel->driver_id              = $user->id;
    //     $travel->driver_car_id          = $driverCar->id;
    //     $travel->save();

    //     $travelRequest->status = 'accepted';
    //     $travelRequest->save();

    //     TravelRequest::where([
    //         ['id', '!=', $travelRequest->id],
    //         ['travel_id', '=', $travel->id],
    //     ])->update([
    //         'status' => 'rejected',
    //     ]);

    //     $travelData = TravelData::where([
    //         'travel_id' => $travel->id,
    //     ])->first();

    //     $travelData->driver                 = json_encode($driver);
    //     $travelData->driver_car             = json_encode($driverCar);
    //     $travelData->save();

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //              'travel_request' => $travelRequest,
    //          ],
    //     ]);
    // }
}
