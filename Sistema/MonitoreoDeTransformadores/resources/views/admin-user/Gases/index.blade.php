@extends('admin-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Gases')
@section('content')
@if (count($gases) > 0)
  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-default">
        <div class="panel-body">
          <table width="100%" class="table table-striped table-bordered table-hover" id="gases">
            <thead>
              <tr>
                <th>Nombre</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($gases as $gas)
                <tr>
                  <td>{{ $gas->name }}</td>
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
    $('#gases').DataTable({
      responsive: true
    });
  });
</script>
<script src="{!! asset('functions/functions.js') !!}"></script>
@endsection
