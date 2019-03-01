<?php

namespace App\Http\Controllers\Monitors;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Monitor;
use App\Register;
use App\Gas;
use App\Image;
use Auth;
use View;
use App\Transformer;
use Carbon\Carbon;
use Response;

class MonitorsController extends Controller
{
    public function __construct() //Establece el formato de hora para la ciudad de méxico
    {
      date_default_timezone_set('America/Mexico_City');
    }

    public function index(Request $request) //Muestra la pagina principal de los monitores
    {
      $monitors = Monitor::where('state',1)->get();
      return view($this->redirectTo().'.Monitors.index',compact('monitors'));
    }

    public function exists($new_node, $old_node) //Verifica si el nodo de algun monitor esta repetido
    {
      $tag = "";
      if($old_node != $new_node){
        $monitor = Monitor::where('state',1)->where('node',$new_node)->get();
        if(!$monitor->isEmpty()){
          $tag = "<span style='font-weight:bold;color:red;'>Este nodo ya esta siendo utilizado.</span>";
        }
      }
      return $tag;
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

    public function create() //Redirecciona a la pagina para crear un nuevo monitor
    {
      $gases = Gas::get();
      return view($this->redirectTo().'.Monitors.create',compact('gases'));
    }

    public function getActiveTransformers(Monitor $currentMonitor) //Obtiene los transformadores compatibles para poder enlazar a un monitor
    {
      $transformers_id = [];
      $transformers = Transformer::get();
      foreach ($transformers as $transformer) {
        foreach ($transformer->monitors as $monitor) {
          if($monitor->state == 1 && $this->validateChange($currentMonitor,$monitor) && $currentMonitor->model != $monitor->model){
            $transformers_id[] = $transformer->id;
          }
        }
      }
      return array_unique($transformers_id);
    }

    public function validateChange(Monitor $currentMonitor, Monitor $monitor) //Valida que la cantidad de gases de un modelo en especifico coincida con los de un monitor
    {
      $count = 0;
      foreach ($currentMonitor->gases as $gas) {
        if($monitor->gases->where('name',$gas->name)->first() != NULL){
          $count++;
        }
      }
      return ($count==count($currentMonitor->gases))?true:false;
    }

    /**
     * Description: Redirección al metodo correspondiente para crear un nuevo monitor
     * @param \Illuminate\Http\Request @request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      switch($this->redirectTo()){
        case 'admin-user':
          $this->storeForAdmin($request);
          break;
        case 'config-user':
          $this->storeForConfig($request);
          break;
      }
      return redirect()->route('home');
    }

    public function storeForAdmin($request)
    {
      $transformer = new Transformer;
      $transformer->name = $request->name;
      if($transformer->save()){
        $monitor = new Monitor;
        $monitor->node = $request->node;
        $monitor->model = $request->model;
        if($monitor->save()){
          $this->insertDefault($monitor); //Agrega la configuración por default dependiendo el monitor que se desee agregar
          $monitor->transformers()->attach($transformer->id,['created_at' => Carbon::now()]);
          $this->writeConfigurationFile($this->getCurrentTimeFile()); //Agrega el nuevo monitor al archivo de configuración
          $file = $request->file('file');
          if($file != NULL){
            $path = public_path().'/images/Monitors/';
            $image = new Image;
            $name = time().'.'.$file->getClientOriginalName();
            $file->move($path,$name);
            $image->file = $name;
            $monitor->image()->save($image);
          }
        }
        alert()->success('El monitor ha sido agregado correctamente')->autoclose(1500);
      }else{
        alert()->error('Hubo un problema al procesar la acción', 'Oops!');
      }
    }

    public function storeForConfig($request)
    {
      switch ($request->type) {
        case 'standard':
          $this->storeForAdmin($request);
          break;
        case 'personalized':
          $transformer = new Transformer;
          $transformer->name = $request->name;
          if($transformer->save()){
            $monitor = new Monitor;
            $monitor->node = $request->node;
            $monitor->model = $request->modelPersonalized;
            if($monitor->save() && is_array($request->gases)){
              foreach ($request->gases as $gas) {
                $monitor->gases()->attach($gas);
              }
              $monitor->procedures()->attach($request->methods);
              $monitor->transformers()->attach($transformer->id,['created_at' => Carbon::now()]);
              $this->writeConfigurationFile($this->getCurrentTimeFile()); //Add the new monitor in the configuration file
              $file = $request->file('file');
              if($file != NULL){
                $path = public_path().'/images/Monitors/';
                $image = new Image;
                $name = time().'.'.$file->getClientOriginalName();
                $file->move($path,$name);
                $image->file = $name;
                $monitor->image()->save($image);
              }
            }
            alert()->success('El monitor ha sido agregado correctamente')->autoclose(1500);
          }else{
            alert()->error('Hubo un problema al procesar la acción', 'Oops!');
          }
          break;
      }
    }

    public function writeConfigurationFile($time) //Función para escribir el archivo de configuración
    {
      $monitors = Monitor::where('state',1)->get(); //Obtiene los monitores que esten activos
      $url = public_path("config/ThotXfrm.cfg");
      if(\File::exists($url)){ //Verifica que el archivo exista
        $file = fopen($url, "w");
        fwrite($file, $time . PHP_EOL); //Escribe el tiempo actual de poleo
        fwrite($file, count($monitors) . PHP_EOL); //Escribe la cantidad de monitores activos
        foreach ($monitors as $monitor) {
          fwrite($file, $this->generateLineFile($monitor) . PHP_EOL); //Escribe la linea correspondiente en el archivo para los diferentes monitores
        }
        fclose($file);
      }
    }

    public function getCurrentTimeFile() //Obtiene el tiempo actual de poleo en el archivo
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

    public function generateLineFile(Monitor $monitor) //Genera la linea correspondiente en el archivo, respetando la nomenclatura asignada
    {
      $node = "";

      if($monitor->node < 10){
        $node = "00".$monitor->node;
      }elseif ($monitor->node >= 10 && $monitor->node < 100) {
        $node = "0".$monitor->node;
      }elseif ($monitor->node >= 100) {
        $node = $monitor->node;
      }
      return $node." ".$this->assignModelFile($monitor)." ".$monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->name;
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
        default:
          $model = $monitor->model;
          break;
      }
      return $model;
    }

    public function insertDefault(Monitor $monitor)
    {
      switch ($monitor->model) {
        case "CALISTO 1":
          $monitor->gases()->attach(1); //Gas H2
          $monitor->gases()->attach(2); //Gas WC
          $monitor->procedures()->attach(1); //Reference to method 1 from CALISTO 1
          break;
        case "CALISTO 2":
          $monitor->gases()->attach(1); //Gas H2
          $monitor->gases()->attach(2); //Gas WC
          $monitor->gases()->attach(3); //Gas CO
          $monitor->procedures()->attach(2); //Reference to method 2 from CALISTO 2
          break;
        case "MHT410":
          $monitor->gases()->attach(1); //Gas H2
          $monitor->gases()->attach(2); //Gas WC
          $monitor->gases()->attach(4); //Gas TEMPERATURA
          $monitor->procedures()->attach(1); //Reference to method 1 from CALISTO 1
          break;
        case "CALISTO 9":
          $monitor->gases()->attach(1); //Gas H2
          $monitor->gases()->attach(5); //Gas CH4
          $monitor->gases()->attach(6); //Gas C2H6
          $monitor->gases()->attach(7); //Gas C2H4
          $monitor->gases()->attach(8); //Gas C2H2
          $monitor->gases()->attach(3); //Gas CO
          $monitor->gases()->attach(9); //Gas CO2
          $monitor->gases()->attach(10); //Gas O2
          $monitor->gases()->attach(2); //Gas WC
          $monitor->procedures()->attach(3); //Reference to method 3 from CALISTO 9
          break;
        case "OPT100":
          $monitor->gases()->attach(1); //Gas H2
          $monitor->gases()->attach(5); //Gas CH4
          $monitor->gases()->attach(6); //Gas C2H6
          $monitor->gases()->attach(7); //Gas C2H4
          $monitor->gases()->attach(8); //Gas C2H2
          $monitor->gases()->attach(3); //Gas CO
          $monitor->gases()->attach(9); //Gas CO2
          $monitor->gases()->attach(10); //Gas WC
          $monitor->gases()->attach(2); //Gas WC
          $monitor->procedures()->attach(4); //Reference to method 3 from CALISTO 9
          break;
      }
    }

    public function getGasesStringByModel($model) //Obtiene los gases utilizados correspondientes al modelo de monitor
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

    public function getGasesString(Monitor $monitor) //Obtiene los gases de un monitor
    {
      $count = 0;
      foreach ($monitor->gases as $gas) {
        $gases[$count] = $gas->name;
        $count++;
      }

      return implode(", ",isset($gases)?$gases:array(""));
    }

    public function getTransformersString(Monitor $monitor) //Obtiene los transformadores asociados a un transformador
    {
      if($monitor->transformers->count() > 1){
        foreach ($monitor->transformers as $transformer) {
          if($transformer->id != $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->id){
            $transformers[] = $transformer->name;
          }
        }
        return implode(", ",$transformers);
      }

      return "Ninguno";
    }

     public function show(Monitor $monitor, $idTransformer) //Función para gráficar el estado de probabilidad de un modelo en particular
     {
       $state = FALSE;
       $gases = $this->getGasesString($monitor);
       $transformer = Transformer::find($idTransformer);
       if($transformer->registers()->count()>0){
         alert()->success('Generando grafica', 'Obteniendo datos')->autoclose(2000);
         if($this->verifyState($monitor,$transformer)){
           $state = TRUE;
         }
         return view($this->redirectTo().'.Monitors.view',compact('monitor','idTransformer','gases','state','transformer'));
       }else{
         alert()->error('El monitor no contiene información para graficar', 'Oops!');
         return redirect()->back();
       }
     }

     public function verifyState(Monitor $monitor, Transformer $transformer) //Verifica el estado actual de probabilidad, esto para poder lanzar un cartel indicando el estado de la misma
     {
       if(($monitor->model == "CALISTO 1" || $monitor->model == "MHT410") && round($this->getProbabilityByMonth($monitor,$transformer)) >= 49){
         return TRUE;
       }else{
         if($monitor->model == "CALISTO 2" && round($this->getProbabilityByMonth($monitor,$transformer)) >= 47){
           return TRUE;
         }
       }
       return FALSE;
     }

     public function showDescription(Monitor $monitor) //Muestra la información escencial de un monitor
     {
       $gases = $this->getGasesString($monitor);
       $transformers = $this->getTransformersString($monitor);
       return view('default-user.Monitors.show',compact('monitor','gases','transformers'));
     }

     public function detailGases(Monitor $monitor, $idTransformer) //Redirección a la pagina para mostrar las mediciones de los gases de un monitor en particular
     {
       $transformer = Transformer::find($idTransformer);
       $gases = $this->getGasesString($monitor);
       return view($this->redirectTo().'.Monitors.detail-gases',compact('monitor','transformer','idTransformer','transformer','gases'));
     }

     /**
      * Description: Obtiene la probabilidad por mes
      * @param App\Monitor $monitor
      * @param App\Transformer $transformer
      * @return float
      */
     public function getProbabilityByMonth(Monitor $monitor, Transformer $transformer)
     {
       $probability = 0;
       $registersYesterday = $transformer->registers()->whereDate('date', '=', Carbon::now()->subDays(2)->format('Y-m-d'))->get();
       $registersLast30Days = $transformer->registers()->whereDate('date', '=', Carbon::now()->subDays(32)->format('Y-m-d'))->get();

       $registersYesterday = ($registersYesterday->first()!=NULL)?$registersYesterday->first()->gases:array();
       $registersLast30Days = ($registersLast30Days->first()!=NULL)?$registersLast30Days->first()->gases:array();
       return $this->returnAVG($monitor,$registersYesterday,$registersLast30Days);
     }

     /**
      * Description: Obtiene las probabilidades dependiendo el procedimiento que tenga asignado el monitor
      * @param App\Monitor $monitor
      * @param Illuminate\Support\Collection $registerLast
      * @param Illuminate\Support\Collection $registerFirst
      * @return float
      */
      public function returnAVG(Monitor $monitor, $registerLast, $registerFirst){
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
             return $this->calculateProbabilityC9($h2AvgLast,$ch4AvgLast,$c2h6AvgLast,$c2h4AvgLast,$c2h2AvgLast,$coAvgLast,$co2AvgLast,$o2AvgLast,$wcAvgLast,$h2AvgFirst,$ch4AvgFirst,$c2h6AvgFirst,$c2h4AvgFirst,$c2h2AvgFirst,$coAvgFirst,$co2AvgFirst);
             break;
           case 4:
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

     public function edit(Monitor $monitor) //Redirección a la pagina para editar los datos de un monitor
     {
       switch($this->redirectTo()){
         case 'admin-user': //Redirección a la vista de administrador
           $gases = $this->getGasesString($monitor);
           $transformers = $this->getTransformersString($monitor);
           return view('admin-user.Monitors.edit',compact('monitor','gases','transformers'));
         case 'config-user': //Redirección a la vista de usuario de configuración
           $gases = $this->getGasesString($monitor);
           $transformers = $this->getActiveTransformers($monitor);
           $transformersNames = Transformer::get();
           return view('config-user.Monitors.edit',compact('monitor','gases','transformers','transformersNames'));
       }
     }

     public function validateTransformers($gases) //Obtiene los transformadores que puedan ser ligados en base a una cantidad de gases
     {
       $transformers_id = [];
       $transformers = Transformer::get();
       foreach ($transformers as $transformer) {
         if($transformer->monitors->first() != null && $transformer->monitors->first()->state == 1 && $this->monitorHasGases($transformer->monitors->first()->gases,$gases)){ //Verify if monitor is active
           $transformers_id[] = $transformer->id;
         }
       }
       return $transformers_id;
     }

     public function monitorHasGases($monitor_gases, $gases) //Verifica que el monitor contiene una cierta cantidad de gases
     {
       $count = 0;
       foreach ($gases as $gas) {
         if($monitor_gases->find($gas) != null){
           $count++;
         }
       }
       return ($count==count($monitor_gases))?true:false;
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

     /**
      * Description: Método para el cálculo de probabilidad del CALISTO 1 y MHT410
      * @param float $wcAvgYesterday
      * @param float $h2AvgYesterday
      * @param float $h2AvgLast30Days
      * @return float $probability
      */
     public function calculateProbabilityC1($wcAvgYesterday, $h2AvgYesterday, $h2AvgLast30Days)
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

     /**
      * Description: Método para el cálculo de probabilidad del CALISTO 2
      * @param float $wcAvgYesterday
      * @param float $h2AvgYesterday
      * @param float $coAvgYesterday
      * @param float $h2AvgLast30Days
      * @param float $coAvgLast30Days
      * @return float $probability
      */
     public function calculateProbabilityC2($wcAvgYesterday, $h2AvgYesterday, $coAvgYesterday, $h2AvgLast30Days, $coAvgLast30Days)
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

     public function updateForAdmin(Request $request, Monitor $monitor)
     {
       $monitor->node = $request->node;
     }

     public function updateForConfig(Request $request, Monitor $monitor)
     {
       $monitor->node = $request->node;

       foreach ($monitor->transformers as $old_transformer) {
         $monitor->transformers()->detach($old_transformer->id);
       }

       $monitor->transformers()->attach($request->current_transformer,['created_at' => Carbon::now()]);

       if($request->transformers != NULL){
         //Add new transformers
         foreach ($request->transformers as $new_transformer) {
           $monitor->transformers()->attach($new_transformer,['created_at' => Carbon::now()]);
         }
       }
     }

    public function update(Request $request, $monitor)
    {
      switch($this->redirectTo()){
        case 'admin-user':
          $this->updateForAdmin($request,$monitor);
          break;
        case 'config-user':
          $this->updateForConfig($request,$monitor);
          break;
      }

      //-------------------Section to change the image if exists-------------------
      $file = $request->file('file');
      if($file != NULL){
        $path = public_path().'/images/Monitors/';
        $name = time().'.'.$file->getClientOriginalName();
        if($monitor->image != null){
          $url = "images/Monitors/".$monitor->image->file;
          if(\File::exists(public_path($url))){
            \File::delete(public_path($url));
          }
          $file->move($path,$name);
          $monitor->image->file = $name;
          $monitor->image->save();
        }else{
          $image = new Image;
          $file->move($path,$name);
          $image->file = $name;
          $monitor->image()->save($image);
        }
      }

      //------------------------Section to save the information-------------------------
      if($monitor->save()){
        $this->writeConfigurationFile($this->getCurrentTimeFile());
        alert()->success('El monitor se modifico correctamente')->autoclose(1500);
        return redirect()->route('home');
      }else{
        alert()->error('Hubo un problema al procesar la acción', 'Oops!');
        return redirect()->route('home');
      }
    }

    public function destroy(Request $request)
    {
      $monitor = Monitor::find($request->id);
      $monitor->state = 0;
      $monitor->save();
      $this->writeConfigurationFile($this->getCurrentTimeFile());
    }
}
