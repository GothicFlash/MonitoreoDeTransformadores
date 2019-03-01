<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Gas extends Model
{
    public $timestamps = false;

    public function monitors()
    {
      return $this->belongsToMany('App\Monitor','detail_gases')->withPivot('gas_id');
    }

    public function registers()
    {
      return $this->belongsToMany('App\Register','binnacles')->withPivot('gas_id','ppm','hour');
    }
}
