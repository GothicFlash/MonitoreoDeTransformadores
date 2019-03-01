@extends('admin-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Detalles de monitor')
@section('content')
  <div class="well">
    <h4>Transformador: {{ $monitor->name }} ({{ $idTransformer }})</h4>
    <h4>{{ $monitor->model }}: @foreach ($monitor->gases as $gas) {{ $gas->name }},  @endforeach</h4>
    <h4>Transformadores: </h4>
    <div class="btn-group">
      @foreach ($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get() as $transformer)
        <a href="{{ url('monitor-gases', ['monitor' => $monitor->id, 'transformer' => $transformer->id]) }}" onclick="" class="btn btn-info"> Transformador {{ $transformer->id }} </a>
      @endforeach
    </div>
  </div>

  <div class="btn-group">
    <a type="button" href="{{ url('monitor',['monitor' => $monitor->id, 'transformer' => $idTransformer]) }}" class="btn btn-primary">Probabilidad de falla</a>
    <a type="button" href="{{ url('monitor-gases', ['monitor' => $monitor->id, 'transformer' => $idTransformer]) }}" class="btn btn-primary">Gases</a>
  </div><br><br>

<div class="row">
@foreach ($monitor->gases as $gas)
  <div class="col-md-6">
    <div class="card">
      <div class="card-header well well-sm">
        <i class="fa fa fa-bar-chart fa-fw"></i>
        <div class="btn-group">
          <button type="button" onclick="getGasesBy({{ $monitor->id }},{{ $idTransformer }},'day',{{ $gas->id }}, '{{ $gas->name }}')" class="btn btn-default">Dia</button>
          <button type="button" onclick="getGasesBy({{ $monitor->id }},{{ $idTransformer }},'month',{{ $gas->id }}, '{{ $gas->name }}')" class="btn btn-default">Mes</button>
          <button type="button" onclick="getGasesBy({{ $monitor->id }},{{ $idTransformer }},'year',{{ $gas->id }}, '{{ $gas->name }}')" class="btn btn-default">Año</button>
          <button type="button" onclick="getGasesBy({{ $monitor->id }},{{ $idTransformer }},'all',{{ $gas->id }}, '{{ $gas->name }}')" class="btn btn-default">Todo</button>
        </div>
      </div>
      <div class="card-body" id="container-canvas-{{ $gas->id }}">
        <canvas id="canvas-gas-{{ $gas->id }}"></canvas>
      </div>
    </div>
  </div>
@endforeach
</div>
@endsection
@section('script')
  <script src="{!! asset('graphics/graphics.js') !!}"></script>
  <script>graphicGases({{ $idTransformer }})</script>
@endsection
