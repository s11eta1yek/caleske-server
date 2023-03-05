<?php

namespace App\Http\Controllers\User\History;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Travel;
use App\Models\TravelPoint;
use App\Models\User;
use App\Models\Car;

class HistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getTravelRecords(Request $request)
    {
        $userId = Auth()->id();

        $travelRecords = Travel::select([
            'travels.id',
            'travels.user_id',
            'travels.driver_id',
            'travels.driver_car_id',
            'drivers.username as driver_username',
            'drivers.first_name as driver_first_name',
            'drivers.last_name as driver_last_name',
            'drivers.cellphone as driver_cellphone',
            'drivers.gender as driver_gender',
            'drivers.avatar as driver_avatar',
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
            ->where([
                'travels.user_id' => $userId,
                'travels.is_finished' => 1,
            ])
            ->leftJoin('users as drivers', 'drivers.id', '=', 'travels.driver_id')
            ->leftJoin('languages', 'languages.id', '=', 'travels.language_id')
            ->orderBy('travels.created_at', 'DESC')
            ->paginate(12);

        foreach ($travelRecords as $travelRecord) {
            $travelRecord->points = TravelPoint::select([
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
                    'travel_points.travel_id' => $travelRecord->id,
                ])
                ->join('cities', 'cities.id', '=', 'travel_points.city_id')
                ->join('provinces', 'provinces.id', '=', 'travel_points.province_id')
                ->orderBy('order', 'ASC')
                ->get();

            if ($travelRecord->driver_id) {
                $travelRecord->driver_car = Car::select([
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
                        ['driver_cars.user_id', '=', $travelRecord->driver_id],
                        ['driver_cars.status', '=', 'confirmed'],
                    ])
                    ->join('driver_cars', 'driver_cars.car_id', '=', 'cars.id')
                    ->orderBy('driver_cars.created_at', 'desc')
                    ->first();
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'travel_records' => $travelRecords,
            ],
        ]);
    }

    public function getTravelRecord(Request $request)
    {
        $userId = Auth()->id();
        $travelId = $request->get('travel_id');

        $travelRecord = Travel::select([
            'travels.id',
            'travels.user_id',
            'travels.driver_id',
            'travels.driver_car_id',
            'drivers.username as driver_username',
            'drivers.first_name as driver_first_name',
            'drivers.last_name as driver_last_name',
            'drivers.cellphone as driver_cellphone',
            'drivers.gender as driver_gender',
            'drivers.avatar as driver_avatar',
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
            ->where([
                'travels.id' => $travelId,
                'travels.user_id' => $userId,
                'travels.is_finished' => 1,
            ])
            ->leftJoin('users as drivers', 'drivers.id', '=', 'travels.driver_id')
            ->leftJoin('languages', 'languages.id', '=', 'travels.language_id')
            ->first();

        if (!$travelRecord) {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_record_not_exist',
            ]);
        }

        $travelRecord->points = TravelPoint::select([
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
                'travel_points.travel_id' => $travelRecord->id,
            ])
            ->join('cities', 'cities.id', '=', 'travel_points.city_id')
            ->join('provinces', 'provinces.id', '=', 'travel_points.province_id')
            ->orderBy('order', 'ASC')
            ->get();

        if ($travelRecord->driver_id) {
            $travelRecord->driver_car = Car::select([
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
                    ['driver_cars.user_id', '=', $travelRecord->driver_id],
                    ['driver_cars.status', '=', 'confirmed'],
                ])
                ->join('driver_cars', 'driver_cars.car_id', '=', 'cars.id')
                ->orderBy('driver_cars.created_at', 'desc')
                ->first();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'travel_record' => $travelRecord,
            ],
        ]);
    }
}
