@extends('config-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Nuevo gas')
@section('content')
  <form action="{{ route('gases.store') }}" method="post">
    {{ csrf_field() }}
    <div class="form-group">
      <label for="formGroupExampleInput">Nombre</label>
      <input type="text" class="form-control" name="name"  onkeyup="verifyExists(this,'gas',-1, {{ json_encode(url('/')) }});javascript:this.value=this.value.toUpperCase();" required>
      <div id="result"></div>
    </div>
    <div class="text-center">
      <button type="submit" id="btn-send" class="btn btn-primary btninter"><i class="fa fa-floppy-o fa-fw"></i> Guardar</button>
      <a href="{{ route('gases.index') }}" class="btn btn-danger btninter right"><i class="fa fa-times fa-fw"></i> Cancelar</a>
    </div>
  </form>
@endsection
@section('script')
  <script src="{!! asset('validations/validations.js') !!}"></script>
@endsection
