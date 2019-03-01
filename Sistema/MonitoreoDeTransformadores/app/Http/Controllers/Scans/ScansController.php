<?php

namespace App\Http\Controllers\Scans;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Monitor;

class ScansController extends Controller
{
    public function index() //Muestra la pagina principal de tiempo de poleo
    {
      $time = $this->getCurrentTimeFile() / 60; //Obtiene el tiempo actual de poleo en minutos
      return view('config-user.Scans.index',compact('time'));
    }

    public function store(Request $request) //Sobrescribe el tiempo de poleo en segundos
    {
      $this->writeConfigurationFile(($request->time * 60));
    }

    public function writeConfigurationFile($time) //Función para escribir en el archivo de configuración
    {
      $monitors = Monitor::where('state',1)->get();
      $url = public_path("config/ThotXfrm.cfg");
      if(\File::exists($url)){
        $file = fopen($url, "w");
        fwrite($file, $time . PHP_EOL); //Write the time
        fwrite($file, count($monitors) . PHP_EOL); //Write the quantity of monitors availables
        foreach ($monitors as $monitor) {
          fwrite($file, $this->generateLineFile($monitor) . PHP_EOL); //Write each monitor in the file
        }
        fclose($file);
      }
    }

    public function getCurrentTimeFile() //Obtiene el tiempo actual de poleo en el archivo de configuración
    {
      $time = 0;
      $url = public_path("config/ThotXfrm.cfg");
      if(\File::exists($url)){
        $file = fopen($url, "r");
        $time = (int)fgets($file);
        fclose($file);
      }
      return $time;
    }

    public function generateLineFile(Monitor $monitor) ////Genera la linea correspondiente en el archivo, respetando la nomenclatura asignada
    {
      $node = "";

      if($monitor->node < 10){
        $node = "00".$monitor->node;
      }elseif ($monitor->node >= 10 && $monitor->node < 100) {
        $node = "0".$monitor->node;
      }elseif ($monitor->node >= 100) {
        $node = $monitor->node;
      }

      return $node." ".$this->assignModelFile($monitor)." ".$monitor->name;
    }

    public function assignModelFile(Monitor $monitor) //Genera la nomenclatura correspondiente al modelo del monitor, se considera para agregarla en el archivo de configuración
    {
      $model = "";
      switch ($monitor->model) {
        case "CALISTO 1":
          $model = "C1";
          break;
        case "CALISTO 2":
          $model = "C2";
          break;
        case "MHT410":
          $model = "V1";
          break;
        case "CALISTO 9":
          $model = "C9";
          break;
        case "OPT100":
          $model = "OPT100";
          break;
      }
      return $model;
    }
}
