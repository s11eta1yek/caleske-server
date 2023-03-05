<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Travel extends Model
{
    use HasFactory;

    protected $table = 'travels';

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function passengers()
    {
        return $this->hasMany(Passenger::class);
    }

    public function travel_data()
    {
        return $this->hasOne(TravelData::class);
    }
}
