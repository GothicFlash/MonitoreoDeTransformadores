@extends('config-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Gases')
@section('content')
@if (count($gases) > 0)
  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-default">
        <div class="panel-body">
          <table width="100%" class="table table-striped table-bordered table-hover" id="dataTables-example">
            <thead>
              <tr>
                <th>Id</th>
                <th>Nombre</th>
                <th></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach ($gases as $gas)
                <tr id="gas-{{ $gas->id }}">
                  <td>{{ $gas->id }}</td>
                  <td>{{ $gas->name }}</td>
                  <td style="text-align:center;">
                    <a {{ ($gas->monitors->isEmpty())?'':'disabled' }} href="{{ ($gas->monitors->isEmpty())?route('gases.edit',$gas):'#' }}" type="button" class="btn btn-primary" style="display:inline"><i class="fa fa fa-pencil fa-fw"></i> Editar</a>
                  </td>
                  <td style="text-align:center;">
                    <a href="" class="button btn btn-danger" data-id="{{ $gas->id }}"><i class="fa fa-trash fa-fw"></i> Eliminar</a>
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
    <strong> No hay gases que mostrar </strong>
  </div>
@endif
@endsection

@section('script')
<script>
  $(document).ready(function() {
    $('#dataTables-example').DataTable({
      responsive: true
    });
  });
</script>
<script src="{!! asset('functions/functions.js') !!}"></script>
<script>
  $(document).on('click', '.button', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    swal({
      title: "Confirmar borrado",
      text: "¿Estas seguro de eliminar el gas?",
      icon: "warning",
      buttons: ["Cancelar", "Continuar"],
      dangerMode: true,
    }).then(
      function(isConfirm){
        if(isConfirm){
          $.ajax({
            type: "POST",
            url: "{{ route('destroy-gas') }}",
            data: {id: id, _token: '{{csrf_token()}}'},
            success: function (data) {
              if(data.error.errorInfo === undefined){
                swal("Eliminando!", "El gas ha sido eliminado.", "success");
                $("#gas-"+id).remove();
              }else{
                swal("Oops!", "Este gas no puede ser eliminado", "warning");
              }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
              console.log(textStatus);
            }
          });
        }
    });
  });
</script>
@endsection
