<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'claimer_id',
        'finder_id',
        'status'
    ];

    protected $casts = [
        'status' => ClaimStatus::class
    ];

    function item(){
        return $this->belongsTo(Item::class);
    }

    function claimer(){
        return $this->belongsTo(User::class, 'claimer_id');
    }

    function finder(){
        return $this->belongsTo(User::class, 'finder_id');
    }

    function messages(){
        return $this->hasMany(Message::class);
    }
}
