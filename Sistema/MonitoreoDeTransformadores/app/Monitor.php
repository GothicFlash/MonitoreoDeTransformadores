<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Monitor extends Model
{
  public $timestamps = false;

  public function image()
  {
    return $this->hasOne('App\Image');
  }

  public function gases()
  {
    return $this->belongsToMany('App\Gas','detail_gases')->withPivot('monitor_id');
  }

  public function transformers(){
    return $this->belongsToMany('App\Transformer','detail_transformers')->withPivot('monitor_id','created_at');
  }

  public function procedures(){
    return $this->belongsToMany('App\Procedure','detail_procedures')->withPivot('monitor_id');
  }
}
