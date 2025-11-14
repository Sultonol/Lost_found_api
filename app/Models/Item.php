<?php

namespace App\Models;

use App\Enums\ItemStatus;
use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'item_name',
        'description',
        'image_url',
        'location',
        'report_type',
        'report_date',
        'status',
    ];

    protected $casts = [
        'report_date' => 'date',
        'report_type' => ReportType::class,
        'status' => ItemStatus::class,
    ];

    function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    function category(){
        return $this->belongsTo(Category::class);
    }

    function claims(){
        return $this->hasMany(Claim::class);
    }
}
