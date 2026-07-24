<?php

namespace App\Models;

use App\Traits\HasGuid;
use Illuminate\Database\Eloquent\Model;

class GrapeVariety extends Model
{
    use HasGuid;

    protected $fillable = ['name'];

    protected $hidden = ['id'];
}
