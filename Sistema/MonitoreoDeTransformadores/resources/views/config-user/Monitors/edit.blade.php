@extends('config-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Editar monitor')
@section('content')
  <form action="{{ route('monitors.update',$monitor->id) }}" method="post" enctype="multipart/form-data">
    {{ csrf_field() }}
    {{ method_field('PATCH') }}
    <div class="panel panel-default">
      <div class="panel-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="formGroupExampleInput">Imagen</label>
              @if ($monitor->image == NULL)
                <img src="{{ asset('images/unavailable.jpg') }}" id="image" width="80%" height="" class="img-responsive center-block">
              @else
                <img src="{{ asset('images/Monitors/'.$monitor->image->file)}}" id="image" width="80%" height="" class="img-responsive center-block"/>
              @endif
              <input type="file" class="custom-file-input" name="file" id="file" accept="image/x-png,image/gif,image/jpeg" onchange="changeImage(this)">
              <label class="custom-file-label" for="validatedCustomFile">Seleccionar imagen...</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="formGroupExampleInput">Modelo</label>
              <input type="text" class="form-control" name="model" value="{{ $monitor->model }}" readonly onmousedown="return false;">
            </div>
            <div class="form-group">
              <label for="formGroupExampleInput">Nodo</label>
              <input type="number" class="form-control" name="node" value="{{ $monitor->node }}" min="1" max="255" onclick="verifyExists(this,'monitor',{{ $monitor->node }}, {{ json_encode(url('/')) }})" onkeyup="verifyExists(this,'monitor',{{ $monitor->node }}, {{ json_encode(url('/')) }})" required>
              <div id="result"></div>
            </div>
            <div class="form-group">
              <label for="formGroupExampleInput">Gases</label>
              <input type="text" class="form-control" name="gases" value="{{ $gases }}" readonly onmousedown="return false;">
            </div>
            <div class="form-group">
              <label for="formGroupExampleInput">Monitoreo actual</label>
              <input type="text" class="form-control" name="current" value="{{ $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->name }}" readonly onmousedown="return false;">
            </div>
            <div class="form-group">
              <label for="formGroupExampleInput">Ligar transformadores</label><br>
              <input type="hidden" name="current_transformer" value="{{ $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->id }}">
              <select id="transformers" class="form-control" name="transformers[]" multiple>
                @foreach ($transformers as $transformer)
                  @if ($transformer != $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->id)
                    @if ($monitor->transformers->find($transformer) != NULL)
                      <option value="{{ $transformer }}" selected>{{ $transformersNames->find($transformer)->name }}</option>
                    @else
                      <option value="{{ $transformer }}" >{{ $transformersNames->find($transformer)->name }}</option>
                    @endif
                  @endif
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="text-center">
      <button type="submit" class="btn btn-primary btninter"><i class="fa fa-pencil-square-o fa-fw"></i> Modificar</button>
      <a href="{{ route('home') }}" class="btn btn-danger btninter right"><i class="fa fa-times fa-fw"></i> Cancelar</a>
    </div>
    <br><br>
  </form>
@endsection
@section('script')
  <script type="text/javascript">
    $(document).ready(function() {
        $('#transformers').multiselect();
    });
  </script>
  <script src="{!! asset('functions/functions.js') !!}"></script>
  <script src="{!! asset('validations/validations.js') !!}"></script>
@endsection
