<?php

namespace App\Http\Controllers\Probabilities;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Monitor;
use App\Gas;
use Carbon\Carbon;
use Auth;

class ProbabilitiesController extends Controller
{
    public function index() //Muestra la pagina principal para probabilidades con registros externos
    {
      return view($this->redirectTo().'.Probabilities.index');
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

    public function store(Request $request) //Función para crear un nuevo calculo
    {
      $model = $request->model; //Obtiene el modelo de monitor seleccionado
      $gases = implode(", ",$this->getGasesString($model)); //Retorna los gases del monitor para ser mostrados en la vista
      $url = $request->file; //Obtiene la url del archivo seleccioando
      if(\File::exists($url)){ //Veriica que el archivo exista
        switch ($this->validateInsertion($url,$model)) { //Verifica que el archivo tenga el formato adecuado
          case -1:
            alert()->error('Error al leer el archivo', 'Oops!');
            break;
          case 0:
            alert()->error('Archivo invalido', 'Oops!');
            break;
          case 1:
            alert()->success('Generando grafica', 'Obteniendo datos')->autoclose(2000);
            $monthProbability = round($this->getMonthProbability($url,$model));
            $yearProbability = json_encode($this->getYearProbability($url,$model));
            $allProbability = json_encode($this->getAllProbability($url,$model));
            $dataMonth = $this->getDataMonth($url);
            $dataYear =  json_encode($this->getDataYear($url));
            $dataAll = json_encode($this->getDataAll($url));
            return view($this->redirectTo().'.Probabilities.show',compact('model','gases','monthProbability','dataMonth','yearProbability','dataYear','allProbability','dataAll'));
            break;
        }
      }else{
        alert()->error('El archivo no existe o ha sido eliminado', 'Oops!');
      }
      return redirect()->back();
    }

    public function getDataMonth($url) //Retorna la información (fechas) mensuales
    {
      $registers = $this->getRegistersFromFile($url,"year");
      return end($registers)[count(end($registers))-1]["date"];
    }

    public function getDataYear($url) //Retorna la información (fechas) anuales
    {
      $dates = [];
      $registersByYear = $this->getRegistersFromFile($url,"year");
      $index = (int)date('Y');
      if (array_key_exists($index, $registersByYear)) {
        $registersByYear = $registersByYear[$index];
        foreach ($registersByYear as $value) {
          $dates[] = date('Y-m',strtotime($value["date"]));
        }
      }
      return array_values(array_unique($dates));
    }

    public function getDataAll($url) //Retorna la información (fechas) de todos los años
    {
      $registersByMonth = $this->getRegistersFromFile($url,"year");
      return array_keys($registersByMonth);
    }

    public function getMonthProbability($url, $model) //Obtiene la probabilidad por mes
    {
      $registersByMonth = $this->getRegistersFromFile($url,"month");
      $registers = $this->group_by("date",end($registersByMonth),"day");
      $registerLast = (sizeof($registers)>29)?array_values($registers)[29]:array();
      $registerFirst = array_values($registers)[0];
      return $this->returnAVG($model,$registerLast,$registerFirst);
    }

    public function getYearProbability($url, $model) //Obtiene la probabilidad por año
    {
      $registersByMonth = array_reverse($this->getRegistersFromFile($url,"month"));
      foreach ($registersByMonth as $register) {
        $next = next($register);
        $registersByDay = $this->group_by("date",$register,"day");
        if(count($registersByDay) >= 20){
          $registerLast = (sizeof($registersByDay)>29)?$this->filterByYear(array_values($registersByDay)[29]):array();
          $registerFirst = $this->filterByYear(array_values($registersByDay)[0]);
          $probabilities[] = $this->returnAVG($model,$registerLast,$registerFirst);
        }else{
          if($next != NULL && count($next) >= 20) {
            $registerLast = (sizeof($next)>29)?$this->filterByYear(array_values($next)[29]):array();
            $registerFirst = $this->filterByYear(array_values($next)[0]);
            $probabilities[] = $this->returnAVG($model,$registerLast,$registerFirst);
          }else{
            $probabilities[] = $this->returnAVG($model,array(),array());
          }
        }
      }
      return $probabilities;
    }

    public function getAllProbability($url, $model) //Obtiene la probabilidad de todos los años
    {
      $registersByYear = $this->getRegistersFromFile($url,"year");
      foreach ($registersByYear as $registers) {
        $probabilityMonth = 0;
        $registersByMonth = array_reverse($this->group_by("date",$registers,"month"));
        foreach ($registersByMonth as $register) {
          $next = next($register);
          $registersByDay = $this->group_by("date",$register,"day");
          if(count($registersByDay) >= 20){
            $registerLast = (sizeof($registersByDay)>29)?array_values($registersByDay)[29]:array();
            $registerFirst = array_values($registersByDay)[0];
            $probabilityMonth += $this->returnAVG($model,$registerLast,$registerFirst);
          }else{
            if($next != NULL && count($next) >= 20) {
              $registerLast = (sizeof($next)>29)?array_values($next)[29]:array();
              $registerFirst = array_values($next)[0];
              $probabilityMonth += $this->returnAVG($model,$registerLast,$registerFirst);
            }else{
              $probabilityMonth += $this->returnAVG($model,array(),array());
            }
          }
        }
        $probabilities[] = $probabilityMonth / count($registersByMonth);
      }
      return $probabilities;
    }

    public function filterByYear($registers) //Función para filtrar registros solo de aquellos que corresponden al año actual
    {
      $newRegisters = [];
      foreach ($registers as $register) {
        if(date('Y') == date('Y',strtotime($register["date"]))) {
          $newRegisters[] = $register;
        }
      }
      return $newRegisters;
    }

    public function calculateAVG($registers, $name) //Obtiene el promedio de un gas dentro de cierta cantidad de registros
    {
      $count = 0;
      $sum = 0;
      foreach ($registers as $gas) {
        if($gas["name"] == $name){
          $sum += $gas["ppm"];
          $count++;
        }
      }
      return $sum/$count;
    }

    public function returnAVG($model, $registerLast, $registerFirst) //Redirecciona al método de cálculo de probabilidad dependiendo el procedimiento que tenga ligado cada monitor
    {
      if($model == "CALISTO 1" || $model == "MHT410") {
        $h2AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"H2"):0;
        $wcAvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"WC"):0;
        $h2AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"H2"):0;
        return $this->calculateProbabilityC1($wcAvgLast,$h2AvgLast,$h2AvgFirst);
      }elseif ($model == "CALISTO 2") {
        $h2AvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"H2"):0;
        $wcAvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"WC"):0;
        $coAvgLast = (count($registerLast)>0)?$this->calculateAVG($registerLast,"CO"):0;
        $h2AvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"H2"):0;
        $coAvgFirst = (count($registerFirst)>0)?$this->calculateAVG($registerFirst,"CO"):0;
        return $this->calculateProbabilityC2($wcAvgLast,$h2AvgLast,$coAvgLast,$h2AvgFirst,$coAvgFirst);
      }elseif ($model == "CALISTO 9") {
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
      }elseif ($model == "OPT100") {
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
      }
    }

    public function getGeneralRegisters($url, Monitor $monitor, $type) //Obtiene todos los registros agrupados por fecha
    {
      $registers = $this->getRegistersFromFile($url,$monitor);
      foreach ($monitor->gases as $gas) {
        $registersByGas[] = $this->group_by("date",$registers[$gas->name], $type);
      }
      return $registersByGas;
    }

    function group_by($key, $data, $type) //Función para agrupar los registros por ciertos parametros
    {
      $result = array();
      foreach($data as $val) {
        if(array_key_exists($key, $val)){
          switch($type){
            case "day":
              $result[$val[$key]][] = $val;
              break;
            case "month":
              $result[Carbon::parse($val["date"])->format('m')][] = $val;
              break;
            case "year":
              $result[Carbon::parse($val["date"])->format('Y')][] = $val;
              break;
          }
        }else{
          $result[""][] = $val;
        }
      }
      return $result;
    }

    public function getRegistersFromFile($url, $type) //Obtiene todos los registros del archivo CSV
    {
      $handle = fopen($url, "r");
      $registers = [];
      while (($data = fgetcsv($handle)) !== FALSE) {
        $registers[] = array("name" => $data[3],"date" => $data[2], "hour" => $data[4], "ppm" => $data[5]);
      }
      fclose($handle);

      return $this->group_by("date",$registers,$type);
    }

    public function getGasesString($model) //Obtiene los gases utilizados correspondientes al modelo de monitor
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

    public function getTransformerFile($url) //Retorna el id del transformador que hace referencia en el archivo CSV
    {
      $handle = fopen($url, "r") or die("Unable to open file!");
      while (($data = fgetcsv($handle)) !== FALSE) {
        $idTransformer = $data[1];
      }
      fclose($handle);
      return $idTransformer;
    }

    public function validateInsertion($url, $model) //Valida que el archivo contenga los registros de los gases requeridos para cierto monitor
    {
      $case = 0;
      $handle = fopen($url, "r") or die("Unable to open file!");
      while (($data = fgetcsv($handle)) !== FALSE) {
        if (array_key_exists(3, $data)) {
          $gases[] = $data[3];
        }
      }
      $gases = isset($gases)?array_unique($gases):NULL; //Elimina los valores duplicados
      fclose($handle);
      if($gases != NULL){
        if(sizeof($gases) === count($this->getGasesString($model))){
          $count = 0;
          foreach ($gases as $gas) {
            if(Gas::where('name',$gas)->get()->first() != NULL && $this->gasFind($model,$gas)){
              $count++;
            }
          }
          if($count===count($this->getGasesString($model))){
            $case = 1;
          }
        }
      }else{
        $case = -1;
      }
      return $case;
    }

    public function gasFind($model, $gas) //Verifica que el gas este dentro del monitor
    {
      $gases = $this->getGasesString($model);
      foreach ($gases as $gas_name) {
        if($gas_name == $gas) {
          return TRUE;
        }
      }
      return FALSE;
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
