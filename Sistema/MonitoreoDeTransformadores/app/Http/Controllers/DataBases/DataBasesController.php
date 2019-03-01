<?php

namespace App\Http\Controllers\DataBases;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Monitor;
use Auth;
use Response;

class DataBasesController extends Controller
{

    public function index() //Muestra la pagina principal de base de datos
    {
        $monitors = Monitor::get();
        return view($this->redirectTo().'.DataBases.index',compact('monitors'));
    }

    public function redirectTo() //Redirecciona dependiendo el tipo de usuario
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

    public function downloadDatabase(Request $request){ //Descarga el archivo de la base de datos respetando el formato actual de confiamex
      $monitor = Monitor::find($request->monitor);
      ini_set('max_execution_time', 900);
      ini_set('memory_limit', '-1');
      $filename = $monitor->model."-Nodo ".$monitor->node."-".$monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->name;
      $handle = fopen($filename, 'w+');
      fputcsv($handle, array('', 'H2 (ppm)', 'CO (ppm)', 'CH4 (ppm)', 'C2H2 (ppm)', 'C2H4 (ppm)', 'C2H6 (ppm)', 'CO2 (ppm)', 'O2 (ppm)', 'N2 (ppm)', 'SF6 (ppm)', 'TDG (%)', 'TDCG (%)', 'THCG (%)', 'WC (ppm)', 'RS@25°C (%)', 'RS@T1 (%)', 'RS@T2 (%)', 'RS@T3 (%)', 'T1 (°C)', 'T2 (°C)', 'T3 (°C)', 'Carrier Presure (psi)', 'Calibration Gas Pressure (psi)', 'Load (kVA)', 'Load (MVA)', 'Load (A)', 'Load (%)', 'H2 (% LEL)'));
      if($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->count() > 0){
        $this->writeInCSV($handle,$monitor);
      }
      fclose($handle);
      $headers = array(
        'Content-Type' => 'text/csv',
      );
      return Response::download($filename, $filename.'.csv', $headers);
    }

    public function writeInCSV($handle, Monitor $monitor) //Funcion para escribir directamente en el archivo CSV
    {
      foreach ($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers as $register) {
        $registerByHour = $register->gases->groupBy(function($val) {
          return $val->pivot->hour;
        });
        foreach ($registerByHour as $gases) {
          $line = array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '');
          foreach ($gases as $gas) {
            $line[$this->returnCSVPosition($gas->name)] = $gas->pivot->ppm;
            $hour = $gas->pivot->hour;
          }
          $line[0] = $register->date.' '.$hour;
          fputcsv($handle,$line);
        }
      }
    }

    public function returnCSVPosition($column) //Returna la posicion de columna en el archivo para poder agregar contenido
    {
      switch (strtolower($column)) {
        case 'h2':
          return 1;
        case 'wc':
          return 14;
        case 'co':
          return 2;
        case 'temperatura':
          return 19;
        case 'ch4':
          return 3;
        case 'c2h6':
          return 6;
        case 'c2h4':
          return 5;
        case 'c2h2':
          return 4;
        case 'co2':
          return 7;
        case 'o2':
          return 8;
      }
    }
}
