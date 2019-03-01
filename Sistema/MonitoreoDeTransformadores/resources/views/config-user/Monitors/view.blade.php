@extends('config-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Detalles de monitor')
@section('content')
@if ($state)
  <div class="alert alert-warning alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <strong>Advertencia!</strong> La probabilidad está fuera de los normal, el transformador necesita ser revisado.
  </div>
@endif
<div class="well">
  <h4>Transformador: {{ $transformer->name }}</h4>
  <h4>{{ $monitor->model }}: {{ $gases }}</h4>
  <h4>Transformadores: </h4>
  <div class="btn-group">
    @foreach ($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get() as $transformer)
      <a href="{{ url('monitor',['monitor' => $monitor->id, 'transformer' => $transformer->id]) }}" onclick="" class="btn btn-info"> {{ $transformer->name }} </a>
    @endforeach
  </div>
</div>

<div class="btn-group">
  <a type="button" href="{{ url('monitor',['monitor' => $monitor->id, 'transformer' => $transformer->id]) }}" class="btn btn-primary">Probabilidad de falla</a>
  <a type="button" href="{{ url('monitor-gases', ['monitor' => $monitor->id, 'transformer' => $transformer->id]) }}" class="btn btn-primary">Gases</a>
</div><br><br>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header well well-sm">
        <i class="fa fa fa-bar-chart fa-fw"></i>
        <div class="btn-group">
          <button type="button" onclick="getGraphicByProbability({{ $monitor->id }}, {{ $idTransformer }}, 'month')" class="btn btn-default">Mes</button>
          <button type="button" onclick="getGraphicByProbability({{ $monitor->id }}, {{ $idTransformer }}, 'year')" class="btn btn-default">Año</button>
          <button type="button" onclick="getGraphicByProbability({{ $monitor->id }}, {{ $idTransformer }}, 'all')" class="btn btn-default">Todo</button>
        </div>
      </div>
      <div class="card-body">
        <canvas id="canvas"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-header well well-sm">
        Probabilidad de falla en el transformador
      </div>
      <div class="card-body" id="value-probability"></div>
      <a id="show-details" onclick="showDetails()" class="btn btn-success center-block">Ver detalles</a>
    </div>
  </div>
</div>
<br>
<!-- Create a table dinamically -->
<div id="table" class="well" style="display: none; overflow: scroll; height: 200px; font-size:18px;"></div>
@endsection
@section('script')
  <script src="{!! asset('graphics/graphics.js') !!}"></script>
  <script src="{!! asset('functions/functions.js') !!}"></script>
  <script>getGraphicByProbability({{ $monitor->id }}, {{ $idTransformer }}, "month", "{{ $monitor->model }}");</script>
@endsection
