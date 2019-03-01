<?php

namespace App\Http\Controllers\Gases;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Gas;
use Auth;
use App\Monitor;

class GasesController extends Controller
{
    public function index() //Muestra la pagina principal de los gases
    {
      $monitors = Monitor::get();
      $gases = Gas::orderBy('id')->get();
      return view($this->redirectTo().'.Gases.index',compact('gases','monitors'));
    }

    public function exists($new_gas, $old_gas){ //Verifica si existe un gas repetido
      $tag = "";
      if($old_gas != $new_gas){
        $gas = Gas::where('name',$new_gas)->get();
        if(!$gas->isEmpty()){
          $tag = "<span style='font-weight:bold;color:red;'>El gas ya existe.</span>";
        }
      }
      return $tag;
    }

    public function redirectTo(){ //Redirecciona dependiendo el tipo de usuario
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

    public function create() //Redirecciona a la pagina para crear un nuevo gas
    {
        return view('config-user.Gases.create');
    }

    public function store(Request $request) //Recibe los datos para crear un nuevo gas en la base de datos
    {
        $gas = new Gas;
        $gas->name = $request->name;
        if($gas->save()){
          alert()->success('El gas ha sido agregado correctamente')->autoclose(1500);
          return redirect()->route('gases.index');
        }else{
          alert()->error('Hubo un problema al procesar la acción', 'Oops!');
          return redirect()->route('gases.index');
        }
    }

    public function edit($gas) //Redirecciona a la pagina para editar un gas
    {
      $gas = Gas::find($gas);
      return view('config-user.Gases.edit',compact('gas'));
    }

    public function update(Request $request, $id) //Modifica la información de un gas
    {
      $gas = Gas::find($id);
      $gas->name = $request->name;
      if($gas->save()){
        alert()->success('El gas se modifico correctamente')->autoclose(1500);
        return redirect()->route('gases.index');
      }else{
        alert()->error('Hubo un problema al procesar la acción', 'Oops!');
        return redirect()->route('gases.index');
      }
    }

    public function destroy(Request $request) //Elimina un gas de la base de datos
    {
      try {
        $gas = Gas::find($request->id);
        if($gas->delete()){
          return response()->json(['error'=>'']);
        }
      }catch (\Illuminate\Database\QueryException $e){
        return response()->json(['error'=>$e]);
      }
    }
}
