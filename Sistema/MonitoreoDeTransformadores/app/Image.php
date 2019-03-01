<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    public $timestamps = false;

    public function monitor()
    {
      return $this->belongsTo('App\Monitor');
    }
}
