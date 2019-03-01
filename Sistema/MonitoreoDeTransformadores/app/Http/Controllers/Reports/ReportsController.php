<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Monitor;
use App\Transformer;
use Carbon\Carbon;
use PDF;
use Auth;

class ReportsController extends Controller
{
    public function __construct() //Establece el tiempo actual de acuerdo a la ciudad de méxico
    {
      date_default_timezone_set('America/Mexico_City');
    }

    public function index()
    {
      $monitors = Monitor::get();
      return view($this->redirectTo().'.Reports.index',compact('monitors'));
    }

    public function redirectTo() //Redirección dependiendo el tipo de usuario
    {
      $type = '';
      switch(Auth::user()->type){
        case 'admin':
          $type = 'admin-user';
          break;
        case 'user':
          $type = 'default-user';
          break;
        case 'config':
          $type = 'config-user';
          break;
      }
      return $type;
    }

    public function generatePDF(Request $request) //Función para descargar el PDF
    {
      $monitor = Monitor::find($request->monitors);
      $transformer = $monitor->transformers->where('id',$request->transformers)->first();
      $probabilityByMonth = round($this->getProbabilityByMonth($monitor,$transformer));
      $probabilityByYear = round($this->getProbabilityByYear($monitor,$transformer));
      $probabilityByAll = round($this->getProbabilityByAll($monitor,$transformer));

      $dayAverages = $this->getAverages($monitor->id,$transformer->id,"day");
      $monthAverages = $this->getAverages($monitor->id,$transformer->id,"month");
      $yearAverages = $this->getAverages($monitor->id,$transformer->id,"year");
      $allAverages = $this->getAverages($monitor->id,$transformer->id,"all");

      ini_set('max_execution_time', 300);
      $pdf = PDF::loadView($this->redirectTo().'.Reports.template',compact('monitor','probabilityByMonth','probabilityByYear','probabilityByAll','transformer','dayAverages','monthAverages','yearAverages','allAverages'));
      return $pdf->download($monitor->model."-Nodo ".$monitor->node."-".$transformer->name.'-Report-'.date('Y-m-d_H:i:s').'.pdf');
    }

    ////Esta función retorna la información compactada de cada monitor incluyendo los nombres de los gases, mediciones e información adicional
    public function getAverages($idMonitor, $idTransformer, $type)
    {
      $monitor = Monitor::find($idMonitor);
      $transformer = $monitor->transformers->where('id',$idTransformer)->first();
      $count = 0;
      foreach ($monitor->gases as $gas) {
        if(count($transformer->registers->last()->gases->where('id','=',$gas->id))>0){ //Verify if the registers have enough information to make the array
          $information[$count] = $this->getInformationBy($idMonitor,$transformer->id,$gas->id,$type);
          $gases[$count] = $gas->name;
        }
        $count++;
      }
      $data = compact('information','gases');
      return $data;
    }

    public function getInformationBy($idMonitor, $idTransformer, $idGas, $type) //Obtiene las mediciones de un monitor ya sea por mes, año o todos los años
    {
      switch($type){
        case 'day':
          return $this->getGasesByDay($idMonitor,$idTransformer,$idGas);
          break;
        case "month":
          return $this->getGasesByMonth($idMonitor,$idTransformer,$idGas);
          break;
        case "year";
          return $this->getGasesByYear($idMonitor,$idTransformer,$idGas);
          break;
        case "all":
          return $this->getGasesByAll($idMonitor,$idTransformer,$idGas);
          break;
      }
    }

    public function getGasesByDay($idMonitor, $idTransformer, $gas) //Obtiene la información de los gases de un transformador por dia
    {
      $dates = NULL;
      $averages = NULL;
      $monitor = Monitor::find($idMonitor);
      $transformer = $monitor->transformers->where('id',$idTransformer)->first();
      $register = $transformer->registers()->whereDate('date', '=', Carbon::now()->subDays(1)->format('Y-m-d'))->get()->first();
      if($register == NULL){
        $register = $transformer->registers->last();
      }
      $count = 0;
      if($register != NULL){
        foreach ($register->gases->where('id','=',$gas) as $gas) {
          $dates[$count] = Carbon::parse($register->date)->format('d-M-Y')." (".$gas->pivot->hour.")";
          $averages[$count] = (int)$gas->pivot->ppm;
          $count++;
        }
      }
      $data = compact('averages','dates');
      return $data;
    }

