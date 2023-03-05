<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Travel;

class TravelFound implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $travel;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Travel $travel)
    {
        $this->travel = $travel;
    }

    protected function getDrivers()
    {
        $drivers = Travel::select([
            'drivers.id',
            'drivers.first_name',
            'drivers.last_name',
            'drivers.cellphone',
            'drivers.avatar',
            DB::raw("
                (SELECT
                    (
                        ROUND(111.111 *
                        DEGREES(ACOS(LEAST(1.0, COS(RADIANS(`drivers`.`latest_latitude`))
                        * COS(RADIANS(`tp`.`latitude`))
                        * COS(RADIANS(`drivers`.`latest_longitude` - `tp`.`longitude`))
                        + SIN(RADIANS(`drivers`.`latest_latitude`))
                        * SIN(RADIANS(`tp`.`latitude`))))))
                    ) AS `distance_in_km`
                    FROM `travel_points` as `tp`
                    WHERE `tp`.`travel_id` = `travels`.`id` AND `tp`.`order` = 0
                ) AS `distance_in_km`
            "),
        ])
            ->where('drivers.location_updated_at', '>=', date("Y-m-d H:i:s", strtotime('+3 hours +20 minutes')))
            ->where([
                ['travels.id', '=', $this->travel->id],
                ['drivers.type', '=', 'driver'],
                ['drivers.status', '=', 'confirmed'],
                ['driver_cities.id', '!=', null],
                ['travel_points.order', '=', 0],
            ])
            ->join('travel_points', 'travel_points.travel_id', '=', 'travels.id')
            ->leftJoin('users as drivers', 'drivers.latest_city_id', '=', 'travel_points.city_id')
            ->leftJoin('driver_cities', function ($join) {
                $join->on('driver_cities.city_id', 'travel_points.city_id')
                    ->on('driver_cities.user_id', 'drivers.id');
            })
            ->join('users', 'users.id', '=', 'travels.user_id')
            ->groupBy(
                'travels.id',
                'drivers.id',
                'drivers.first_name',
                'drivers.last_name',
                'drivers.cellphone',
                'drivers.avatar',
                'drivers.latest_longitude',
                'drivers.latest_latitude',
            )
            ->orderBy('distance_in_km', 'ASC')
            ->get();

        return $drivers;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $drivers = $this->getDrivers();

        $privateChannels = [];

        foreach ($drivers as $driver) {
            $privateChannels[] = new PrivateChannel('user.' . $driver->id);
        }

        return $privateChannels;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'status' => 'success',
            'data' => [
                'travel' => $this->travel,
            ],
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'travel.found';
    }
}
