<?php

namespace App\Http\Controllers\Notifications;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notification;
use Auth;
use Response;

class NotificationsController extends Controller
{
    public function index() //Muestra la pagina principal para las notificaciones
    {
      $notifications = Notification::get();
      return view($this->redirectTo().'.Notifications.index',compact('notifications'));
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

    public function destroy(Request $request) //Elimina una notificación de la base de datos
    {
      $notification = Notification::find($request->id);
      if($notification->delete()){
        return response()->json(['success'=>'']);
      }
    }

    public function downloadNotifications() //Función para descargar el archivo de notificaciones en formato TXT
    {
      $notifications = Notification::get(); //Obtiene todas las notificaciones
      $filename = "Thot-Notifications";
      $content = "";
      foreach ($notifications as $notification) {
        $content .= $notification->hour." ".$notification->date." ".$notification->description;
        $content .= chr(13).chr(10);
      }
      $file = fopen($filename,"w");
      fwrite($file,$content);
      fclose($file);
      $headers = array(
        'Content-Type' => 'text/txt',
      );
      return Response::download($filename, $filename.'.txt', $headers);
    }
}