    public function getGasesByMonth($idMonitor, $idTransformer, $gas){ //Obtiene la información de los gases de un transformador por mes
      $monitor = Monitor::find($idMonitor);
      $transformer = $monitor->transformers->where('id',$idTransformer)->first();

      $registers = $transformer->registers()->whereMonth('date', '=',date('m'))->whereYear('date', '=',date('Y'))->orderBy('date','asc')->get()->groupBy(function($val) {
        return Carbon::parse($val->date)->format('d');
      });

      $count = 0;
      foreach ($registers as $register) {
        $gases = $register->last()->gases->where('id','=',$gas);
        $dates[$count] = Carbon::parse($register->first()->date)->format('jS \\of F Y');
        $averages[$count] = $this->getAverageByGas($gases);
        $count++;
      }
      $data = compact('averages','dates');
      return $data;
    }

    public function getGasesByYear($idMonitor, $idTransformer, $gas){ //Obtiene la informacion de los gases de un transformador por año
      $monitor = Monitor::find($idMonitor);
      $transformer = $monitor->transformers->where('id',$idTransformer)->first();

      $registers = $transformer->registers()->whereYear('date', '=',date('Y'))->orderBy('date','asc')->get()->groupBy(function($val) {
        return Carbon::parse($val->date)->format('m');
      });

      $count = 0;
      foreach ($registers as $register) {
        $sum = 0;
        foreach ($register as $value) {
          $gases = $value->gases->where('id','=',$gas);
          $sum += $this->getAverageByGas($gases);
        }
        $dates[$count] = Carbon::parse($register->first()->date)->format('Y F');
        $averages[$count] = $sum/count($register);
        $count++;
      }
      $data = compact('averages','dates');
      return $data;
    }

    public function getGasesByAll($idMonitor, $idTransformer, $gas){ //Obtiene la informacion de los gases de un transformador por todos los años
      $monitor = Monitor::find($idMonitor);
      $transformer = $monitor->transformers->where('id',$idTransformer)->first();

      $registers = $transformer->registers()->orderBy('date','asc')->get()->groupBy(function($val) {
        return Carbon::parse($val->date)->format('Y');
      });
      $count = 0;
      foreach ($registers as $register) {
        $sum = 0;
        foreach ($register as $value) {
          $gases = $value->gases->where('id','=',$gas);
          $sum += $this->getAverageByGas($gases);
        }
        $dates[$count] = Carbon::parse($register->first()->date)->format('Y');
        $averages[$count] = $sum/count($register);
        $count++;
      }
      $data = compact('averages','dates');
      return $data;
    }

    public function getAverageByGas($gases) //Obtiene el promedio de las mediciones de un gas en especifico
    {
      $sum = 0;
      foreach ($gases as $gas) {
        $sum+=$gas->pivot->ppm;
      }
      if(count($gases)>0){
        return $sum/count($gases);
      }
      return $sum;
    }

    public function getProbabilityByMonth(Monitor $monitor, Transformer $transformer) //Obtiene la probabilidad por mes
    {
      $registersYesterday = $transformer->registers()->whereDate('date', '=', Carbon::now()->subDays(2)->format('Y-m-d'))->get();
      $registersLast30Days = $transformer->registers()->whereDate('date', '=', Carbon::now()->subDays(32)->format('Y-m-d'))->get();

      $registersYesterday = ($registersYesterday->first()!=NULL)?$registersYesterday->first()->gases:array();
      $registersLast30Days = ($registersLast30Days->first()!=NULL)?$registersLast30Days->first()->gases:array();
      return $this->returnAVG($monitor,$registersYesterday,$registersLast30Days);
    }

