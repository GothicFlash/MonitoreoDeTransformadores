<?php

namespace App\Http\Controllers\Backs;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Artisan;
use Log;
use Storage;

use Symfony\Component\Process\Process;

class BacksController extends Controller
{
    public function __construct(){ //Función para establecer la hora correspondiente a la ciudad de méxico
      date_default_timezone_set('America/Mexico_City');
    }

    public function index()
    {
      $disk = Storage::disk(config('backup.backup.destination.disks')[0]);
      $files = $disk->files(config('backup.backup.name'));
      $backups = [];
      // Hace un arreglo de todos los respaldos con su tamaño y fecha de creación
      foreach ($files as $k => $f) {
        // Solo cuenta los archivos que son de tipo ZIP
        if (substr($f, -4) == '.zip' && $disk->exists($f)) {
          $backups[] = [
            'file_path' => $f,
            'file_name' => str_replace(config('backup.backup.name') . '/', '', $f),
            'file_size' => $this->human_filesize($disk->size($f)),
            'last_modified' => date("Y-m-d H:i:s", $disk->lastModified($f)),
          ];
        }
      }
      //Invierte el arreglo para que el ultimo respaldo agregado se muestre al principio
      $backups = array_reverse($backups);
      return view("config-user.Backs.index",compact('backups'));
    }

    public function create()
    {
      try {
        // Inicia el proceso de respaldo
        Artisan::call('backup:run');
        $output = Artisan::output();
        // Returna el respaldo como una llamada ajax
        return redirect()->back();
      } catch (Exception $e) {
        Flash::error($e->getMessage());
        return redirect()->back();
      }
    }

    public function download($file_name) //Esta función sirve para descargar un respaldo especifico en formato ZIP
    {
      $file = config('backup.backup.name') . '/' . $file_name;
      $disk = Storage::disk(config('backup.backup.destination.disks')[0]);
      if ($disk->exists($file)) {
        $fs = Storage::disk(config('backup.backup.destination.disks')[0])->getDriver();
        $stream = $fs->readStream($file);

        return \Response::stream(function () use ($stream) {
          fpassthru($stream);
        }, 200, [
          "Content-Type" => $fs->getMimetype($file),
          "Content-Length" => $fs->getSize($file),
          "Content-disposition" => "attachment; filename=\"" . basename($file) . "\"",
        ]);
      } else {
        abort(404, "The backup file doesn't exist.");
      }
    }

    public function delete($file_name) //Esta función sirve para eliminar un archivo en especifico del disco local
    {
      $disk = Storage::disk(config('backup.backup.destination.disks')[0]);
      if ($disk->exists(config('backup.backup.name') . '/' . $file_name)) {
        $disk->delete(config('backup.backup.name') . '/' . $file_name);
        alert()->success('El respaldo ha sido eliminado', 'Eliminando respaldo')->autoclose(1500);
        return redirect()->route('backs.index');
      } else {
        abort(404, "The backup file doesn't exist.");
      }
    }

    function human_filesize($size, $precision = 2) //Returna el tamaño de un archivo en especifico
    {
      $units = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
      $step = 1024;
      $i = 0;
      while (($size / $step) > 0.9) {
        $size = $size / $step;
        $i++;
      }
      return round($size, $precision).$units[$i];
    }
}
