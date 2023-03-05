<?php

namespace App\Http\Controllers\Driver\Travel;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TravelPoint;
use App\Models\Transaction;
use App\Models\Travel;
use App\Models\Rate;
use App\Models\User;
use App\Models\Car;
use App\Models\Location;
use App\Events\TravelEnded;
use App\Events\TravelCanceled;

class TravelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    protected int $companyPercentage = 20;

    public function getActiveTravel(Request $request)
    {
        $userId = Auth()->id();

        $travel = Travel::select([
            'travels.id',
            'travels.user_id',
            'users.username as user_username',
            'users.first_name as user_first_name',
            'users.last_name as user_last_name',
            'users.cellphone as user_cellphone',
            'users.gender as user_gender',
            'users.avatar as user_avatar',
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
            ->join('users', 'users.id', '=', 'travels.user_id')
            ->leftJoin('languages', 'languages.id', '=', 'travels.language_id')
            ->where([
                'travels.driver_id' => $userId,
                'travels.status' => 'started',
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

        $travel->driver                 = [];
        $travel->driver_car             = [];

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

        if ($travel->driver_id) {
            $travel->driver = User::select([
                'users.id',
                'users.username',
                'users.first_name',
                'users.last_name',
                'users.gender',
            ])
                ->where([
                    'id' => $travel->driver_id,
                ])
                ->first();

            $travel->driver_car = Car::select([
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
            ])
                ->where([
                    ['driver_cars.user_id', '=', $travel->driver_id],
                    ['driver_cars.status', '=', 'confirmed'],
                ])
                ->join('driver_cars', 'driver_cars.car_id', '=', 'cars.id')
                ->orderBy('driver_cars.created_at', 'desc')
                ->first();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'travel' => $travel,
            ],
        ]);
    }

    // public function startTravel(Request $request)
    // {
    //     $userId = Auth()->id();
    //     $travelId = $request->get('travel_id');

    //     $travel = Travel::where([
    //         'id' => $travelId,
    //         'driver_id' => $userId,
    //     ])->first();

    //     if (!$travel) {
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'travel_not_exist',
    //         ]);
    //     }

    //     if ($travel->status != 'not_started') {
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => 'travel_is_started',
    //         ]);
    //     }

    //     $travel->travel_start_time      = date("Y-m-d H:i:s");
    //     $travel->status                 = 'started';
    //     $travel->save();

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //             'travel' => $travel,
    //         ],
    //     ]);
    // }

    public function endTravel(Request $request)
    {
        $userId = Auth()->id();
        $travelId = $request->get('travel_id');

        $travel = Travel::where([
            'id' => $travelId,
            'driver_id' => $userId,
        ])->first();

        if (!$travel) {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_not_exist',
            ]);
        }

        if ($travel->status != 'started') {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_is_finished',
            ]);
        }

        $travel->travel_end_time        = date("Y-m-d H:i:s");
        $travel->status                 = 'finished';
        $travel->is_finished            = 1;
        $travel->save();

        if ($travel->payment_type == 'online') {
            $transaction = Transaction::where([
                'travel_id' => $travel->id,
                'type' => 'travel_cost',
            ])->update([
                'status' => 'success',
            ]);

            $transaction                = new Transaction;
            $transaction->travel_id     = $travel->id;
            $transaction->user_id       = $travel->driver_id;
            $transaction->amount        = $travel->price - ($travel->price * $this->companyPercentage);
            $transaction->type          = 'travel_income';
            $transaction->status        = 'success';
            $transaction->save();
        } else {
            $transaction                = new Transaction;
            $transaction->travel_id     = $travel->id;
            $transaction->user_id       = $travel->driver_id;
            $transaction->amount        = -($travel->price * $this->companyPercentage);
            $transaction->type          = 'travel_income';
            $transaction->status        = 'success';
            $transaction->save();
        }

        // broadcast(new TravelEnded($travel));

        return response()->json([
            'status' => 'success',
            'data' => [
                'travel' => $travel,
            ],
        ]);
    }

    public function cancelTravel(Request $request)
    {
        
        $userId = Auth()->id();
        $travelId = $request->get('travel_id');

        $travel = Travel::where([
            'id' => $travelId,
            'driver_id' => $userId,
        ])->first();

        if (!$travel) {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_not_exist',
            ]);
        }

        if ($travel->is_finished) {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_is_started',
            ]);
        }

        $travel->status             = 'failed';
        $travel->canceled_by_id     = $userId;
        $travel->is_finished        = 1;
        $travel->save();

        if ($travel->payment_type == 'online') {
            $transaction = Transaction::where([
                'travel_id' => $travel->id,
                'type' => 'travel_cost',
            ])->update([
                'status' => 'failed',
            ]);
        }

        // broadcast(new TravelCanceled($travel));

        return response()->json([
            'status' => 'success',
            'data' => [
                'travel' => $travel,
            ],
        ]);
    }

    // public function rateTravel(Request $request)
    // {
    //     $userId                         = Auth()->id();
    //     $travelId                       = $request->get('travel_id');
    //     $description                    = $request->get('description');
    //     $value                          = $request->get('value');
    //     $items                          = $request->get('items');
    //     $type                           = $request->get('type');

    //     $validator = Validator::make($request->all(), [
    //         'travel_id'                 => 'required|numeric',
    //         'description'               => 'nullable|max:255',
    //         'value'                     => 'required|in:1,2,3,4,5',
    //         'items'                     => 'nullable|array',
    //         'type'                      => 'required|in:private,public',
    //     ], [
    //          'numeric' => 'numeric',
    //          'array' => 'array',
    //          'nullable' => 'nullable',
    //          'max' => 'max',
    //          'required' => 'required',
    //          'in' => 'in',
    //      ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'failed',
    //             'validation' => $validator->errors(),
    //         ]);
    //     }

    //     $travel = Travel::where([
    //         'id' => $travelId,
    //         'user_id' => $userId,
    //     ])->first();

    //     if (!$travel) {
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'travel_not_exist',
    //         ]);
    //     }

    //     if (!$travel->is_finished || $travel->status != 'fnished') {
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => 'travel_is_not_finished',
    //         ]);
    //     }

    //     $rate = Rate::where([
    //         'user_id' => $userId,
    //         'travel_id' => $travel->id,
    //         'made_by' => 'driver',
    //     ])->first();

    //     if ($rate) {
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'user_already_rated',
    //         ]);
    //     }

    //     $rate                           = new Rate;
    //     $rate->user_id                  = $userId;
    //     $rate->travel_id                = $travel->id;
    //     $rate->description              = $description;
    //     $rate->value                    = $value;
    //     $rate->items                    = json_encode($items);
    //     $rate->made_by                  = 'driver';
    //     $rate->type                     = $type;
    //     $rate->save();

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //             'rate' => $rate,
    //         ],
    //     ]);
    // }

    public function reportTravel(Request $request)
    {
        $userId                         = Auth()->id();
        $travelId                       = $request->get('travel_id');
        $type                           = $request->get('type');
        $title                          = $request->get('title');
        $description                    = $request->get('description');

        $validator = Validator::make($request->all(), [
            'travel_id'                 => 'required|numeric',
            'type'                      => 'required|in:complaint,security,emergency',
            'title'                     => 'required|max:100',
            'description'               => 'required|max:255',
        ], [
            'numeric' => 'numeric',
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

        $travel = Travel::where([
            'id' => $travelId,
            'driver_id' => $userId,
        ])->first();

        if (!$travel) {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_not_exist',
            ]);
        }

        if ($travel->status != 'not_started') {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_is_not_finished',
            ]);
        }

        $report                         = new Report;
        $report->user_id                = $userId;
        $report->travel_id              = $travel->id;
        $report->made_by                = 'driver';
        $report->type                   = $type;
        $report->title                  = $title;
        $report->description            = $description;
        $report->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'report' => $report,
            ],
        ]);
    }

    public function sendLocation(Request $request)
    {
        $user                           = Auth()->user();
        $cityId                         = $request->get('city_id');
        $travelId                       = $request->get('travel_id');
        $latitude                       = (float) $request->get('latitude');
        $longitude                      = (float) $request->get('longitude');

        $validator = Validator::make($request->all(), [
            'city_id'                   => 'required|exists:cities,id',
            'travel_id'                 => 'nullable|numeric',
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
            'numeric' => 'numeric',
            'nullable' => 'nullable',
            'required' => 'required',
            'regex' => 'regex',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'validation' => $validator->errors(),
            ]);
        }

        $user->latest_city_id           = $cityId;
        $user->latest_latitude          = $latitude;
        $user->latest_longitude         = $longitude;
        $user->location_updated_at      = date("Y-m-d H:i:s", strtotime('now'));
        $user->save();

        $location                       = new Location;
        $location->user_id              = $user->id;
        $location->city_id              = $cityId;
        $location->travel_id            = $travelId;
        $location->latitude             = $latitude;
        $location->longitude            = $longitude;
        $location->save();

        return response()->json([
            'status' => 'success',
            'data' => [],
        ]);
    }
}