    public function getProbabilityByYear(Monitor $monitor, Transformer $transformer) //Obtiene la probabilidad por año
    {
      $registers = $transformer->registers()->whereYear('date', '=',date('Y'))->orderBy('date','asc')->get()->groupBy(function($val) {
        return Carbon::parse($val->date)->format('m');
      })->reverse();

      $sum = 0;
      $collection = $registers->getIterator();

      foreach ($collection as $register) {
        $next = next($collection);

        if($register->count()>=20){
          $sum += $this->returnAVG($monitor,$register->last()->gases,$register->first()->gases);
        }else{
          if($next !=NULL && $next->count()>=20){
            $sum+= $this->returnAVG($monitor,$next->last()->gases,$next->first()->gases);
          }else{
            $sum += $this->returnAVG($monitor,array(),array());
          }
        }
      }

      return $sum/count($registers);
    }

    public function getProbabilityByAll(Monitor $monitor, Transformer $transformer) //Obtiene las probabilidades de todos los años
    {
      $registers = $transformer->registers()->orderBy('date','asc')->get()->groupBy(function($val) {
        return Carbon::parse($val->date)->format('Y');
      })->reverse();

      $prom = 0;

      foreach ($registers as $register) {
        $registersByMonth = $register->groupBy(function($val) {
          return Carbon::parse($val->date)->format('m');
        })->reverse();

        $sum = 0;
        $collection = $registersByMonth->getIterator();

        foreach ($collection as $register) {
          $next = next($collection);
          if($register->count()>=20){
            $sum += $this->returnAVG($monitor,$register->last()->gases,$register->first()->gases);
          }else{
            if($next !=NULL && $next->count()>=20){
              $sum += $this->returnAVG($monitor,$next->last()->gases,$next->first()->gases);
            }else{
              $sum += $this->returnAVG($monitor,array(),array());
            }
          }
        }
        $prom += $sum/count($registersByMonth);
      }
      return $prom/count($registers);
    }

