<?php

namespace App\Models;

use App\Traits\HasGuid;
use Illuminate\Database\Eloquent\Model;

class Winery extends Model
{
    use HasGuid;

    protected $fillable = ['name'];

    protected $hidden = ['id'];
}
