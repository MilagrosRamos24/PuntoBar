<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Mesa extends Model
{
    protected $fillable = [
        'numero',
        'estado',
        'mozo_id',
        'cantidad_personas',
    ];

    public function mozo()
    {
        return $this->belongsTo(User::class, 'mozo_id');
    }
}