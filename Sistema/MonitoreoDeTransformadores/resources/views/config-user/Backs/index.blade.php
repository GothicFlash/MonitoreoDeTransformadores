@extends('config-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Respaldos')
@section('content')
<div class="col-xs-12 clearfix">
  <a href="{{ route('backs.create') }}" class="btn btn-primary pull-right" style="margin-bottom:2em;"><i class="fa fa-plus"></i> Crear nuevo respaldo</a>
</div>
@if (sizeof($backups) > 0)
  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-default">
        <div class="panel-body">
          <table width="100%" class="table table-striped table-bordered table-hover" id="backups">
            <thead>
              <tr>
                <th>Archivo</th>
                <th>Tamaño</th>
                <th>Fecha</th>
                <th></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @for ($i=sizeof($backups)-1; $i >= 0; $i--)
                <tr id="backup-{{ $i }}">
                  <td>{{ $backups[$i]["file_name"] }}</td>
                  <td>{{ $backups[$i]["file_size"] }}</td>
                  <td>{{ $backups[$i]['last_modified'] }}</td>
                  <td style="text-align:center;">
                    <a href="{{ route('download-db',$backups[$i]['file_name']) }}" type="button" class="btn btn-primary"><i class="fa fa-download fa-fw"></i> Descargar</a>
                  </td>
                  <td style="text-align:center;">
                    <a id="delete-btn" data-id="{{ $i }}" href="{{ route('delete-db',$backups[$i]['file_name']) }}" type="button" class="btn btn-danger"><i class="fa fa-trash fa-fw"></i> Eliminar</a>
                  </td>
                </tr>
              @endfor
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@else
  <div class="col-xs-12 clearfix">
    <div class="alert alert-info alert-block">
      <strong> No hay respaldos que mostrar </strong>
    </div>
  </div>
@endif
@endsection

@section('script')
<script>
  $(document).ready(function() {
    $('#backups').DataTable({
      responsive: true
    });
  });
</script>
<script>
  var deleter = {
    linkSelector : "a#delete-btn",
    init: function() {
      $(this.linkSelector).on('click', {self:this}, this.handleClick);
    },
    handleClick: function(event) {
      event.preventDefault();
      var id = $(this).data('id');
      var self = event.data.self;
      var link = $(this);

      swal({
        title: "Confirmar borrado",
        text: "¿Estas seguro de eliminar el respaldo?",
        icon: "warning",
        buttons: ["Cancelar", "Continuar"],
        dangerMode: true,
      }).then(
        function(isConfirm){
          if(isConfirm){
            console.log(link.attr('href'));
            $.ajax({
              type: "GET",
              url: link.attr('href'),
              success: function () {
                swal("Eliminando!", "El respaldo ha sido eliminado.", "success");
                $("#backup-"+id).remove();
              }
            });
          }
        });
      },
    };
    deleter.init();
</script>
@endsection
