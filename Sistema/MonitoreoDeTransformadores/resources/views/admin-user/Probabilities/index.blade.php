@extends('admin-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Calculo de probabilidades')
@section('content')
  <form action="{{ route('probabilities.store') }}" method="post" class="form-horizontal" enctype="multipart/form-data">
    {{ csrf_field() }}
    <div class="form-group">
      <label class="control-label col-xs-2">Monitor (modelo): </label>
      <div class="col-xs-10">
        <select class="form-control" name="model">
          <option value="CALISTO 1">CALISTO 1</option>
          <option value="CALISTO 2">CALISTO 2</option>
          <option value="CALISTO 9">CALISTO 9</option>
          <option value="MHT410">MHT410</option>
          <option value="OPT100">OPT100</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="control-label col-xs-2">Registros: </label>
      <div class="col-xs-10">
        <input id="file" type="file" name="file" accept=".csv" required>
      </div>
    </div>
    <div class="col-md-12">
      <button id="load" type="submit" class="btn btn-primary center-block"><i class="fa fa-line-chart fa-fw"></i> Graficar</button>
    </div>
  </form>
@endsection
