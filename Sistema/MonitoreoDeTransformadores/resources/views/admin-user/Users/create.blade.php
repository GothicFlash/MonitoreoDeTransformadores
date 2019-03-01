@extends('admin-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Nuevo usuario')
@section('content')
  <form action="{{ route('users.store') }}" method="post">
    {{ csrf_field() }}
    <div class="form-group">
      <label for="formGroupExampleInput">Nombre</label>
      <input type="text" class="form-control" name="name" required>
    </div>
    <div class="form-group">
      <label for="formGroupExampleInput">Email</label>
      <input type="email" class="form-control" name="email" onkeyup="verifyExists(this,'user', -1, {{ json_encode(url('/')) }})" required>
      <div id="result"></div>
    </div>
    <div class="form-group">
      <label for="formGroupExampleInput">Tipo</label>
      <select class="form-control" name="type">
        <option value="user">Usuario</option>
        <option value="admin">Administrador</option>
      </select>
    </div>
    <div class="form-group">
      <label for="formGroupExampleInput">Contraseña</label>
      <input type="password" class="form-control" name="password" minlength="8">
    </div>
    <div class="text-center">
      <button type="submit" class="btn btn-primary btninter" id="btn-send"><i class="fa fa-floppy-o fa-fw"></i> Guardar</button>
      <a href="{{ route('users.index') }}" class="btn btn-danger btninter right"><i class="fa fa-times fa-fw"></i> Cancelar</a>
    </div>
  </form>
@endsection
@section('script')
  <script src="{!! asset('validations/validations.js') !!}"></script>
@endsection
