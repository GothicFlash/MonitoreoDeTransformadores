<?php

namespace App\Http\Controllers\Imports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Monitor;
use App\Register;
use App\Gas;
use App\Transformer;
use Auth;

class ImportsController extends Controller
{
    public function index() //Mustra la pagina principal para importar registros
    {
      $monitors = Monitor::where('state',1)->get();
      return view($this->redirectTo().'.Imports.index',compact('monitors'));
    }

    public function store(Request $request) //Inserta todos los registros provenientes del archivo CSV
    {
      $url = $request->file; //Obtiene la URL del archivo de registros
      if(\File::exists($url)){ //Verifica que aún exista el archivo
        $monitor = Monitor::find($request->monitor);
        if($this->validateInsertion($url,$monitor) === 1){ //Verifica que los registros de los gases corresponden a los gases del monitor seleccionado
          try {
            date_default_timezone_set('America/Mexico_City'); //Agrega la zona horaria de la ciudad de méxico
            ini_set('max_execution_time', 0);
            $handle = fopen($url, "r"); //Se abre el archivo
            while (($data = fgetcsv($handle)) !== FALSE) { //Realiza la lectura del archivo
              $transformer = $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first();
              $date = $data[2];
              $register = $transformer->registers->where('date',$date)->first();
              if($register == NULL){ //Verifica que el registro no este agregado
                $register = new Register;
                $register->date = $date;
                $transformer->registers()->save($register);
              }
              if(!$this->IsInBinnacle($register,$data[3],$data[5],$data[4])){ //Verifica que la medición no este agregada
                $register->gases()->attach(Gas::where('name',$data[3])->get()->first()->id,['ppm' => $data[5],'hour' => $data[4]]);
              }
            }
            fclose($handle);
          } catch (\Exception $e) {
            return response()->json(2); //Case for file exception
          }
        }
      }else {
        return response()->json(3); //Cause for empty file or file doesn't exist
      }
      return response()->json($this->validateInsertion($url,Monitor::find($request->monitor)));
    }

    public function IsInBinnacle($register, $gas, $ppm, $hour) //Funcion para verificar si un registro ya fue agregado a la tabla binnacle (bitácora)
    {
      $state = FALSE;
      if($register->gases != NULL){
        foreach ($register->gases->where('name',$gas) as $value) {
          if($value->pivot->ppm == $ppm && $value->pivot->hour == $hour){
            $state = TRUE;
          }
        }
      }
      return $state;
    }

    public function validateInsertion($url, Monitor $monitor) //Verifica que el archivo es el formato adecuado
    {
      $case = 0;
      $handle = fopen($url, "r") or die("Unable to open file!"); //with lock
      while (($data = fgetcsv($handle)) !== FALSE) {
        if (array_key_exists(3, $data)) {
          $gases[] = $data[3];
        }
      }
      $gases = isset($gases)?array_unique($gases):NULL; //Elimina valores duplicados de los gases
      fclose($handle);
      if($gases != NULL){
        if(sizeof($gases) === count($monitor->gases)){
          $count = 0;
          foreach ($gases as $gas) {
            if(Gas::where('name',$gas)->get()->first() != NULL && $monitor->gases->find(Gas::where('name',$gas)->get()->first()->id) != NULL){
              $count++;
            }
          }
          if($count===count($monitor->gases)){
            $case = 1;
          }
        }
      }else{
        $case = -1;
      }
      return $case;
    }

    public function redirectTo(){ //Función para redireccionar dependiendo el tipo de usuario
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

    public function loadInformation(){
      $message = "No hay registros que vaciar";
      date_default_timezone_set('America/Mexico_City');
      ini_set('max_execution_time', 0);
      $this->fileAccessed = FALSE;
      $url = public_path("Registers/archivocsv.csv");
      if(\File::exists($url)){
        $message = "Archivo existente -> ";
        $handle = fopen($url, "r") or die("Unable to open file!"); //with lock
        if (flock($handle, LOCK_EX | LOCK_NB)) { //file without block
            $message .= "Los registros fueron vaciados";
            //---Inserts the information in DB
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
              $monitor = Monitor::where('node',$data[2])->where('state',1)->get()->first();
              if($monitor != NULL){
                $time_info = explode(" ", $data[0]); //Separates date and hour
                $date = $time_info[0]; //Contains the date in the column 0 of the CSV file
                $hour = $time_info[1]; //Contains the hour in the column 1 of the CSV file
                $register = $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->where('date',$date)->first();
                if($register == NULL){ //Check if the record has already been added
                  $register = new Register;
                  $register->date = $date;
                  $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers()->save($register);
                }
                foreach ($monitor->gases as $gas) { //This structure is for create a register in the DB depends of each gas that monitor has
                  $index = $this->findGasPosition(strtolower($gas->name));
                  if($index != -1){
                    $ppm = $data[$index];
                    if(!$this->IsInBinnacle($register,$gas->name,$ppm,$hour)){ //Verifica que la medición no este agregada
                      $register->gases()->attach($gas->id,['ppm' => $ppm,'hour' => $hour]);
                    }
                  }
                }
              }
            }
            //---

            flock($handle, LOCK_UN);    // release the lock
            $this->fileAccessed = TRUE;
        }else{
          $message .= "Los registros ya fueron vaciados por otro equipo";
        }

        fclose($handle);
        if($this->fileAccessed){
            \File::delete($url);
        }
      }
      return json_encode($message);
    }

    public function findGasPosition($name) //Retorna una posición especifica del archivo CSV dependiendo del nombre del gas y el valor de la columna
    {
      $column = -1;
      switch ($name) {
        case 'h2':
          $column =  4;
          break;
        case 'co':
          $column = 5;
          break;
        case 'ch4':
          $column = 6;
          break;
        case 'c2h2':
          $column = 7;
          break;
        case 'c2h4':
          $column = 8;
          break;
        case 'c2h6':
          $column = 9;
          break;
        case 'co2':
          $column = 10;
          break;
        case 'o2':
          $column = 11;
          break;
        case 'n2':
          $column = 12;
          break;
        case 'wc':
          $column = 13;
          break;
        case 'tgc':
          $column = 14;
        case 'temperatura':
          $column = 14;
          break;
        case 'co2co':
          $column = 15;
          break;
        case 'wcrs':
          $column = 16;
          break;
      }
      return $column;
    }
}
