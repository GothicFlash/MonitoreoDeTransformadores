<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{
    public $timestamps = false;

    public function monitors()
    {
      return $this->belongsToMany('App\Monitor','detail_procedures')->withPivot('procedure_id');
    }
}
