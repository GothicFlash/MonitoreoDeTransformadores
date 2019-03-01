@extends('default-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Detalles del monitor')
@section('content')
<div class="panel panel-default">
  <div class="panel-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label for="formGroupExampleInput">Imagen</label>
          @if ($monitor->image == NULL)
            <img src="{{ asset('images/unavailable.jpg') }}" width="80%" height="" class="img-responsive center-block">
          @else
            <img src="{{ asset('images/Monitors/'.$monitor->image->file)}}" width="80%" height="" class="img-responsive center-block"/>
          @endif
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label for="formGroupExampleInput">Modelo</label>
          <input type="text" class="form-control" value="{{ $monitor->model }}" readonly onmousedown="return false;">
        </div>
        <div class="form-group">
          <label for="formGroupExampleInput">No. serie</label>
          <input type="text" class="form-control" value="{{ $monitor->node }}" readonly onmousedown="return false;">
        </div>
        <div class="form-group">
          <label for="formGroupExampleInput">Gases</label>
          <input type="text" class="form-control" value="{{ $gases }}" readonly onmousedown="return false;">
        </div>
        <div class="form-group">
          <label for="formGroupExampleInput">Monitoreo actual</label>
          <input type="text" class="form-control" value="{{ $monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->name }}" readonly onmousedown="return false;">
        </div>
        <div class="form-group">
          <label for="formGroupExampleInput">Transformadores ligados</label>
          <input type="text" class="form-control" value="{{ $transformers }}" readonly onmousedown="return false;">
        </div>
      </div>
    </div>
  </div>
</div>
<div class="text-center">
  <a href="{{ route('home') }}" class="btn btn-primary"><i class="fa fa-arrow-left fa-fw"></i> Regresar</a>
</div>
@endsection
@section('script')
  <script src="{!! asset('functions/functions.js') !!}"></script>
@endsection
