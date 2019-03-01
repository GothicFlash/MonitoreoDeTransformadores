<?php

namespace App\Http\Controllers\Users;
use Crypt;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Auth;

class UsersController extends Controller
{
    public function index() //Muestra la pagina principal de usuarios
    {
      $users = User::get();
      return view($this->redirectTo().'.Users.index',compact('users'));
    }

    public function create() //Redireccion a la pagina para crear un nuevo usuario
    {
        return view($this->redirectTo().'.Users.create');
    }

    public function exists($new_email, $old_email){ // Verifica si el correo de un usuario ya existe
      $tag = "";
      if($old_email != $new_email){
        $user = User::where('email',$new_email)->get();
        if(!$user->isEmpty()){
          $tag = "<span style='font-weight:bold;color:red;'>Este correo ya esta siendo utilizado.</span>";
        }
      }
      return $tag;
    }

    public function store(Request $request) //Función para agregar un nuevo usuario
    {
        $user = new User;
        $user->name = $request->name;
        $user->email =  $request->email;
        $user->type = $request->type;
        $user->password = bcrypt($request->password);
        $user->encrypted_password = Crypt::encrypt($request->password);
        if($user->save()){
          alert()->success('El usuario ha sido agregado correctamente')->autoclose(1500);
          return redirect()->route('users.index');
        }else{
          alert()->error('Hubo un problema al procesar la acción', 'Oops!');
          return redirect()->route('users.index');
        }
    }

    public function edit(User $user) //Redirección a la pagina para editar los datos de un usuario
    {
      return view($this->redirectTo().'.Users.edit',compact('user'));
    }

    public function update(Request $request, $id) //Función para actualizar los datos de un usuario
    {
      $user = User::find($id);
      $user->name = $request->name;
      $user->email = $request->email;
      $user->type = $request->type = $request->type;
      $user->password = bcrypt($request->password);
      $user->encrypted_password = Crypt::encrypt($request->password);
      if($user->save()){
        alert()->success('El usuario ha sido modificado correctamente')->autoclose(1500);
        return redirect()->route('users.index');
      }else{
        alert()->error('Hubo un problema al procesar la acción', 'Oops!');
        return redirect()->route('users.index');
      }
    }

    public function destroy(Request $request) //Función para eliminar un usuario
    {
      $user = User::find($request->id);
      if($user->delete()){
        return response()->json(['success'=>'']);
      }
    }

    public function redirectTo() //Redirección dependiendo el tipo de usuario
    {
      $type = '';
      switch(Auth::user()->type){
        case 'admin':
          $type = 'admin-user';
          break;
        case 'config':
          $type = 'config-user';
          break;
      }
      return $type;
    }
}
