<?php
Route::group(['middleware' => 'auth'], function()
{
  //Ruta principal del sistema
  Route::get('/home',[
    'uses' => 'Monitors\MonitorsController@index',
    'as' => 'home'
  ]);

  //Ruta para retornar el monitor cuando es buscado por url
  Route::bind('monitor', function($id){
    return App\Monitor::where('id',$id)->first();
  });

  //Ruta para mostrar los detales de un monitor en especifico
  Route::get('monitor/{monitor?}/{idTransformer?}',[
    'uses' => 'Monitors\MonitorsController@show',
    'as' => 'detail-monitor'
  ]);

  //Ruta para mostrar las mediciones de todos los gases de un monitor
  Route::get('monitor-gases/{monitor?}/{transformer?}',[
    'uses' => 'Monitors\MonitorsController@detailGases',
    'as' => 'detail-gases'
  ]);

  //--------------------------------Rutas individuales----------------------------------

  //Ruta para mostrar los gases de un monitor, esto se basa en las graficas
  Route::get('monitor-gases/{idTransformer}/getGasesByMonitor/{idMonitor?}','Graphics\GraphicsController@getGasesByMonitor');

  //Ruta para obtener los transformadores que corresponden a un monitor en especifico, es mediante AJAX
  Route::get('transformer/{idTransformer}','Graphics\GraphicsController@getTransformers');

  //Ruta para crear el archivo CSV
  Route::post('excel','Excel\ExcelController@exportCSV')->name('create-excel');

  //Ruta para crear el archivo CSV respetando el formato de confiamex
  Route::post('database','DataBases\DataBasesController@downloadDatabase')->name('download-database');

  //Ruta para descargar el archivo txt de notificaciones
  Route::get('notification','Notifications\NotificationsController@downloadNotifications')->name('download-notifications');

  //Rutas para mostrar las mediciones de los gases por diferentes parametros
  Route::get('monitor-gases/{idTransformer}/getGasesByDay/{idMonitor?}/{idGas?}', 'Graphics\GraphicsController@getGasesByDay');
  Route::get('monitor-gases/{idTransformer}/getGasesByMonth/{idMonitor?}/{idGas?}', 'Graphics\GraphicsController@getGasesByMonth');
  Route::get('monitor-gases/{idTransformer}/getGasesByYear/{idMonitor?}/{idGas?}', 'Graphics\GraphicsController@getGasesByYear');
  Route::get('monitor-gases/{idTransformer}/getGasesByAll/{idMonitor?}/{idGas?}', 'Graphics\GraphicsController@getGasesByAll');

  //Rutas para mostrar el cálculo de probabilidad de falla por diferentes parametros
  Route::get('monitor/{idTransformer?}/getMonthProbability/{idMonitor?}', 'Graphics\GraphicsController@getProbabilityBy');
  Route::get('monitor/{idTransformer?}/getYearProbability/{idMonitor?}', 'Graphics\GraphicsController@getYearProbability');
  Route::get('monitor/{idTransformer?}/getAllProbability/{idMonitor?}', 'Graphics\GraphicsController@getAllProbability');

  //Ruta para obtener los promedios de los gases de un monitor, esto para mostrar la tabla de detalles en la vista de cálculo de probabilidad de falla (boton graficar)
  Route::get('monitor/{idMonitor?}/getAverages/{idTransformer?}/{type?}', 'Graphics\GraphicsController@getAverages');

  //Ruta para mostrar las probabilidades de falla en la pagina principal
  Route::get('home/getProbabilities','Graphics\GraphicsController@getProbabilities');

  //Ruta para mostrar las ultimas mediciones de los gases
  Route::get('home/getLastMeasurements','Graphics\GraphicsController@getLastMeasurements');

  //Ruta para importar la información que es recibida del archivo CSV
  Route::get('import-information', 'Imports\ImportsController@loadInformation');

  //Ruta para verificar el estado de probabilidad de falla y lanzar notificaciones
  Route::get('scan-probabilities', 'Graphics\GraphicsController@checkProbabilities');

  //Ruta para obtener los transformadores compatibles con un modelo de monitor en especifico
  Route::get('validate-transformers/{model?}', 'Graphics\GraphicsController@getActiveTransformers');

  //Ruta para crear (descargar) un PDF
  Route::post('pdf','Reports\ReportsController@generatePDF')->name('create-pdf');

  //Ruta para mostrar los detalles de un monitor en especifico, es solo para visualizar
  Route::get('monitor-detail/{monitor}',[
    'uses' => 'Monitors\MonitorsController@showDescription',
    'as' => 'showMonitor'
  ]);

  //Rutas generales para los diferentes controladores
  Route::resource('gases','Gases\GasesController');
  Route::resource('monitors','Monitors\MonitorsController');
  Route::resource('databases','DataBases\DataBasesController');
  Route::resource('reports','Reports\ReportsController');
  Route::resource('scans','Scans\ScansController');
  Route::resource('notifications','Notifications\NotificationsController');

  Route::group(['middleware' => ['admin' OR 'config']], function()
  {
    //Rutas para verificar si existe un atributo en especifico de algun modelo en la base de datos
    Route::get('gases/gas-find/{name?}/{id?}','Gases\GasesController@exists');
    Route::get('users/user-find/{email?}/{id?}','Users\UsersController@exists');
    Route::get('monitors/monitor-find/{node?}/{id?}','Monitors\MonitorsController@exists');
    Route::get('gases/gas-find/{name?}/{id?}','Gases\GasesController@exists');
    Route::get('users/user-find/{email?}/{id?}','Users\UsersController@exists');

    //Rutas generales para los diferentes controladores
    Route::resource('users','Users\UsersController');
    Route::resource('imports','Imports\ImportsController');
    Route::resource('probabilities','Probabilities\ProbabilitiesController');

    //Rutas para eliminar diferentes modelos de la base de datos
    Route::post('destroy-monitor','Monitors\MonitorsController@destroy')->name('destroy-monitor');
    Route::post('destroy-gas','Gases\GasesController@destroy')->name('destroy-gas');
    Route::post('destroy-user','Users\UsersController@destroy')->name('destroy-user');
  });

  Route::group(['middleware' => 'config'], function()
  {
    //Rutas para las diferentes acciones acerca de la base de datos
    Route::get('delete/{file_name}',[
      'uses' => 'Backs\BacksController@delete',
      'as' => 'delete-db'
    ]);

    Route::get('download/{file_name}',[
      'uses' => 'Backs\BacksController@download',
      'as' => 'download-db'
    ]);

    //Ruta para eliminar notificaciones
    Route::post('destroy-notification','Notifications\NotificationsController@destroy')->name('destroy-notification');

    //Ruta general para crear respaldos en la base de datos
    Route::resource('backs','Backs\BacksController');
  });
});

//Rutas para referentes al login y cierre de sesión
Route::get('/','Auth\LoginController@showLoginForm')->middleware('guest');
Route::post('login','Auth\LoginController@login')->name('login');
Route::get('logout','Auth\LoginController@logout')->name('logout');
