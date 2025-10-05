<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;
    public $incrementing = false;        // UUID primary key
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'phone',
        'email',
        'version',
    ];


}
