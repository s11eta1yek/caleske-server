<?php

namespace App\Http\Controllers\User\Request;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Travel;
use App\Models\TravelRequest;
use App\Models\TravelPoint;
use App\Models\TravelData;
use App\Models\Transaction;
use App\Models\Language;
use App\Models\Passenger;
use App\Models\Discount;
use App\Models\User;
use App\Models\City;
use App\Events\TravelFound;

class RequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getNoDriverTravel(Request $request)
    {
        $userId = Auth()->id();

        $travel = Travel::select([
            'travels.id',
            'travels.user_id',
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
        ])
            ->leftJoin('languages', 'languages.id', '=', 'travels.language_id')
            ->where([
                'travels.user_id' => $userId,
                'travels.status' => 'no_driver',
            ])
            ->first();

        if (!$travel) {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_not_exist',
            ]);
        }

        if ($travel->is_finished) {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_is_finished',
            ]);
        }

        $travel->points = TravelPoint::select([
            'travel_points.id',
            'travel_points.travel_id',
            'travel_points.city_id',
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

        // $travel->travel_requests = TravelRequest::select([
        //     'travel_requests.id',
        //     'travel_requests.user_id',
        //     'travel_requests.driver_id',
        //     'travel_requests.travel_id',
        //     'users.username as driver_username',
        //     'users.first_name as driver_first_name',
        //     'users.last_name as driver_last_name',
        //     'users.gender as driver_gender',
        //     'users.avatar as driver_avatar',
        //     'travel_requests.made_by',
        //     'travel_requests.status',
        //     'travel_requests.created_at',
        //     'travel_requests.updated_at',
        // ])
        //     ->where('travel_id', $travel->id)
        //     ->join('users', 'users.id', '=', 'travel_requests.driver_id')
        //     ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'travel' => $travel,
            ],
        ]);
    }

    public function getNearByDrivers(Request $request)
    {
        $userId                         = Auth()->id();
        $cityId                         = $request->get('city_id');
        $latitude                       = (float) $request->get('latitude');
        $longitude                      = (float) $request->get('longitude');

        $validator = Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,id',
            'latitude' => [
                'required',
                'regex:/^[+-]?([0-9]+\.?[0-9]*|\.[0-9]+)$/',
            ],
            'longitude' => [
                'required',
                'regex:/^[+-]?([0-9]+\.?[0-9]*|\.[0-9]+)$/',
            ],
        ], [
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

        $nearByDrivers = User::select([
            'users.latest_longitude as longitude',
            'users.latest_latitude as latitude',
            DB::raw("
                (
                    ROUND(111.111 *
                    DEGREES(ACOS(LEAST(1.0, COS(RADIANS(`users`.`latest_latitude`))
                    * COS(RADIANS($latitude))
                    * COS(RADIANS(`users`.`latest_longitude` - $longitude))
                    + SIN(RADIANS(`users`.`latest_latitude`))
                    * SIN(RADIANS($latitude))))))
                ) AS `distance_in_km`
            "),
        ])
            ->where('users.location_updated_at', '>=', date("Y-m-d H:i:s", strtotime('+3 hours +20 minutes')))
            ->where([
                ['users.type', '=', 'driver'],
                ['users.status', '=', 'confirmed'],
                ['users.latest_city_id', '=', $cityId],
            ])
            ->groupBy(
                'users.latest_longitude',
                'users.latest_latitude',
            )
            ->orderBy('distance_in_km', 'ASC')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'near_by_drivers' => $nearByDrivers,
            ],
        ]);
    }

    protected function calculate($points, $language, $isReturning, $stopTime, $carType, $discount)
    {
        $price = $discountPrice = $finalPrice = 0;

        $price = count($points) * 50000;
        $price += $language ? 3000 : 0;
        $price += $isReturning ? $price * 3/4 : 0;
        $price += $stopTime * 100;
        $price += [
            'samand' => 2000,
            'peugeot' => 5000,
            'lux' => 10000,
            'all' => 0,
        ][$carType];

        if ($discount) {
            $discountPrice = $price * $discount->percentage > $discount->max_discount
                ? $discount->max_discount
                : $price * $discount->percentage;
        }

        $finalPrice = $price - $discountPrice;

        return [
            'price' => $price,
            'discount_price' => $discountPrice,
            'final_price' => $finalPrice,
        ];
    }

    public function calculatePrice(Request $request)
    {
        $userId                         = Auth()->id();
        $languageId                     = $request->get('language_id');
        $discountCode                   = $request->get('discount_code');
        $carType                        = $request->get('car_type');
        $points                         = $request->get('points');
        $stopTime                       = $request->get('stop_time');
        $isReturning                    = $request->get('is_returning');

        $validator = Validator::make($request->all(), [
            'language_id'               => 'nullable|numeric',
            'discount_code'             => 'nullable|alpha_num',
            'reserve_time'              => 'nullable|date|after:now',
            'car_type'                  => 'required|in:samand,peugeot,lux,all',
            'points'                    => 'required|array',
            'stop_time'                 => 'required|numeric',
            'is_returning'              => 'required|boolean',
            'points.*.city_id'          => 'required|exists:cities,id',
            'points.*.address'          => 'required|max:255',
            'points.*.latitude'         => [
                'required',
                'regex:/^[+-]?([0-9]+\.?[0-9]*|\.[0-9]+)$/',
            ],
            'points.*.longitude'        => [
                'required',
                'regex:/^[+-]?([0-9]+\.?[0-9]*|\.[0-9]+)$/',
            ],
        ], [
            'boolean'                   => 'boolean',
            'numeric'                   => 'numeric',
            'array'                     => 'array',
            'nullable'                  => 'nullable',
            'required'                  => 'required',
            'in'                        => 'in',
            'date'                      => 'date',
            'alpha_num'                 => 'alpha_num',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        $language = [];
        if ($languageId) {
            $language = Language::find($languageId);

            if (!$language) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'language_not_exist',
                ]);
            }
        }

        $discount = [];
        if ($discountCode) {
            $discount = Discount::select([
                'discounts.*',
                DB::raw("IF(`expire_at` > NOW(), `has_finished`, 1) as `has_finished`"),
            ])
                ->where([
                    'code' => $discountCode,
                    'user_id' => $userId,
                ])
                ->first();

            if (!$discount || $discount->has_finished) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'discount_not_valid',
                ]);
            }
        }

        $data = $this->calculate($points, $language, $isReturning, $stopTime, $carType, $discount);

        $price                          = $data['price'];
        $discountPrice                  = $data['discount_price'];
        $finalPrice                     = $data['final_price'];

        return response()->json([
            'status' => 'success',
            'data' => [
                'price' => $price,
                'discount_price' => $discountPrice,
                'final_price' => $finalPrice,
            ],
        ]);
    }

    public function createTravel(Request $request)
    {
        $user                           = Auth()->user();
        // $driverId                       = $request->get('driver_id');
        $languageId                     = $request->get('language_id');
        $discountCode                   = $request->get('discount_code');
        // $paymentType                    = $request->get('payment_type');
        // $reserveTime                    = $request->get('reserve_time');
        $carType                        = $request->get('car_type');
        $description                    = $request->get('description');
        $points                         = $request->get('points');
        // $passengers                     = $request->get('passengers');
        $stopTime                       = $request->get('stop_time');
        $isReturning                    = $request->get('is_returning');

        $validator = Validator::make($request->all(), [
            // 'driver_id'                 => 'nullable|numeric',
            'language_id'               => 'nullable|numeric',
            'discount_code'             => 'nullable|alpha_num',
            // 'payment_type'              => 'in:online,cash',
            // 'reserve_time'              => 'nullable|date|after:now',
            'car_type'                  => 'in:samand,peugeot,lux,all',
            'description'               => 'nullable|max:255',
            'points'                    => 'required|array',
            // 'passengers'                => 'nullable|array',
            'stop_time'                 => 'required|numeric',
            'is_returning'              => 'required|boolean',
            'points.*.city_id'          => 'required|exists:cities,id',
            'points.*.address'          => 'required|max:255',
            'points.*.latitude'         => [
                'required',
                'regex:/^[+-]?([0-9]+\.?[0-9]*|\.[0-9]+)$/',
            ],
            'points.*.longitude'        => [
                'required',
                'regex:/^[+-]?([0-9]+\.?[0-9]*|\.[0-9]+)$/',
            ],
            // 'passengers.*.first_name'   => 'required|alpha',
            // 'passengers.*.last_name'    => 'required|alpha',
            // 'passengers.*.father_name'  => 'required|alpha',
            // 'passengers.*.melli_code'   => 'required|numeric',
            // 'passengers.*.gender'       => 'required|in:unkown,male,female',
            // 'passengers.*.stage'        => 'required|in:infant,child,adult',
        ], [
            'after'                     => 'after',
            'boolean'                   => 'boolean',
            'exists'                    => 'exists',
            'alpha'                     => 'alpha',
            'numeric'                   => 'numeric',
            'array'                     => 'array',
            'nullable'                  => 'nullable',
            'max'                       => 'max',
            'required'                  => 'required',
            'in'                        => 'in',
            'regex'                     => 'regex',
            'date'                      => 'date',
            'alpha_num'                 => 'alpha_num',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        if (count($points) < 2) {
            return response()->json([
                'status' => 'failed',
                'message' => 'points_not_enough',
            ]);
        }

        // if ($driverId) {
        //     $driver = User::where([
        //         'id' => $driverId,
        //         'type' => 'driver',
        //         'status' => 'confirmed',
        //     ])->first();

        //     if (!$driver) {
        //         return response()->json([
        //             'status' => 'failed',
        //             'message' => 'driver_not_valid',
        //         ]);
        //     }
        // }

        $language = [];
        if ($languageId) {
            $language = Language::find($languageId);

            if (!$language) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'language_not_exist',
                ]);
            }
        }

        $discount = [];
        if ($discountCode) {
            $discount = Discount::select([
                'discounts.*',
                DB::raw("IF(`expire_at` > NOW(), `has_finished`, 1) as `has_finished`"),
            ])
                ->where([
                    'code' => $discountCode,
                    'user_id' => $user->id,
                ])
                ->first();

            if (!$discount || $discount->has_finished) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'discount_not_exist',
                ]);
            }
        }

        $preTravels = Travel::where([
            'user_id' => $user->id,
        ])
            ->whereIn('status', ['no_driver', 'started'])
            ->get();

        foreach ($preTravels as $preTravel) {
            if ($preTravel->status == 'no_driver') {
                $preTravel->status             = 'failed';
                $preTravel->canceled_by_id     = $user->id;
                $preTravel->is_finished        = 1;
                $preTravel->save();        

                if ($preTravel->payment_type == 'online') {
                    $transaction = Transaction::where([
                        'travel_id' => $preTravel->id,
                        'user_id' => $preTravel->user_id,
                        'type' => 'travel_cost',
                    ])->first();

                    if ($transaction) {
                        $transaction->status = 'failed';
                        $transaction->save();
                    }
                }
            }

            if ($preTravel->status == 'started') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'user_has_active_travel',
                ]);
            }
        }

        $data = $this->calculate($points, $language, $isReturning, $stopTime, $carType, $discount);

        $price                          = $data['price'];
        $discountPrice                  = $data['discount_price'];
        $finalPrice                     = $data['final_price'];

        $possession = Transaction::where([
            'user_id' => $user->id,
        ])
            ->whereIn('status', ['success', 'freeze'])
            ->whereIn('type', ['user_charge', 'user_clear', 'travel_cost'])
            ->sum('amount');

        if ($possession < $finalPrice) {
            return response()->json([
                'status' => 'failed',
                'message' => 'credit_not_enough',
            ]);
        }

        $travel                         = new Travel;
        $travel->user_id                = $user->id;
        $travel->language_id            = $languageId;
        $travel->discount_id            = $discountCode ? $discount->id : null;
        $travel->price                  = $price;
        $travel->discount_price         = $discountPrice;
        $travel->final_price            = $finalPrice;
        $travel->request_type           = 'public'; // $driverId ? 'private' : 'public';
        $travel->payment_type           = 'online'; // $paymentType;
        $travel->payment_status         = 'pending';
        $travel->status                 = 'no_driver';
        $travel->is_finished            = 0;
        $travel->is_reserve             = 0; // $reserveTime ? 1 : 0;
        $travel->reserve_time           = null; // $reserveTime;
        $travel->car_type               = $carType;
        $travel->description            = $description;
        $travel->travel_options         = json_encode([
            'stop_time' => $stopTime,
            'is_returning' => $isReturning,
        ]);
        $travel->save();

        foreach ($points as $key => $point) {
            $city = City::find($point['city_id']);

            if ($city) {
                $travelPoint                    = new TravelPoint;
                $travelPoint->travel_id         = $travel->id;
                $travelPoint->province_id       = $city->province_id;
                $travelPoint->city_id           = $city->id;
                $travelPoint->address           = $point['address'];
                $travelPoint->latitude          = $point['latitude'];
                $travelPoint->longitude         = $point['longitude'];
                $travelPoint->order             = $key;
                $travelPoint->save();
            } else {
                $travel->status             = 'failed';
                $travel->canceled_by_id     = $user->id;
                $travel->is_finished        = 1;
                $travel->save();      

                return response()->json([
                    'status' => 'failed',
                    'message' => 'city_not_exist',
                ]);
            }
        }

        // $passengers[] = [
        //     'first_name'                => $user->first_name,
        //     'last_name'                 => $user->last_name,
        //     'father_name'               => $user->father_name,
        //     'melli_code'                => $user->melli_code,
        //     'gender'                    => $user->gender,
        //     'stage'                     => 'adult',
        // ];

        // $lng = count($passengers);
        // foreach ($passengers as $key => $item) {
        //     $passenger                      = new Passenger;
        //     $passenger->user_id             = $user->id;
        //     $passenger->travel_id           = $travel->id;
        //     $passenger->first_name          = $item['first_name'];
        //     $passenger->last_name           = $item['last_name'];
        //     $passenger->father_name         = $item['father_name'];
        //     $passenger->melli_code          = $item['melli_code'];
        //     $passenger->gender              = $item['gender'];
        //     $passenger->stage               = $item['stage'];
        //     $passenger->is_supervisor       = $lng - 1 == $key ? 1 : 0;
        //     $passenger->save();
        // }

        if (true) {
        // if ($travel->payment_type == 'online') {
            $transaction                = new Transaction;
            $transaction->travel_id     = $travel->id;
            $transaction->user_id       = $user->id;
            $transaction->amount        = -$finalPrice;
            $transaction->type          = 'travel_cost';
            $transaction->status        = 'freeze';
            $transaction->save();
        }

        // if ($driverId) {
        //     $travelRequest                  = new TravelRequest;
        //     $travelRequest->user_id         = $user->id;
        //     $travelRequest->driver_id       = $driverId;
        //     $travelRequest->travel_id       = $travel->id;
        //     $travelRequest->made_by         = 'user';
        //     $travelRequest->status          = 'pending';
        //     $travelRequest->save();
        // }

        $travelData                         = new TravelData;
        $travelData->travel_id              = $travel->id;
        $travelData->driver                 = json_encode([]);
        $travelData->driver_car             = json_encode([]);
        $travelData->passengers             = json_encode([]); // json_encode($passengers);
        $travelData->save();

        // broadcast(new TravelFound($travel));

        return response()->json([
            'status' => 'success',
            'data' => [
                'travel' => $travel,
            ],
        ]);
    }

    // public function acceptRequest(Request $request)
    // {
    //     $userId                 = Auth()->id();
    //     $travelRequestId        = $request->get('travel_request_id');

    //     $travelRequest = TravelRequest::where([
    //         'id'                => $travelRequestId,
    //         'user_id'           => $userId,
    //         'made_by'           => 'driver',
    //         'status'            => 'pending',
    //     ])->first();

    //     if (!$travelRequest) {
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => 'travel_request_not_exist',
    //         ]);
    //     }

    //     $travel = Travel::find($travelRequest->travel_id);

    //     if ($travel->request_type != 'public' || $travel->status != 'no_driver') {
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
    //             ['driver_cars.user_id', '=', $travelRequest->driver_id],
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

    //     $travel->driver_id              = $driver->id;
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
    //         'data' => [],
    //     ]);
    // }
}
