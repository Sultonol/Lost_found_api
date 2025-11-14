<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
      use HasFactory;

    protected $fillable = [
        'claim_id',
        'sender_id',
        'receiver_id',
        'message',
    ];

    public const UPDATED_AT = null;

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
