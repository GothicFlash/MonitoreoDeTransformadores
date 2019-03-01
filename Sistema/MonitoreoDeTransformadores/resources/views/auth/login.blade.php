<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logotipo-confiamex.png') }}" />
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Acceso al sistema</title>
    <!-- Bootstrap Core CSS -->
    <link href="{!! asset('bootstrap/css/bootstrap.min.css') !!}" rel="stylesheet">
    <!-- Style sheet login CSS-->
    <link href="{!! asset('auth/css/login.css') !!}" rel="stylesheet">
</head>
<body>
  <div class="container">
          <div class="card card-container">
              <!-- <img class="profile-img-card" src="//lh3.googleusercontent.com/-6V8xOA6M7BA/AAAAAAAAAAI/AAAAAAAAAAA/rzlHcD0KYwo/photo.jpg?sz=120" alt="" /> -->
              <img id="profile-img" class="img-responsive center-block" src="{{ asset('images/logotipo-confiamex.png') }}" />
              <p id="profile-name" class="profile-name-card"></p>
              <form class="form-signin" method="POST" action="{{ route('login') }}">
                {{ csrf_field() }}
                  <span id="reauth-email" class="reauth-email"></span>
                  <div class="form-group {{ $errors->has('email') ? 'has-error' : ''}}">
                    <input type="email" id="inputEmail" class="form-control" placeholder="Correo electrónico" value="{{ old('email') }}" name="email">
                    {!! $errors->first('email','<span class="help-block">:message</span>') !!}
                  </div>
                  <div class="form-group {{ $errors->has('password') ? 'has-error' : ''}}">
                    <input type="password" id="inputPassword" class="form-control" placeholder="Contraseña" name="password">
                    {!! $errors->first('password','<span class="help-block">:message</span>') !!}
                  </div>
                  <button class="btn btn-lg btn-primary btn-block btn-signin" type="submit">Acceder</button>
              </form><!-- /form -->
          </div><!-- /card-container -->
      </div><!-- /container -->
</body>
</html>
