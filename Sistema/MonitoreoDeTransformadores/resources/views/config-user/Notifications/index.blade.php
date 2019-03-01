@extends('config-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Notificaciones')
@section('content')
@if (count($notifications) > 0)
  <div class="col-xs-12 clearfix">
    <a href="{{ route('download-notifications') }}" class="btn btn-primary pull-right" style="margin-bottom:2em;"><i class="fa fa-download"></i> Descargar notificaciones</a>
  </div>
  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-default">
        <div class="panel-body">
          <table width="100%" class="table table-striped table-bordered table-hover" id="notifications-table">
            <thead>
              <tr>
                <th>Fecha y hora</th>
                <th>Descripción</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($notifications as $notification)
                <tr id="notification-{{ $notification->id }}-delete">
                  <td>{{ $notification->date.' '.$notification->hour }}</td>
                  <td>{{ $notification->description }}</td>
                  <td style="text-align:center;">
                    <a href="" data-id="{{ $notification->id }}" type="submit" class="button btn btn-danger"><i class="fa fa-trash fa-fw"></i> Eliminar</a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@else
  <div class="alert alert-info alert-block">
  	<strong> No hay notificaciones que mostrar </strong>
  </div>
@endif
@endsection
@section('script')
  <script>
    $(document).ready(function() {
        $('#notifications-table').DataTable({
            responsive: true
        });
    });
  </script>
  <script>
    $(document).on('click', '.button', function (e) {
      e.preventDefault();
      var id = $(this).data('id');
      swal({
        title: "Confirmar borrado",
        text: "¿Estas seguro de eliminar la notificación?",
        icon: "warning",
        buttons: ["Cancelar", "Continuar"],
        dangerMode: true,
      }).then(
        function(isConfirm){
          if(isConfirm){
            $.ajax({
              type: "POST",
              url: "{{ route('destroy-notification') }}",
              data: {id: id, _token: '{{csrf_token()}}'},
              success: function (data) {
                swal("Eliminando!", "La notificación ha sido eliminada.", "success");
                $("#notification-"+id+"-delete").remove();
              }
            });
          }
      });
    });
  </script>
@endsection
