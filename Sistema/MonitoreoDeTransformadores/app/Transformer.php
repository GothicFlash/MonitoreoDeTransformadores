<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class transformer extends Model
{
    public $timestamps = false;

    public function registers()
    {
      return $this->hasMany('App\Register');
    }

    public function monitors()
    {
      return $this->belongsToMany('App\Monitor','detail_transformers')->withPivot('transformer_id','created_at');
    }
}
