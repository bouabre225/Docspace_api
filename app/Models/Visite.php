<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasUuid;

class Visite extends Model
{
    use HasUuid;

    protected $fillable = ['visitor_id', 'user_id', 'annonce_id', 'page', 'referer'];
}
