<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/logotipo-confiamex.png') }}" />
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>@yield('title')</title>
    <!-- Bootstrap Core CSS -->
    <link href="{!! asset('bootstrap/css/bootstrap.min.css') !!}" rel="stylesheet">
    <!-- MetisMenu CSS -->
    <link href="{!! asset('metisMenu/metisMenu.min.css') !!}" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{!! asset('dist/css/sb-admin-2.css') !!}" rel="stylesheet">
    <!-- Morris Charts CSS -->
    <link href="{!! asset('morrisjs/morris.css') !!}" rel="stylesheet">
    <!-- Custom Fonts -->
    <link href="{!! asset('font-awesome/css/font-awesome.min.css') !!}" rel="stylesheet" type="text/css">
    <!-- DataTables CSS -->
    <link href="{!! asset('datatables-plugins/dataTables.bootstrap.css') !!}" rel="stylesheet">
    <!-- DataTables Responsive CSS -->
    <link href="{!! asset('datatables-responsive/dataTables.responsive.css') !!}" rel="stylesheet">
    <link rel="stylesheet" href="{!! asset('bootstrap-multiselect/css/bootstrap-multiselect.css') !!}" type="text/css"/>
    <!-- My own styles CSS -->
    <link href="{!! asset('mystyles/styles.css') !!}" rel="stylesheet">
    <!--SweetAlert CSS -->
    <link href="{!! asset('sweetalert/sweetalert.css') !!}" rel="stylesheet">
    <script type="text/javascript" src="{!! asset('jquery/jquery-latest.min.js') !!}"></script>
    <script type="text/javascript">
      $(document).ready(function() {
        function importData(){
          var url = "{{ url('/import-information') }}";
          $.get(url,function(resul){
            var data = jQuery.parseJSON(resul);
            console.log(data);
          })
        }

        function getProbabilities(){
          var url = window.location+"";
          var url2 = 'home/getProbabilities';
          if(url.indexOf("home") > -1){ //Verifica si esta en la pagina de inicio
            $.get(url2,function(resul){
              var datos= jQuery.parseJSON(resul);
              for(i=0;i<datos.probabilities.length;i++){
                if ($("#monitor-"+datos.monitors[i]).length) {
                  if(datos.probabilities[i] == -1){
                    document.getElementById("monitor-"+datos.monitors[i]).innerHTML = "<h4 class='text-dark text-center'>Sin registro</h4>";
                  }else{
                    document.getElementById("monitor-"+datos.monitors[i]).innerHTML = changeLabelColor(datos.monitors_models[i],datos.probabilities[i],0);
                  }
                }
              }
            })
          }
        }

        function latestMeasurements(){
          var url = window.location+"";
          var url2 = 'home/getLastMeasurements';
          if(url.indexOf("home") > -1){ //Verifica si esta en la pagina de inicio
            $.get(url2,function(resul){
              var datos= jQuery.parseJSON(resul);
              for(i=0;i<datos.information.length;i++){
                if ($("#latest-measurements-"+datos.monitorsId[i]).length) {
                  document.getElementById("latest-measurements-"+datos.monitorsId[i]).innerHTML = datos.information[i];
                }
              }
            })
          }
        }

        function notifications(){
          var url = "{{ url('/scan-probabilities') }}";
          $.get(url,function(resul){
            var data = jQuery.parseJSON(resul);
            if(data.length == 0){
              $('#notifications_count').html('');
              $('#notifications').html('Sin notificaciones recientes');
            }else{
              var labels = "";
              for (var i = 0; i < data.length; i++) {
                labels += "<li><i class='fa fa-exclamation-triangle fa-fw'></i> "+data[i].description+"</li>";
              }
              $('#notifications_count').html(data.length);
              $('#notifications').html(labels);
            }
          })
        }
        setInterval(notifications, 1000); //Read the currents notifications
        setInterval(importData, 10000); //Imports the information each 10 seconds
        setInterval(getProbabilities, 1000);
        setInterval(latestMeasurements, 10000);
      });
    </script>
</head>
<body>
    <div id="wrapper">
        <!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
            <div class="navbar-header">
              <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
              </button>
            </div>
            @include('default-user.partials.header')
            @include('default-user.partials.sidebar')
        </nav>
        <div id="page-wrapper">
          <div class="row">
              <div class="col-lg-12">
                  <h1 class="page-header">@yield('title-content')</h1>
              </div>
              <!-- /.col-lg-12 -->
          </div>
          @yield('content')
        </div>
        <!-- /#page-wrapper -->
    </div>
    <!-- /#wrapper -->
    <!-- jQuery -->
    <script src="{!! asset('jquery/jquery.min.js') !!}"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="{!! asset('bootstrap/js/bootstrap.min.js') !!}"></script>
    <!-- Metis Menu Plugin JavaScript -->
    <script src="{!! asset('metisMenu/metisMenu.min.js') !!}"></script>
    <!-- Morris Charts JavaScript -->
    <script src="{!! asset('raphael/raphael.min.js') !!}"></script>
    <script src="{!! asset('morrisjs/morris.min.js') !!}"></script>
    <!-- Custom Theme JavaScript -->
    <script src="{!! asset('dist/js/sb-admin-2.js') !!}"></script>
    <!-- DataTables JavaScript -->
    <script src="{!! asset('datatables/js/jquery.dataTables.min.js') !!}"></script>
    <script src="{!! asset('datatables-plugins/dataTables.bootstrap.min.js') !!}"></script>
    <script src="{!! asset('datatables-responsive/dataTables.responsive.js') !!}"></script>
    <script src="{!! asset('bootstrap-multiselect/js/bootstrap-multiselect.js') !!}"></script>
    <!-- ChartJs JavaScript -->
    <script src="{!! asset('chartjs/Chart.bundle.js') !!}"></script>
    <script src="{!! asset('chartjs/utils.js') !!}"></script>
    <!-- SweetAlert JavaScript -->
    <script src="{!! asset('sweetalert/sweetalert.min.js') !!}"></script>
    @include('sweet::alert')
    @yield('script')
</body>
</html>
