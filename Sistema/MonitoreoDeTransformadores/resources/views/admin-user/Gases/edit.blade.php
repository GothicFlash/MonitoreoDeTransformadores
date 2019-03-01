@extends('admin-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Editar gas')
@section('content')
  <form action="{{ route('gases.update',$gas->id) }}" method="post">
    {{ csrf_field() }}
    {{ method_field('PATCH') }}
    <div class="form-group">
      <label for="formGroupExampleInput">Nombre</label>
      <input type="text" class="form-control" id="formGroupExampleInput" name="name" value="{{ $gas->name }}" onkeyup="verifyExists(this,'gas','{{ $gas->name }}', {{ json_encode(url('/')) }})" required>
      <div id="result"></div>
    </div>
    <div class="text-center">
      <button id="btn-send" type="submit" class="btn btn-primary btninter"><i class="fa fa-pencil-square-o fa-fw"></i> Modificar</button>
      <a href="{{ route('gases.index') }}" class="btn btn-danger btninter right"><i class="fa fa-times fa-fw"></i> Cancelar</a>
    </div>
  </form>
@endsection
@section('script')
  <script src="{!! asset('validations/validations.js') !!}"></script>
@endsection
