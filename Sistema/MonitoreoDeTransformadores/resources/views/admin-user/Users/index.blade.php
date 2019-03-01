@extends('admin-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Usuarios')
@section('content')
@if (count($users) > 0 && $users->where('name','!=','MasterConfig')->count() > 0)
  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-default">
        <div class="panel-body">
          <table width="100%" class="table table-striped table-bordered table-hover" id="dataTables-example">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Tipo</th>
                <th></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach ($users as $user)
                @if ($user->name != "MasterConfig" && $user->type != "config")
                  <tr id="user-{{ $user->id }}">
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                      @if ($user->type == 'user')
                        Usuario
                      @else
                        @if ($user->type == 'admin')
                          Administrador
                        @endif
                      @endif
                    </td>
                    <td style="text-align:center;">
                      <a href="{{ route('users.edit',$user) }}" type="button" class="btn btn-primary"><i class="fa fa fa-pencil fa-fw"></i> Editar</a>
                    </td>
                    <td style="text-align:center;">
                      <a href="" type="button" data-id="{{ $user->id }}" class="button btn btn-danger"><i class="fa fa-trash fa-fw"></i> Eliminar</a>
                    </td>
                  </tr>
                @endif
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@else
  <div class="alert alert-info alert-block">
    <strong> No hay usuarios que mostrar </strong>
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
<script>
  $(document).on('click', '.button', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    swal({
      title: "Confirmar borrado",
      text: "¿Estas seguro de eliminar al usuario?",
      icon: "warning",
      buttons: ["Cancelar", "Continuar"],
      dangerMode: true,
    }).then(
      function(isConfirm){
        if(isConfirm){
          $.ajax({
            type: "POST",
            url: "{{ route('destroy-user') }}",
            data: {id: id, _token: '{{csrf_token()}}'},
            success: function (data) {
              swal("Eliminando!", "El usuario ha sido eliminado.", "success");
              $("#user-"+id).remove();
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
