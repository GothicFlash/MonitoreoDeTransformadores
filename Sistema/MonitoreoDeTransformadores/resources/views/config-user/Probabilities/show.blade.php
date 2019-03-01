@extends('config-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Probabilidad de falla')
@section('content')
<div class="well">
  <h4>{{ $model }}: {{ $gases }}</h4>
</div>
<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header well well-sm">
        <i class="fa fa fa-bar-chart fa-fw"></i>
        <div class="btn-group">
          <button type="button" onclick='getGraphicByProbability("month",{{ $monthProbability }},"{{ $dataMonth }}",{!! $yearProbability !!}, {!! $dataYear !!}, {!! $allProbability !!}, {!! $dataAll !!}, "{{ $model }}")' class="btn btn-default">Mes</button>
          <button type="button" onclick='getGraphicByProbability("year",{{ $monthProbability }},"{{ $dataMonth }}",{!! $yearProbability !!}, {!! $dataYear !!}, {!! $allProbability !!}, {!! $dataAll !!}, "{{ $model }}")' class="btn btn-default">Año</button>
          <button type="button" onclick='getGraphicByProbability("all",{{ $monthProbability }},"{{ $dataMonth }}",{!! $yearProbability !!}, {!! $dataYear !!}, {!! $allProbability !!}, {!! $dataAll !!}, "{{ $model }}")' class="btn btn-default">Todo</button>
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
        <h5>Probabilidad de falla en el transformador</h5>
      </div>
      <div class="card-body" id="value-probability"></div>
    </div>
  </div>
</div>
<br>
@endsection
@section('script')
  <script src="{!! asset('fileProbabilities/probabilities.js') !!}"></script>
  <script>getGraphicByProbability("month",{{ $monthProbability }},"{{ $dataMonth }}",{!! $yearProbability !!}, {!! $dataYear !!}, {!! $allProbability !!}, {!! $dataAll !!}, "{{ $model }}");</script>
@endsection
