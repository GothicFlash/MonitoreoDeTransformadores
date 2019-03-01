@extends('config-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Editar usuario')
@section('content')
  <form action="{{ route('users.update',$user->id) }}" method="post">
    {{ csrf_field() }}
    {{ method_field('PATCH') }}
    <div class="form-group">
      <label for="formGroupExampleInput">Nombre</label>
      <input type="text" class="form-control" name="name" value="{{ $user->name }}" required>
    </div>
    <div class="form-group">
      <label for="formGroupExampleInput">Email</label>
      <input type="email" class="form-control" name="email" value="{{ $user->email }}" onkeyup="verifyExists(this,'user', '{{ $user->email }}', {{ json_encode(url('/')) }})" required>
      <div id="result"></div>
    </div>
    <div class="form-group">
      <label for="formGroupExampleInput">Tipo</label>
      <select class="form-control" name="type">
        @if($user->type =='user')
          <option value="user" selected>Usuario</option>
          <option value="admin">Administrador</option>
          <option value="config">Configuración</option>
        @else
          @if($user->type == 'admin')
            <option value="user">Usuario</option>
            <option value="admin" selected>Administrador</option>
            <option value="config">Configuración</option>
          @else
            <option value="user">Usuario</option>
            <option value="admin">Administrador</option>
            <option value="config" selected>Configuración</option>
          @endif
        @endif
      </select>
    </div>
    <div class="form-group">
      <label for="formGroupExampleInput">Contraseña</label>
      <input type="password" class="form-control" name="password" value="{{ Crypt::decrypt($user->encrypted_password) }}" id="pass">
      <input type="checkbox" name="vehicle1" value="Bike" id="show-pass" onclick="showPassword()"> Mostrar contraseña<br>
    </div>
    <div class="text-center">
      <button id="btn-send" type="submit" class="btn btn-primary btninter"><i class="fa fa-pencil-square-o fa-fw"></i> Modificar</button>
      <a href="{{ route('users.index') }}" class="btn btn-danger btninter right"><i class="fa fa-times fa-fw"></i> Cancelar</a>
    </div>
  </form>
@endsection
@section('script')
  <script src="{!! asset('validations/validations.js') !!}"></script>
@endsection
