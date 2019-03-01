@extends('admin-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Monitores')
@section('content')
@if(isset($error))
  <div class="alert alert-warning alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <strong>Acceso restringido!</strong> {{ $error }}
  </div>
@endif
@if (count($monitors) > 0)
  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-default">
        <div class="panel-body">
          <table width="100%" class="table table-striped table-bordered table-hover" id="monitors-table">
            <thead>
              <tr>
                <th>Nodo</th>
                <th>Imagen</th>
                <th>Transformador</th>
                <th>Monitor</th>
                <th>Generar gráfica</th>
                <th>Base de datos</th>
                <th>Porcentaje de falla</th>
                <th>Mediciones</th>
                <th></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach ($monitors as $monitor)
                <tr id="monitor-{{ $monitor->id }}-delete">
                  <td>{{ $monitor->node }}</td>
                  <td>
                    @if ($monitor->image == NULL)
                      <img src="{{ asset('images/unavailable.jpg') }}" width="50%" height="" class="img-responsive center-block zoom">
                    @else
                      <img src="{{ asset('images/Monitors/'.$monitor->image->file)}}" width="50%" height="" class="img-responsive center-block zoom"/>
                    @endif
                  </td>
                  <td>{{ $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->name }}</td>
                  <td>{{ $monitor->model }}</td>
                  <td style="text-align:center;">
                    <a href="{{ url('monitor',['monitor' => $monitor->id, 'transformer' => $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->id]) }}" class="btn btn-primary"><i class="fa fa fa-bar-chart fa-fw"></i> Graficar</a>
                  </td>
                  <td style="text-align:center;">
                    <form action="{{ route('download-database') }}" method="post" class="form-horizontal">
                      {{ csrf_field() }}
                      <input type="hidden" name="monitor" value="{{ $monitor->id }}">
                      <button type="submit" class="btn btn-warning"><i class="fa fa fa-download fa-fw"></i> Descargar</button>
                    </form>
                  </td>
                  <td id="monitor-{{ $monitor->id }}"></td>
                  <td>
                    @if (count($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers) != 0)
                      @if (count($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->last()->gases) != 0)
                        Última fecha de medición: {{ $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->last()->date." ".$monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->last()->gases->last()->pivot->hour }}
                        <table class="table table-bordered">
                          <thead>
                            <tr>
                              <th>Gas</th>
                              <th>Valor</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach ($monitor->gases as $gas)
                              <tr>
                                <td>{{ $gas->name }}</td>
                                <td>
                                  @if ($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->last()->gases->where('name',$gas->name)->last() != NULL)
                                    {{ $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->last()->gases->where('name',$gas->name)->last()->pivot->ppm }}
                                  @else
                                    Sin registros
                                  @endif
                                </td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                      @endif
                    @else
                      <h4 class="text-dark text-center">Sin mediciones</h4>
                    @endif
                  </td>
                  <td style="text-align:center;">
                    <a href="{{ route('monitors.edit',$monitor) }}" type="button" class="btn btn-primary"><i class="fa fa fa-pencil fa-fw"></i> Editar</a>
                  </td>
                  <td style="text-align:center;">
                    <a href="" data-id="{{ $monitor->id }}" type="submit" class="button btn btn-danger"><i class="fa fa-trash fa-fw"></i> Eliminar</a>
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
    <strong> No hay monitores que mostrar </strong>
  </div>
@endif
@endsection

@section('script')
  <script src="{!! asset('graphics/graphics.js') !!}"></script>
  <script>
    $(document).ready(function() {
      $('#monitors-table').DataTable({
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
        text: "¿Estas seguro de eliminar el monitor?",
        icon: "warning",
        buttons: ["Cancelar", "Continuar"],
        dangerMode: true,
      }).then(
        function(isConfirm){
          if(isConfirm){
            $.ajax({
              type: "POST",
              url: "{{ route('destroy-monitor') }}",
              data: {id: id, _token: '{{csrf_token()}}'},
              success: function (data) {
                swal("Eliminando!", "El monitor ha sido eliminado.", "success");
                $("#monitor-"+id+"-delete").remove();
              }
            });
          }
      });
    });
  </script>
@endsection
