<?php

namespace App\Http\Controllers\Excel;
use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Monitor;
use App\Exports\MonitorsExport;

class ExcelController extends Controller
{
    public function exportCSV(Request $request){ //Retorna el archivo CSV utilizable dentro del sistema
      $monitor = Monitor::find($request->monitors);
      $transformer = $monitor->transformers->where('id',$request->transformers)->first();
      return Excel::download(new MonitorsExport($monitor,$transformer),$monitor->model."-Nodo ".$monitor->node."-".$transformer->name.".csv");
    }
}
