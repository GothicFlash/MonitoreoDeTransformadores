@extends('admin-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Importar registros')
@section('content')
  <form action="{{ route('imports.store') }}" method="post" class="form-horizontal" enctype="multipart/form-data">
    {{ csrf_field() }}
    <div class="form-group">
      <label class="control-label col-xs-2">Monitor: </label>
      <div class="col-xs-10">
        <select class="form-control" id="monitors" name="monitor">
          @foreach ($monitors as $monitor)
            <option value="{{ $monitor->id }}">{{ $monitor->model }} Nodo ({{ $monitor->node }})</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="control-label col-xs-2">Registros: </label>
      <div class="col-xs-10">
        <input id="file" type="file" name="file" accept=".csv" required>
      </div>
    </div>
    <div id="load_content" class="form-group">
      <label class="control-label col-xs-2"></label>
      <div class="col-xs-10">
        <div class="progress">
          <div class="percent progress-bar" role="progressbar">0%</div>
        </div>
      </div>
    </div>
    <div id="loading" style="display: none;" class="col-xs-12">
      <img src="{{ asset('images/load.gif') }}" class="center-block" width="8%">
    </div>
    <div class="col-md-12">
      <button id="load" type="submit" class="btn btn-primary center-block"><i class="fa fa-upload fa-fw"></i> Subir</button>
    </div>
  </form>
@endsection
@section('script')
  <script src="{!! asset('functions/functions.js') !!}"></script>
  <script>
    (function()
    {
      var percent = $('.percent');
      var loading = $('#loading');
      var content = $('#load_content');
      var button = $('#load');

      $('form').ajaxForm({
        beforeSend: function() {
        var percentVal = '0%';
        percent.css("width", percentVal);
        percent.html(percentVal);
      },
      uploadProgress: function(event, position, total, percentComplete) {
        var percentVal = percentComplete + '%';
        percent.css("width", percentVal);
        percent.html(percentVal);
        if(percentComplete == 100){
          content.hide();
          loading.show();
          button.attr("disabled", true);
        }
      },
      success: function() {
        var percentVal = '100%';
        percent.css("width", percentVal);
        percent.html(percentVal);
      },
      complete: function(xhr) {
        var type = "error";
        var title = "Oops!";
        var message = "Hubo un problema al procesar la acción";
        loading.hide();
        switch (xhr.responseText) {
          case '0':
            title = "Oops!";
            message = "El numero de gases en los registros no coincide con los gases del monitor";
            type = "error";
            break;
          case '-1':
            title = "Oops!";
            message = "Error al leer el archivo";
            type = "error";
            break;
          case '1':
            title = "Registros cargados";
            message = "Los registros han sido cargados correctamente";
            type = "success";
            break;
          case '3':
            title = "Oops!";
            message = "El archivo no existe o ha sido eliminado";
            type = "error";
            break;
          case '2':
            type = "error";
            title = "Oops!";
            message = "Hubo un problema al procesar la acción, verifique que el archivo no haya sido modificado";
            break;
        }
        swal(title,message,type).then(
          function() {
            percent.css("width","0%");
            percent.html("0%");
            document.getElementById("file").value = '';
            button.attr("disabled", false);
          }
        );
      }
    });
    })();
  </script>
@endsection
