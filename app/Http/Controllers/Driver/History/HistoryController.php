<?php

namespace App\Http\Controllers\Driver\History;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Travel;
use App\Models\TravelPoint;

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
            ->where([
                'travels.driver_id' => $userId,
                'travels.is_finished' => 1,
            ])
            ->join('users', 'users.id', '=', 'travels.user_id')
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
            ->where([
                'travels.id' => $travelId,
                'travels.driver_id' => $userId,
                'travels.is_finished' => 1,
            ])
            ->join('users', 'users.id', '=', 'travels.user_id')
            ->leftJoin('languages', 'languages.id', '=', 'travels.language_id')
            ->first();

        if (!$travelRecord) {
            return response()->json([
                'status' => 'failed',
                'message' => 'travel_not_exist',
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

        return response()->json([
            'status' => 'success',
            'data' => [
                'travel_record' => $travelRecord,
            ],
        ]);
    }
}
