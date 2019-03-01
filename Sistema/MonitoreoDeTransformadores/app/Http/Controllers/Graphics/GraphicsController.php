<?php

namespace App\Http\Controllers\Graphics;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Monitor;
use App\Register;
use App\Gas;
use App\Notification;
use App\Transformer;
use Carbon\Carbon;
use Response;

//Este controlador corresponde a todas aquellas peticiones que son realizadas con AJAX

class GraphicsController extends Controller
{
    public function getActiveTransformers($model) //Obtiene los transformadores activos para poder enlazarlos con los monitores
    {                                             //Nota: Se valida que el transformador contenga los mismos gases con el que se
      $transformers_id = [];                      //      plantea cambiar, esto con el fin de respetar los gases que utiliza el
      $transformers = Transformer::get();         //      metodo del monitor.
      foreach ($transformers as $transformer) {
        foreach ($transformer->monitors as $monitor) {
          if($monitor->state == 1 && $this->validateChange($model,$monitor)){
            $transformers_id[] = $transformer->id;
            $names[] = $transformer->name;
          }
        }
      }
      $transformers_id = array_unique($transformers_id); //Elimina los transformadores que esten duplicados
      $data = compact('transformers_id','names');
      return json_encode($data);
    }

    public function getTransformers($idMonitor) //Obtiene todos los transformadores enlazados a un monitor
    {
      $monitor = Monitor::find($idMonitor);
      foreach ($monitor->transformers as $transformer) {
        $names[] = $transformer->name;
        $transformers[] = $transformer->id;
      }
      $data = compact('names','transformers');
      return json_encode($data);
    }

    public function validateChange($model, Monitor $monitor) //Valida que la cantidad de gases de un monitor corresponde a la cantidad de gases de un modelo en especifico
    {
      $count = 0;
      $gases = $this->getGasesString($model);
      foreach ($gases as $gas) {
        if($monitor->gases->where('name',$gas)->first() != NULL){
          $count++;
        }
      }
      return ($count==count($gases))?true:false;
    }

    public function getGasesString($model) //Retorna los gases correspondientes a un modelo de monitor en especifico
    {
      switch($model){
        case "CALISTO 1":
          $gases = array("H2","WC");
          break;
        case "CALISTO 2":
          $gases = array("H2","WC","CO");
          break;
        case "CALISTO 9":
          $gases = array("H2","CH4","C2H6","C2H4","C2H2","CO","CO2","O2","WC");
          break;
        case "MHT410":
          $gases = array("H2","WC","Temperatura");
          break;
        case "OPT100":
          $gases = array("H2","CH4","C2H6","C2H4","C2H2","CO","CO2","O2","WC");
          break;
      }
      return $gases;
    }

    function getLastMeasurements() //Obtiene las ultimas mediciones de un monitor
    { //Nota: Siempre se obtienen las ultimas mediciones del primer transformador ligado al transformador
      $monitors = Monitor::where('state',1)->get();
      foreach ($monitors as $monitor) {
        $dataString = "";
        if (count($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers) != 0){
          if (count($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->last()->gases) != 0){
            $dataString .= "Última fecha de medición: ".$monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->last()->date." ".$monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->last()->gases->last()->pivot->hour;
            $dataString .= "<table class='table table-bordered'>";
              $dataString .= "<thead>";
                $dataString .= "<tr>";
                  $dataString .= "<th>Gas</th>";
                  $dataString .= "<th>Valor</th>";
                $dataString .= "</tr>";
              $dataString .= "</thead>";
              $dataString .= "<tbody>";
                foreach ($monitor->gases as $gas){
                  $dataString .= "<tr>";
                    $dataString .= "<td>".$gas->name."</td>";
                    $dataString .= "<td>";
                      if ($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->last()->gases->where('name',$gas->name)->last() != NULL){
                        $dataString .= $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->last()->gases->where('name',$gas->name)->last()->pivot->ppm;
                      }else{
                        $dataString .= "Sin registros";
                      }
                    $dataString .= "</td>";
                  $dataString .= "</tr>";
                }
              $dataString .= "</tbody>";
            $dataString .= "</table>";
          }
        }else{
          $dataString .= "<h4 class='text-dark text-center'>Sin mediciones</h4>";
        }
        $information[] = $dataString;
        $monitorsId[] = $monitor->id;
      }
      $data = compact('information','monitorsId');
      return json_encode($data);
    }

