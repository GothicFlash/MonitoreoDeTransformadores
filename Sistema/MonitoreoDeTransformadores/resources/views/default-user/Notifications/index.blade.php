@extends('default-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Notificaciones')
@section('content')
@if (count($notifications) > 0)
  <div class="col-xs-12 clearfix">
    <a href="{{ route('download-notifications') }}" class="btn btn-primary pull-right" style="margin-bottom:2em;"><i class="fa fa-download"></i> Descargar notificaciones</a>
  </div>
  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-default">
        <div class="panel-body">
          <table width="100%" class="table table-striped table-bordered table-hover" id="notifications-table">
            <thead>
              <tr>
                <th>Fecha y hora</th>
                <th>Descripción</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($notifications as $notification)
                <tr id="notification-{{ $notification->id }}-delete">
                  <td>{{ $notification->date.' '.$notification->hour }}</td>
                  <td>{{ $notification->description }}</td>
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
  	<strong> No hay notificaciones que mostrar </strong>
  </div>
@endif
@endsection
