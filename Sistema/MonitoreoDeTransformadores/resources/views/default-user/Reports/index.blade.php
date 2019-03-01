@extends('default-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Reportes')
@section('content')
  @if ($monitors->first()->transformers->first()->registers->count()>0)
    <form action="{{ route('create-pdf') }}" method="post" class="form-horizontal">
      {{ csrf_field() }}
      <div class="form-group">
        <label class="control-label col-xs-2">Monitor: </label>
        <div class="col-xs-10">
          <select class="form-control" id="monitors" name="monitors" onchange="changeTransformer()">
            @foreach ($monitors as $monitor)
              @if ($monitor->transformers()->orderBy('pivot_created_at', 'asc')->get()->first()->registers->count()>0)
                <option value="{{ $monitor->id }}">{{ $monitor->model }} Nodo ({{ $monitor->node }})</option>
              @endif
            @endforeach
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="control-label col-xs-2">Transformador: </label>
        <div class="col-xs-10">
          <select class="form-control" id="transformers" name="transformers">
            @if ($monitor->first()->transformers->first()->registers->count()>0)
              @foreach ($monitors->first()->transformers as $transformer)
                @if ($transformer->registers->count() != 0)
                  <option value="{{ $transformer->id }}">{{ $transformer->name }}</option>
                @endif
              @endforeach
            @endif
          </select>
        </div>
      </div>
      <div class="col-md-12">
        <button type="submit" class="btn btn-primary center-block"><i class="fa fa-file-pdf-o fa-fw"></i> Generar</button>
      </div>
    </form>
  @else
    <div class="alert alert-warning"> <strong>Lo sentimos!</strong> No hay bases reportes generar</div>
  @endif
@endsection
@section('script')
  <script src="{!! asset('functions/functions.js') !!}"></script>
@endsection