    //--------------------------------------Inicio de cálculo de probabilidades---------------------------------------------------
    public function getProbabilityBy($idMonitor, $idTransformer) //Get the probability of the last three days prior to the current
    {
      $monitor = Monitor::find($idMonitor);
      $transformer = Transformer::find($idTransformer);
      $max = ($transformer->registers->count()>3)?3:1; //Se obtiene el cálculo de probabilidad mensual en base a 3 dias anteriores, siempre y cuando existan
      for($i=0;$i<$max;$i++){ //Limite como maximo 3 dias
        $probabilities[$i] = $this->getMonthProbability($monitor, $transformer, $i+1); //Obtiene la probabilidad mensual
        $dates[$i] = Carbon::now()->subDays($i+1)->format('Y-m-d'); //Se agrega la fecha en formato-> día, mes y año
      }
      $lastProbability = round($this->getMonthProbability($monitor, $transformer, 1)); //Calculo de la última probabilidad mensual
      $data = compact('probabilities','dates','lastProbability');
      return json_encode($data);
    }

    public function returnAVG(Monitor $monitor, $registerLast, $registerFirst) //Redirecciona al método de cálculo de probabilidad dependiendo el procedimiento que tenga ligado cada monitor
    {
      switch ($monitor->procedures->first()->id) { //Se obtiene el método ligado al monitor
        case 1:  //Referencia al método del CALISTO 1 y MHT410
          $h2AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"H2"):0;
          $wcAvgLast =  (count($registerLast)>0)?$this->calculateAVG($registerLast,"WC"):0;
          $h2AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"H2"):0;
          return $this->calculateProbabilityC1($wcAvgLast,$h2AvgLast,$h2AvgFirst);
          break;
        case 2: //Referencia al método del CALISTO 2
          $h2AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"H2"):0;
          $wcAvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"WC"):0;
          $coAvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"CO"):0;
          $h2AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"H2"):0;
          $coAvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"CO"):0;
          return $this->calculateProbabilityC2($wcAvgLast,$h2AvgLast,$coAvgLast,$h2AvgFirst,$coAvgFirst);
          break;
        case 3: //Referencia al método del CALISTO 9
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
          return $this->calculateProbabilityC9($h2AvgLast,$ch4AvgLast,$c2h6AvgLast,$c2h4AvgLast,$c2h2AvgLast,$coAvgLast,$co2AvgLast,$o2AvgLast,$wcAvgLast,$h2AvgFirst,$ch4AvgFirst,$c2h6AvgFirst,$c2h4AvgFirst,$c2h2AvgFirst,$coAvgFirst,$co2AvgFirst);
          break;
        case 4: //Referencia al método del OPT100
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
          return $this->calculateProbabilityOPT100($h2AvgLast,$ch4AvgLast,$c2h6AvgLast,$c2h4AvgLast,$c2h2AvgLast,$coAvgLast,$co2AvgLast,$o2AvgLast,$wcAvgLast,$h2AvgFirst,$ch4AvgFirst,$c2h6AvgFirst,$c2h4AvgFirst,$c2h2AvgFirst,$coAvgFirst,$co2AvgFirst);
          break;
      }
    }

    public function getMonthProbability(Monitor $monitor, Transformer $transformer, $days) //Obtiene la probabilidad por mes
    {
      $registersYesterday = $transformer->registers()->whereDate('date', '=', Carbon::now()->subDays($days+1)->format('Y-m-d'))->get();
      $registersLast30Days = $transformer->registers()->whereDate('date', '=', Carbon::now()->subDays(31+$days)->format('Y-m-d'))->get();

      $registersYesterday = ($registersYesterday->first()!=NULL)?$registersYesterday->first()->gases:array();
      $registersLast30Days = ($registersLast30Days->first()!=NULL)?$registersLast30Days->first()->gases:array();
      return $this->returnAVG($monitor,$registersYesterday,$registersLast30Days);;
    }

    public function getYearProbability($idMonitor, $idTransformer) //Obtiene la probabilidad por año
    {
      $monitor = Monitor::find($idMonitor);
      $transformer = Transformer::find($idTransformer);

      $registers = $transformer->registers()->whereYear('date', '=',date('Y'))->orderBy('date','asc')->get()->groupBy(function($val) {
        return Carbon::parse($val->date)->format('m');
      })->reverse();

      $collection = $registers->getIterator();

      $sum = 0;
      $count = 0; //Atributo para el indice del arreglo
      foreach ($collection as $register) {
        $next = next($collection);
        if($register->count() >= 20){ //Se valida que el registro contenga al menos 20 mediciones
          $sum += $this->returnAVG($monitor,$register->last()->gases,$register->first()->gases);
          $probabilities[$count] = $this->returnAVG($monitor,$register->last()->gases,$register->first()->gases);
          $dates[$count] = Carbon::parse($register->first()->date)->format('Y-m');
        }else{
          if($next != NULL && $next->count() >= 20){ //En caso de que el registro no contenga al menos 20 mediciones verifica que el siguiente los tenga para poder añadirlo
            $sum += $this->returnAVG($monitor,$next->last()->gases,$next->first()->gases);
            $probabilities[$count] = $this->returnAVG($monitor,$next->last()->gases,$next->first()->gases);
            $dates[$count] = Carbon::parse($register->first()->date)->format('Y-m');
          }else{ //En caso de que el siguiente no los tenga o no exista alguno agrega la probabilidad minima
            $sum += $this->returnAVG($monitor,array(),array());
            $probabilities[$count] = $this->returnAVG($monitor,array(),array());
            $dates[$count] = Carbon::parse($register->first()->date)->format('Y-m');
          }
        }
        $count+=1;
      }
      $lastProbability = round($sum/count($registers)); //Value of probability
      $data = compact('probabilities','dates','lastProbability');
      return json_encode($data);
    }

    public function getAllProbability($idMonitor, $idTransformer) //Obtiene las probabilidades de todos los años
    {
      $monitor = Monitor::find($idMonitor);
      $transformer = Transformer::find($idTransformer);

      $registers = $transformer->registers()->orderBy('date','asc')->get()->groupBy(function($val) {
        return Carbon::parse($val->date)->format('Y');
      })->reverse();

      $prom = 0;
      $count = 0;

      foreach ($registers as $register) {
        $registersByMonth = $register->groupBy(function($val) {
          return Carbon::parse($val->date)->format('m');
        })->reverse();

        $sum = 0;
        $collection = $registersByMonth->getIterator();

        $dates[$count] = Carbon::parse($registersByMonth->first()->first()->date)->format('Y');

        foreach ($collection as $register) {
          $next = next($collection);
          if ($register->count()>=20) {
            $sum += $this->returnAVG($monitor,$register->last()->gases,$register->first()->gases);
          } else {
            if ($next !=NULL && $next->count()>=20) {
              $sum += $this->returnAVG($monitor,$next->last()->gases,$next->first()->gases);
            } else {
              $sum += $this->returnAVG($monitor,array(),array());
            }
          }
        }
        $prom += $sum/count($registersByMonth);
        $probabilities[$count] = $sum/count($registersByMonth);
        $count+=1;
      }
      $lastProbability = round($prom/count($registers));
      $data = compact('probabilities','dates','lastProbability');
      return json_encode($data);
    }
    //--------------------------------------Fin del cálculo de probabilidades---------------------------------------------------

    //Esta función retorna la información compactada de cada monitor incluyendo los nombres de los gases, mediciones e información adicional
    public function getAverages($idMonitor, $idTransformer, $type)
    {
      $monitor = Monitor::find($idMonitor);
      $transformer = $monitor->transformers->where('id',$idTransformer)->first();
      $count = 0;
      foreach ($monitor->gases as $gas) {
        $information[$count] = $this->getInformationBy($idMonitor,$transformer->id,$gas->id,$type);
        $count++;
      }
      $gases = $this->getNamesGasesByMonitor($monitor);
      $data = compact('information','gases');
      return json_encode($data);
    }

    public function getInformationBy($idMonitor, $idTransformer, $idGas, $type) //Obtiene las mediciones de un monitor ya sea por mes, año o todos los años
    {
      switch($type){
        case "month":
          return json_decode($this->getGasesByMonth($idMonitor,$idTransformer,$idGas)); //Obtiene los gases por mes
          break;
        case "year";
          return json_decode($this->getGasesByYear($idMonitor,$idTransformer,$idGas)); //Obtiene los gases por año
          break;
        case "all":
          return json_decode($this->getGasesByAll($idMonitor,$idTransformer,$idGas)); //Obtiene los gases de todos los años
          break;
      }
    }
    //------------------------------------------

    public function getGasesByDay($idMonitor, $idTransformer, $gas) //Obtiene la información de los gases de un transformador por dia
    {
      $dates = NULL;
      $averages = NULL;
      $monitor = Monitor::find($idMonitor);
      $transformer = $monitor->transformers->where('id',$idTransformer)->first();
      $register = $transformer->registers()->whereDate('date', '=', Carbon::now()->subDays(1)->format('Y-m-d'))->get()->first();
      if ($register == NULL) { //Si no existe un registro actua, se obtiene el ultimo registro en la BD
        $register = $transformer->registers->last();
      }
      $count = 0;
      if ($register != NULL) {
        foreach ($register->gases->where('id','=',$gas) as $gas) { //Busca por un gas en especifico dentro de los registros
          $dates[$count] = $gas->pivot->hour;
          $averages[$count] = (int)$gas->pivot->ppm;
          $count++;
        }
      }
      $data = compact('averages','dates');
      return json_encode($data);
    }

    public function getGasesByMonth($idMonitor, $idTransformer, $gas) //Obtiene la información de los gases de un transformador por mes
    {
      $monitor = Monitor::find($idMonitor);
      $transformer = $monitor->transformers->where('id',$idTransformer)->first();

      $registers = $transformer->registers()->whereMonth('date', '=',date('m'))->whereYear('date', '=',date('Y'))->orderBy('date','asc')->get()->groupBy(function($val) {
        return Carbon::parse($val->date)->format('d');
      }); //Obtiene los registros de los transformadores agrupados por dia

      $count = 0;
      foreach ($registers as $register) {
        $gases = $register->last()->gases->where('id','=',$gas);
        $dates[$count] = Carbon::parse($register->first()->date)->format('jS \\of F');
        $averages[$count] = $this->getAverageByGas($gases); //Para cada registro del transformador se calcula el promedio de las mediciones que se tuvieron durante el dia
        $count++;
      }

      $data = compact('averages','dates');
      return json_encode($data);
    }

    public function getGasesByYear($idMonitor, $idTransformer, $gas) //Obtiene la informacion de los gases de un transformador por año
    {
      $monitor = Monitor::find($idMonitor);
      $transformer = $monitor->transformers->where('id',$idTransformer)->first();

      $registers = $transformer->registers()->whereYear('date', '=',date('Y'))->orderBy('date','asc')->get()->groupBy(function($val) {
        return Carbon::parse($val->date)->format('m');
      }); //Obtiene los registros del transformador agrupados por mes

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
      return json_encode($data);
    }

    public function getGasesByAll($idMonitor, $idTransformer, $gas) //Obtiene la informacion de los gases de un transformador por todos los años
    {
      $monitor = Monitor::find($idMonitor);
      $transformer = $monitor->transformers->where('id',$idTransformer)->first();

      $registers = $transformer->registers()->orderBy('date','asc')->get()->groupBy(function($val) {
        return Carbon::parse($val->date)->format('Y');
      }); //Obtiene los registros del transformador agrupados por año

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
      return json_encode($data);
    }

    public function getNamesGasesByMonitor(Monitor $monitor) //Obtiene los nombres de los gases de un monitor en especifico
    {
      foreach ($monitor->gases as $gas) {
        $names[] = $gas->name;
      }
      return $names;
    }

    public function getGasesByMonitor($idMonitor, $idTransformer) //Obtiene la información compactada de un monitor en especifico
    {
      $monitor = Monitor::find($idMonitor);
      $transformer = $monitor->transformers->where('id',$idTransformer)->first();
      $count = 0;
      foreach ($monitor->gases as $gas) {
        $gases[$count] = $gas->id;
        $information[$count] = json_decode($this->getGasesByDay($idMonitor,$idTransformer,$gas->id));
        $count++;
      }
      $names = $this->getNamesGasesByMonitor($monitor);
      $data = compact('gases','names','information');
      return json_encode($data);
    }

    public function getAverageByGas($gases) //Obtiene el promedio de las mediciones de un gas en especifico
    {
      $sum = 0;
      foreach ($gases as $gas) {
        $sum+=$gas->pivot->ppm;
      }

      return $sum/count($gases);
    }

    public function getProbabilities(){ //Obtiene todas las probabilidades por mes de todos los monitores
      $monitors = Monitor::where('state',1)->get();
      $count = 0;
      foreach ($monitors as $monitor) {
        if(count($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers) != 0){
          $probabilities[$count] = $this->getMonthProbability($monitor,$monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first(),1);
        }else{
          $probabilities[$count] = -1; //Assign -1 if the monitor does not have records in the database
        }
        $monitors[$count] = $monitor->id;
        $monitors_models[$count] = $monitor->model;
        $count++;
      }
      $data = compact('monitors','probabilities','monitors_models');
      return json_encode($data);
    }

    public function checkProbabilities() //Esta función sirve para verificar si la probabilidad de un monitor excedio un limite, si es así enviara una notificación
    {
      date_default_timezone_set('America/Mexico_City');
      $monitors = Monitor::where('state',1)->get();
      foreach ($monitors as $monitor) {
        if(count($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers) != 0){
          if($this->getMonthProbability($monitor,$monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first(),1) >= 47){
            $description = "El Transformador ".$monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->name." del monitor ".$monitor->model." con nodo ".$monitor->node." presenta una probabilidad fuera de lo normal";
            $notifications = Notification::where('date',Carbon::now()->format('Y-m-d'))->where('description',$description)->get();
            if($notifications->isEmpty()){
              $notification = new Notification;
              $notification->date = Carbon::now()->format('Y-m-d');
              $notification->hour = Carbon::now()->format('H:m:s');
              $notification->description = $description;
              $notification->save();
            }
          }
        }
      }
      $notifications = Notification::where('date',Carbon::now()->format('Y-m-d'))->orderBy('id', 'DESC')->get();
      return json_encode($notifications);
    }

    public function calculateAVG($registers, $gas) //Obtiene el promedio de un gas dentro de cierta cantidad de registros
    {
        $prom = 0;
        $sum = 0;
        $count = $registers->where('name','=',$gas)->count(); //Obtiene la cantidad de mediciones de gases que se tienen en el registro
        if ($count!=NULL) {
          $registersByGas = $registers->where('name', $gas);
          foreach ($registersByGas as $valueGas) {
            $sum+= $valueGas->pivot->ppm;
          }
          $prom = $sum/$count;
        }
        return $prom;
    }

    public function calculateProbabilityC1($wcAvgYesterday, $h2AvgYesterday, $h2AvgLast30Days) //Método para el cálculo de probabilidad del CALISTO 1 y MHT410
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

    public function calculateProbabilityC2($wcAvgYesterday, $h2AvgYesterday, $coAvgYesterday, $h2AvgLast30Days, $coAvgLast30Days) //Método para el cálculo de probabilidad del CALISTO 2
    {
      $probability = 0;
      $h2Month = $h2AvgYesterday - $h2AvgLast30Days;
      $coMonth = $coAvgYesterday - $coAvgLast30Days;

      if ($h2AvgYesterday < 101) { //H2
         $probability += 3;
      } else if ($h2AvgYesterday > 101 && $h2AvgYesterday < 1000) {
         $probability += 8;
      } else if ($h2AvgYesterday > 1000 && $h2AvgYesterday < 2000) {
         $probability += 14;
      } else if ($h2AvgYesterday > 2000) {
         $probability += 22;
      }

      if ($coAvgYesterday < 351) { //CO
         $probability += 2;
      } else if ($coAvgYesterday > 350 && $coAvgYesterday < 571) {
         $probability += 5;
      } else if ($coAvgYesterday > 570 && $coAvgYesterday < 1401) {
         $probability += 10;
      } else if ($coAvgYesterday > 1400) {
         $probability += 16;
      }

      if ($wcAvgYesterday < 6) { //WC
         $probability += 3;
      } else if ($wcAvgYesterday > 5 && $wcAvgYesterday < 9) {
         $probability += 8;
      } else if ($wcAvgYesterday > 8 && $wcAvgYesterday < 13) {
         $probability += 14;
      } else if ($wcAvgYesterday > 12) {
         $probability += 22;
      }

      if ($h2Month < 101) { //H2 Month generation
         $probability += 3;
      } else if ($h2Month > 101 && $h2Month < 1000) {
         $probability += 8;
      } else if ($h2Month > 1000 && $h2Month < 2000) {
         $probability += 14;
      } else if ($h2Month > 2000) {
         $probability += 22;
      }

      if ($coMonth < 351) { //CO Month generation
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

    public function calculateProbabilityC9($h2AvgYesterday, $ch4AvgYesterday, $c2h6AvgYesterday, $c2h4AvgYesterday, $c2h2AvgYesterday, $coAvgYesterday, $co2AvgYesterday, $o2AvgYesterday, $wcAvgYesterday, $h2AvgLast30Days, $ch4AvgLast30Days, $c2h6AvgLast30Days, $c2h4AvgLast30Days, $c2h2AvgLast30Days, $coAvgLast30Days, $co2AvgLast30Days)
    { //Método para el cálculo de probabilidad del CALISTO 9
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
        $probability += 1;
      } elseif ($h2AvgYesterday > 1000 && $h2AvgYesterday < 2000) {
        $probability += 1;
      } elseif ($h2AvgYesterday > 2000) {
        $probability += 1;
      }

      if ($h2Month < 10) {
        $probability += 1;
      } elseif ($h2Month > 9 && $h2Month < 30) {
        $probability += 1;
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
        $probability += 1;
      } elseif ($c2h2AvgYesterday > 35 && $c2h2AvgYesterday < 51) {
        $probability += 2;
      } elseif ($c2h2AvgYesterday > 50 && $c2h2AvgYesterday < 81) {
        $probability += 2;
      } elseif ($c2h2AvgYesterday > 80) {
        $probability += 3;
      }

      if ($c2h2Month < 0.5) {
        $probability += 1;
      } elseif ($c2h2Month > 0.4 && $c2h2Month < 1.5) {
        $probability += 2;
      } elseif ($c2h2Month > 1.49 && $c2h2Month < 2.5) {
        $probability += 2;
      } elseif ($c2h2Month > 2.49) {
        $probability += 3;
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

    public function calculateProbabilityOPT100($h2AvgYesterday, $ch4AvgYesterday, $c2h6AvgYesterday, $c2h4AvgYesterday, $c2h2AvgYesterday, $coAvgYesterday, $co2AvgYesterday, $o2AvgYesterday, $wcAvgYesterday, $h2AvgLast30Days, $ch4AvgLast30Days, $c2h6AvgLast30Days, $c2h4AvgLast30Days, $c2h2AvgLast30Days, $coAvgLast30Days, $co2AvgLast30Days)
    { //Método para el cálculo de probabilidad del OPT100
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
        $probability += 2;
      } elseif ($h2AvgYesterday > 1000 && $h2AvgYesterday < 2000) {
        $probability += 2;
      } elseif ($h2AvgYesterday > 2000) {
        $probability += 2;
      }

      if ($h2Month < 10) {
        $probability += 1;
      } elseif ($h2Month > 9 && $h2Month < 30) {
        $probability += 2;
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
        $probability += 2;
      } elseif ($c2h2AvgYesterday > 35 && $c2h2AvgYesterday < 51) {
        $probability += 3;
      } elseif ($c2h2AvgYesterday > 50 && $c2h2AvgYesterday < 81) {
        $probability += 3;
      } elseif ($c2h2AvgYesterday > 80) {
        $probability += 4;
      }

      if ($c2h2Month < 0.5) {
        $probability += 1;
      } elseif ($c2h2Month > 0.4 && $c2h2Month < 1.5) {
        $probability += 2;
      } elseif ($c2h2Month > 1.49 && $c2h2Month < 2.5) {
        $probability += 2;
      } elseif ($c2h2Month > 2.49) {
        $probability += 4;
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
