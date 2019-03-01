@extends('config-user.layouts.app')
@section('title','Confiamex')
@section('title-content','Tiempo de poleo en transformadores')
@section('content')
  <div class="form-group">
    <label for="formGroupExampleInput">Tiempo actual en minutos</label>
    <input type="number" id="time" value="{{ $time }}" min="1" max="180" class="form-control" required>
  </div>
  <div class="text-center">
    <a class="btn btn-primary btninter button"><i class="fa fa-floppy-o fa-fw"></i> Modificar</a>
  </div>
@endsection
@section('script')
  <script>
    $(document).on('click', '.button', function (e) {
      var time = document.getElementById("time").value;
      $.ajax({
        type: "POST",
        url: "{{ route('scans.store') }}",
        data: {time: time, _token: '{{csrf_token()}}'},
        success: function (data) {
          swal("Modificado!", "El tiempo de poleo ha sido cambiado.", "success");
        }
      });
    });
  </script>
@endsection
