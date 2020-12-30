<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostHistory extends Model
{
    protected $guarded = [];
    public function postwatcher()
    {
        return $this->belongsTo('App\PostWatcher');
    }
}
