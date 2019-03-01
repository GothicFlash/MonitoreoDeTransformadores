@extends('config-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Nuevo monitor')
@section('content')
  <div class="text-center">
    <div class="btn-group">
      <button id="btn-standard" onclick="typeStore('standard')" class="btn btn-primary active">Standard</button>
      <button id="btn-personalized" onclick="typeStore('personalized')" class="btn btn-primary">Personalizado</button>
    </div>
  </div><br>
  <form action="{{ route('monitors.store') }}" method="post" enctype="multipart/form-data">
    {{ csrf_field() }}
    <input id="type" type="hidden" name="type" value="standard">
    <div class="panel panel-default">
      <div class="panel-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="formGroupExampleInput">Imagen</label>
              <img src="{{ asset('images/unavailable.jpg') }}" id="image" width="80%"  class="img-responsive center-block">
              <input type="file" class="custom-file-input" id="file" name="file" accept="image/x-png,image/gif,image/jpeg" onchange="changeImage(this)">
              <label class="custom-file-label" for="validatedCustomFile">Seleccionar imagen...</label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="formGroupExampleInput">Transformador</label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="form-group" id="div-model">
              <label for="formGroupExampleInput">Modelo</label>
              <select class="form-control" name="model" id="models" onchange="changeGases();">
                <option value="CALISTO 1">CALISTO 1</option>
                <option value="CALISTO 2">CALISTO 2</option>
                <option value="CALISTO 9">CALISTO 9</option>
                <option value="MHT410">MHT410</option>
                <option value="OPT100">OPT100</option>
              </select>
            </div>
            <div class="form-group" id="div-model-unique" style="display: none">
              <label for="formGroupExampleInput">Modelo</label>
              <input id="model-text" type="text" name="modelPersonalized" class="form-control">
            </div>
            <div class="form-group">
              <label for="formGroupExampleInput">Nodo</label>
              <input type="number" class="form-control" name="node" min="1" max="255" onclick="verifyExists(this,'monitor',-1, {{ json_encode(url('/')) }})" onkeyup="verifyExists(this,'monitor',-1, {{ json_encode(url('/')) }})" required>
              <div id="result"></div>
            </div>
            <div class="form-group" id="div-static-gases">
              <label for="formGroupExampleInput">Gases</label><br>
              <input type="text" id="gases" class="form-control" value="H2, WC" readonly onmousedown="return false;">
            </div>

            <div class="form-group" id="div-multiple-gases" style="display: none">
              <label for="formGroupExampleInput">Gases</label><br>
              <select id="multiple-gases" class="form-control" name="gases[]" multiple onchange="showMethods()">
                @foreach ($gases as $gas)
                  <option value="{{ $gas->id }}">{{ $gas->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group" id="div-methods" style="display: none">
              <label for="formGroupExampleInput">Método de probabilidad</label>
              <select class="form-control" name="model" id="methods">

              </select>
              <div id="resultMethods"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="text-center">
      <button type="submit" class="btn btn-primary btninter" id="btn-send"><i class="fa fa-floppy-o fa-fw"></i> Guardar</button>
      <a href="{{ route('home') }}" class="btn btn-danger btninter right"><i class="fa fa-times fa-fw"></i> Cancelar</a>
    </div>
  </form><br>
@endsection
@section('script')
  <script type="text/javascript">
    $(document).ready(function() {
        $('#multiple-gases').multiselect();
    });
  </script>
  <script src="{!! asset('functions/functions.js') !!}"></script>
  <script src="{!! asset('validations/validations.js') !!}"></script>
@endsection
