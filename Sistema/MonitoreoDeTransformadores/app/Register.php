<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
  public $timestamps = false;

  public function gases()
  {
    return $this->belongsToMany('App\Gas','binnacles')->withPivot('register_id','ppm','hour');
  }

  public function transformer()
  {
    return $this->belongsTo('App\Transformer');
  }
}
