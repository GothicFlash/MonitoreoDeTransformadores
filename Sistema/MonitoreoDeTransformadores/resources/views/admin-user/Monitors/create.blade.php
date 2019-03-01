@extends('admin-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Nuevo monitor')
@section('content')
  <form action="{{ route('monitors.store') }}" method="post" enctype="multipart/form-data">
    {{ csrf_field() }}
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
              <label for="formGroupExampleInput">Nombre</label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="form-group">
              <label for="formGroupExampleInput">Modelo</label>
              <select class="form-control" name="model" id="models" onchange="changeGases()">
                <option value="CALISTO 1">CALISTO 1</option>
                <option value="CALISTO 2">CALISTO 2</option>
                <option value="CALISTO 9">CALISTO 9</option>
                <option value="MHT410">MHT410</option>
                <option value="OPT100">OPT100</option>
              </select>
            </div>
            <div class="form-group">
              <label for="formGroupExampleInput">Nodo</label>
              <input type="number" class="form-control" name="node" min="1" max="255" onclick="verifyExists(this,'monitor',-1, {{ json_encode(url('/')) }})" onkeyup="verifyExists(this,'monitor',-1, {{ json_encode(url('/')) }})" required>
              <div id="result"></div>
            </div>
            <div class="form-group">
              <label for="formGroupExampleInput">Gases</label><br>
              <input type="text" id="gases" class="form-control" value="H2, WC" readonly onmousedown="return false;">
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="text-center">
      <button type="submit" class="btn btn-primary btninter" id="btn-send"><i class="fa fa-floppy-o fa-fw"></i> Guardar</button>
      <a href="{{ route('home') }}" class="btn btn-danger btninter right"><i class="fa fa-times fa-fw"></i> Cancelar</a>
    </div>
  </form>
@endsection
@section('script')
  <script src="{!! asset('functions/functions.js') !!}"></script>
  <script src="{!! asset('validations/validations.js') !!}"></script>
@endsection