    public function returnAVG(Monitor $monitor, $registerLast, $registerFirst) //Redirecciona al método de cálculo de probabilidad dependiendo el procedimiento que tenga ligado cada monitor
    {
      switch ($monitor->procedures->last()->id) {
        case 1:
          $h2AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"H2"):0;
          $wcAvgLast =  (count($registerLast)>0)?$this->calculateAVG($registerLast,"WC"):0;
          $h2AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"H2"):0;
          return $this->calculateProbabilityC1($wcAvgLast,$h2AvgLast,$h2AvgFirst);
          break;
        case 2:
          $h2AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"H2"):0;
          $wcAvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"WC"):0;
          $coAvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"CO"):0;
          $h2AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"H2"):0;
          $coAvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"CO"):0;
          return $this->calculateProbabilityC2($wcAvgLast,$h2AvgLast,$coAvgLast,$h2AvgFirst,$coAvgFirst);
          break;
        case 3:
          $h2AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"H2"):0;
          $ch4AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"CH4"):0;
          $c2h6AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"C2H6"):0;
          $c2h4AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"C2H4"):0;
          $c2h2AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"C2H2"):0;
          $coAvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"CO"):0;
          $co2AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"CO2"):0;
          $o2AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"O2"):0;
          $wcAvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"WC"):0;
          $h2AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"H2"):0;
          $ch4AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"CH4"):0;
          $c2h6AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"C2H6"):0;
          $c2h4AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"C2H4"):0;
          $c2h2AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"C2H2"):0;
          $coAvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"CO"):0;
          $co2AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"CO2"):0;
          return $this->calculateProbabilityC9_OPT100($monitor,$h2AvgLast,$ch4AvgLast,$c2h6AvgLast,$c2h4AvgLast,$c2h2AvgLast,$coAvgLast,$co2AvgLast,$o2AvgLast,$wcAvgLast,$h2AvgFirst,$ch4AvgFirst,$c2h6AvgFirst,$c2h4AvgFirst,$c2h2AvgFirst,$coAvgFirst,$co2AvgFirst);
          break;
      }
    }

    public function calculateAVG($registers, $gas) //Obtiene el promedio de un gas dentro de cierta cantidad de registros
    {
        $prom = 0;
        $sum = 0;
        $count = $registers->where('name','=',$gas)->count();
        if ($count!=NULL) {
          $registersByGas = $registers->where('name', $gas);
          foreach ($registersByGas as $valueGas) {
            $sum+= $valueGas->pivot->ppm;
          }
          $prom = $sum/$count;
        }
        return $prom;
    }

    public function calculateProbabilityC1($wcAvgYesterday, $h2AvgYesterday, $h2AvgLast30Days) //Metodo para el cálculo de probabilidad del CALISTO 1 y MHT410
    {
      $probability = 0;
      $h2Month = $h2AvgYesterday - $h2AvgLast30Days;

      if ($h2AvgYesterday < 101) { //H2
          $probability += 5;
      } else if ($h2AvgYesterday > 101 && $h2AvgYesterday < 1000) {
          $probability += 12;
      } else if ($h2AvgYesterday > 1000 && $h2AvgYesterday < 2000) {
          $probability += 21;
      } else if ($h2AvgYesterday > 2000) {
          $probability += 33;
      }

      if ($wcAvgYesterday < 6) {//WC
          $probability += 5;
      } else if ($wcAvgYesterday > 5 && $wcAvgYesterday < 9) {
          $probability += 12;
      } else if ($wcAvgYesterday > 8 && $wcAvgYesterday < 13) {
          $probability += 21;
      } else if ($wcAvgYesterday > 12) {
          $probability += 33;
      }

      if ($h2Month < 10 ) {//H2 Month
          $probability += 5;
      } else if ($h2Month > 9 && $h2Month < 30) {
          $probability += 12;
      } else if ($h2Month > 29 && $h2Month < 50) {
          $probability += 21;
      } else if ($h2Month > 49) {
          $probability +=33;
      }

      return $probability;
    }

    public function calculateProbabilityC2($wcAvgYesterday, $h2AvgYesterday, $coAvgYesterday, $h2AvgLast30Days, $coAvgLast30Days) //Metodo para el cálculo de probabilidad del CALISTO 2
    {
      $probability = 0;
      $h2Month = $h2AvgYesterday - $h2AvgLast30Days;
      $coMonth = $coAvgYesterday - $coAvgLast30Days;

      if ($h2AvgYesterday < 101) { //H2 promedio
         $probability += 3;
      } else if ($h2AvgYesterday > 101 && $h2AvgYesterday < 1000) {
         $probability += 8;
      } else if ($h2AvgYesterday > 1000 && $h2AvgYesterday < 2000) {
         $probability += 14;
      } else if ($h2AvgYesterday > 2000) {
         $probability += 22;
      }

      if ($coAvgYesterday < 351) {//CO promedio
         $probability += 2;
      } else if ($coAvgYesterday > 350 && $coAvgYesterday < 571) {
         $probability += 5;
      } else if ($coAvgYesterday > 570 && $coAvgYesterday < 1401) {
         $probability += 10;
      } else if ($coAvgYesterday > 1400) {
         $probability += 16;
      }

      if ($wcAvgYesterday < 6) {//WC promedio
         $probability += 3;
      } else if ($wcAvgYesterday > 5 && $wcAvgYesterday < 9) {
         $probability += 8;
      } else if ($wcAvgYesterday > 8 && $wcAvgYesterday < 13) {
         $probability += 14;
      } else if ($wcAvgYesterday > 12) {
         $probability += 22;
      }

      if ($h2Month < 101) {//Generación por mes de H2
         $probability += 3;
      } else if ($h2Month > 101 && $h2Month < 1000) {
         $probability += 8;
      } else if ($h2Month > 1000 && $h2Month < 2000) {
         $probability += 14;
      } else if ($h2Month > 2000) {
         $probability += 22;
      }

      if ($coMonth < 351) {//Generación por mes de CO
         $probability += 2;
      } else if ($coMonth > 350 && $coMonth < 571) {
         $probability += 5;
      } else if ($coMonth > 570 && $coMonth < 1401) {
         $probability += 10;
      } else if ($coMonth > 1400) {
         $probability += 16;
      }

      return $probability;
    }

    public function calculateProbabilityC9_OPT100(Monitor $monitor, $h2AvgYesterday, $ch4AvgYesterday, $c2h6AvgYesterday, $c2h4AvgYesterday, $c2h2AvgYesterday, $coAvgYesterday, $co2AvgYesterday, $o2AvgYesterday, $wcAvgYesterday, $h2AvgLast30Days, $ch4AvgLast30Days, $c2h6AvgLast30Days, $c2h4AvgLast30Days, $c2h2AvgLast30Days, $coAvgLast30Days, $co2AvgLast30Days)
    { //Método para el cálculo de probabilidad del CALISTO 9 y OPT100
      $probability = 0;
      $h2Month = $h2AvgYesterday - $h2AvgLast30Days;
      $ch4Month = $ch4AvgYesterday - $ch4AvgLast30Days;
      $c2h6Month = $c2h6AvgYesterday - $c2h6AvgLast30Days;
      $c2h4Month = $c2h4AvgYesterday - $c2h4AvgLast30Days;
      $c2h2Month = $c2h2AvgYesterday - $c2h2AvgLast30Days;
      $coMonth = $coAvgYesterday - $coAvgLast30Days;
      $co2Month = $co2AvgYesterday - $co2AvgLast30Days;
      $co2_co = 0;
      $total_gas = $h2AvgYesterday + $ch4AvgYesterday + $c2h6AvgYesterday + $c2h4AvgYesterday + $c2h2AvgYesterday + $coAvgYesterday;

      if($co2AvgYesterday != 0 && $coAvgYesterday != 0) {
        $co2_co = $co2AvgYesterday / $coAvgYesterday;
      }

      if ($h2AvgYesterday < 101) {
        $probability += 1;
      } elseif ($h2AvgYesterday > 100 && $h2AvgYesterday < 1000) {
        $probability += ($monitor->model=="CALISTO 9")?1:2;
      } elseif ($h2AvgYesterday > 1000 && $h2AvgYesterday < 2000) {
        $probability += ($monitor->model=="CALISTO 9")?1:2;
      } elseif ($h2AvgYesterday > 2000) {
        $probability += ($monitor->model=="CALISTO 9")?1:2;
      }

      if ($h2Month < 10) {
        $probability += 1;
      } elseif ($h2Month > 9 && $h2Month < 30) {
        $probability += ($monitor->model=="CALISTO 9")?1:2;
      } elseif ($h2Month > 29 && $h2Month < 50) {
        $probability += 2;
      } elseif ($h2Month > 49) {
        $probability += 2;
      }

      if ($ch4AvgYesterday > 120 && $ch4AvgYesterday < 401) {
        $probability += 1;
      } elseif ($ch4AvgYesterday > 400 && $ch4AvgYesterday < 1001) {
        $probability += 1;
      } elseif ($ch4AvgYesterday > 1000) {
        $probability += 2;
      }

      if ($ch4Month > 7 && $ch4Month < 23) {
        $probability += 1;
      } elseif ($ch4Month > 22 && $ch4Month < 38) {
        $probability += 1;
      } elseif ($ch4Month > 37) {
        $probability += 2;
      }

      if ($c2h6AvgYesterday > 64 && $c2h6AvgYesterday < 101) {
        $probability += 1;
      } elseif ($c2h6AvgYesterday > 100 && $c2h6AvgYesterday < 151) {
        $probability += 1;
      } elseif ($c2h6AvgYesterday > 150) {
        $probability += 2;
      }

      if ($c2h6Month > 7 && $c2h6Month < 23) {
        $probability += 1;
      } elseif ($c2h6Month > 22 && $c2h6Month < 38) {
        $probability += 1;
      } elseif ($c2h6Month > 37) {
        $probability += 2;
      }

      if ($c2h4AvgYesterday > 50 && $c2h4AvgYesterday < 101) {
        $probability += 1;
      } elseif ($c2h4AvgYesterday > 100 && $c2h4AvgYesterday < 201) {
        $probability += 1;
      } elseif ($c2h4AvgYesterday > 200) {
        $probability += 2;
      }

      if ($c2h4Month > 7 && $c2h4Month < 23) {
        $probability += 1;
      } elseif ($c2h4Month > 22 && $c2h4Month < 38) {
        $probability += 1;
      } elseif ($c2h4Month > 37) {
        $probability += 2;
      }

      if ($c2h2AvgYesterday < 36) {
        $probability += ($monitor->model=="CALISTO 9")?1:2;
      } elseif ($c2h2AvgYesterday > 35 && $c2h2AvgYesterday < 51) {
        $probability += ($monitor->model=="CALISTO 9")?2:3;
      } elseif ($c2h2AvgYesterday > 50 && $c2h2AvgYesterday < 81) {
        $probability += ($monitor->model=="CALISTO 9")?2:3;
      } elseif ($c2h2AvgYesterday > 80) {
        $probability += ($monitor->model=="CALISTO 9")?3:4;
      }

      if ($c2h2Month < 0.5) {
        $probability += 1;
      } elseif ($c2h2Month > 0.4 && $c2h2Month < 1.5) {
        $probability += 2;
      } elseif ($c2h2Month > 1.49 && $c2h2Month < 2.5) {
        $probability += 2;
      } elseif ($c2h2Month > 2.49) {
        $probability += ($monitor->model=="CALISTO 9")?3:4;
      }

      if ($coAvgYesterday > 350 && $coAvgYesterday < 571) {
        $probability += 1;
      } elseif ($coAvgYesterday > 570 && $coAvgYesterday < 1401) {
        $probability += 1;
      } elseif ($coAvgYesterday > 1400) {
        $probability += 2;
      }

      if ($coMonth > 69 && $coMonth < 220) {
        $probability += 1;
      } elseif ($coMonth > 219 && $coMonth < 350) {
        $probability += 1;
      } elseif ($coMonth > 349) {
        $probability += 2;
      }

      if ($co2AvgYesterday > 2500 && $co2AvgYesterday < 4001) {
        $probability += 1;
      } elseif ($co2AvgYesterday > 4000 && $co2AvgYesterday < 10001) {
        $probability += 1;
      } elseif ($co2AvgYesterday > 10000) {
        $probability += 2;
      }

      if ($co2Month > 699 && $co2Month < 2100) {
        $probability += 1;
      } elseif ($co2Month > 2099 && $co2Month < 3500) {
        $probability += 1;
      } elseif ($co2Month > 3499) {
        $probability += 2;
      }

      if ($co2_co < 10.1 && $co2_co > 6) {
        $probability += 1;
      } elseif ($co2_co < 6.1 && $co2_co > 3.9) {
        $probability += 1;
      } elseif ($co2_co < 4) {
        $probability += 2;
      }

      if ($o2AvgYesterday > 3500 && $o2AvgYesterday < 7001) {
        $probability += 1;
      } elseif ($o2AvgYesterday > 7000 && $o2AvgYesterday < 10001) {
        $probability += 1;
      } elseif ($o2AvgYesterday > 10000) {
        $probability += 2;
      }

      if ($total_gas > 720 && $total_gas < 1921) {
        $probability += 1;
      } elseif ($total_gas > 1920 && $total_gas < 4631) {
        $probability += 1;
      } elseif ($total_gas > 4631) {
        $probability += 2;
      }

      if ($wcAvgYesterday < 6) {
        $probability += 1;
      } elseif ($wcAvgYesterday > 5 && $wcAvgYesterday < 9) {
        $probability += 1;
      } elseif ($wcAvgYesterday > 8 && $wcAvgYesterday < 13) {
        $probability += 2;
      } elseif ($wcAvgYesterday > 12) {
        $probability += 2;
      }

      return $probability;
    }
}
