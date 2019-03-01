<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Reporte de probabilidad</title>
     <style type="text/css">
       @font-face {
         font-family: SourceSansPro;
         src: url(SourceSansPro-Regular.ttf);
       }

       .clearfix:after {
         content: "";
         display: table;
         clear: both;
       }

       body {
         position: relative;
         width: 21cm;
         height: 29.7cm;
         margin: 0 auto;
         color: #555555;
         background: #FFFFFF;
         font-family: Arial, sans-serif;
         font-size: 14px;
         font-family: SourceSansPro;
       }

       header {
         padding: 10px 0;
         margin-bottom: 20px;
         border-bottom: 1px solid #AAAAAA;
       }

       #logo {
         text-align: center;
         margin-bottom: 10px;
       }

       #logo img {
         width: 90px;
       }

       #company {
         float: right;
         text-align: right;
       }


       #details {
         margin-bottom: 50px;
       }

       #invoice {
         float: right;
         text-align: right;
       }

       #invoice h1 {
         color: #0087C3;
         font-size: 2.4em;
         line-height: 1em;
         font-weight: normal;
         margin: 0  0 10px 0;
       }

       #invoice .date {
         font-size: 1.1em;
         color: #777777;
       }


       table {
         width: 100%;
         border-collapse: collapse;
         border-spacing: 0;
         margin-bottom: 20px;
       }

       table tr:nth-child(2n-1) td {
         background: #F5F5F5;
       }

       table th,
       table td {
         text-align: center;
       }

       table th {
         padding: 5px 20px;
         color: #5D6975;
         border-bottom: 1px solid #C1CED9;
         white-space: nowrap;
         font-weight: normal;
       }

       table .service,
       table .desc {
         text-align: left;
       }

       table td {
         padding: 20px;
         text-align: right;
       }

       table td.service,
       table td.desc {
         vertical-align: top;
       }

       table td.unit,
       table td.qty,
       table td.total {
         font-size: 1.2em;
       }

       table td.grand {
         border-top: 1px solid #5D6975;;
       }

       #notices .notice {
         color: #5D6975;
         font-size: 1.2em;
       }

       footer {
         color: #5D6975;
         width: 100%;
         height: 30px;
         position: absolute;
         bottom: 0;
         border-top: 1px solid #C1CED9;
         padding: 8px 0;
         text-align: center;
       }
     </style>
  </head>
  <body>
    <header class="clearfix">
      <div id="logo">
        <img src="{{ public_path('images/logotipo-confiamex.png') }}" width="15%">
      </div>
    </header>

    <main>
      <div id="details" class="clearfix">
        <div id="invoice">
          <h1>{{ $monitor->model }}</h1>
          <div class="date">Nodo: {{ $monitor->node }}</div>
          <div class="date">
            Gases:
              @foreach ($monitor->gases as $gas) {{ $gas->name }}, @endforeach
          </div>
          <div class="date">Transformador: {{ $transformer->name }}</div>
          <div class="date">Fecha de reporte: {{ date("d/m/Y") }}</div>
        </div>
      </div>

      <h1>Estado de probabilidades</h1>
      <!-- Tabla para mostrar los valores de las probabilidades -->
      <table>
        <thead>
          <tr>
            <th class="service">Tipo</th>
            <th class="desc">Porcentage (%)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="service">Probabilidad por mes</td>
            <td class="desc">{{ $probabilityByMonth }}</td>
          </tr>
          <tr>
            <td class="service">Probabilidad por año</td>
            <td class="desc">{{ $probabilityByYear }}</td>
          </tr>
          <tr>
            <td class="service">Probabilidad total</td>
            <td class="desc">{{ $probabilityByAll }}</td>
          </tr>
        </tbody>
      </table>
      <h1>Mediciones de gases</h1>
      <!-- Tabla para mediciones de gases por dia (Muestra valores en ppm de cada hora registrada en la bitacora)-->
      <table>
        <thead>
          <tr>
            <th colspan="{{ sizeof($dayAverages["gases"]) + 1 }}"> Mediciones por dia (ppm)</th>
          </tr>
          <tr>
            <th class="service">Hora</th>
            @for ($i=0; $i < sizeof($dayAverages["gases"]); $i++)
              <th class="service">{{ $dayAverages["gases"][$i] }}</th>
            @endfor
          </tr>
        </thead>
        <tbody>
          @for ($i=0; $i < sizeof($dayAverages["information"][0]["dates"]); $i++)
            <tr>
              <th>{{ $dayAverages["information"][0]["dates"][$i] }}</th>
              @for ($j=0; $j < sizeof($dayAverages["gases"]); $j++)
                @if (array_key_exists($i,$dayAverages["information"][$j]["averages"]))
                  <th> {{ $dayAverages["information"][$j]["averages"][$i] }} </th>
                @else
                  <th> Sin registro </th>
                @endif
              @endfor
            </tr>
          @endfor
        </tbody>
      </table>
      <!-- Tabla para mediciones de gases por mes (Muestra promedios)-->
      <table>
        <thead>
          <tr>
            <th colspan="{{ sizeof($monthAverages["gases"]) + 1 }}"> Mediciones por mes (Promedios)</th>
          </tr>
          <tr>
            <th class="service">Día</th>
            @for ($i=0; $i < sizeof($monthAverages["gases"]); $i++)
              <th class="service">{{ $monthAverages["gases"][$i] }}</th>
            @endfor
          </tr>
        </thead>
        <tbody>
          @for ($i=0; $i < sizeof($monthAverages["information"][0]["dates"]); $i++)
            <tr>
              <th>{{ $monthAverages["information"][0]["dates"][$i] }}</th>
              @for ($j=0; $j < sizeof($monthAverages["gases"]); $j++)
                @if (array_key_exists($i,$monthAverages["information"][$j]["averages"]))
                  <th> {{ $monthAverages["information"][$j]["averages"][$i] }} </th>
                @else
                  <th> Sin registro </th>
                @endif
              @endfor
            </tr>
          @endfor
        </tbody>
      </table>
      <!-- Tabla para mediciones de gases por año (Muestra promedios) -->
      <table>
        <thead>
          <tr>
            <th colspan="{{ sizeof($yearAverages["gases"]) + 1 }}"> Mediciones por año (Promedios)</th>
          </tr>
          <tr>
            <th class="service">Año</th>
            @for ($i=0; $i < sizeof($yearAverages["gases"]); $i++)
              <th class="service">{{ $yearAverages["gases"][$i] }}</th>
            @endfor
          </tr>
        </thead>
        <tbody>
          @for ($i=0; $i < sizeof($yearAverages["information"][0]["dates"]); $i++)
            <tr>
              <th>{{ $yearAverages["information"][0]["dates"][$i] }}</th>
              @for ($j=0; $j < sizeof($yearAverages["gases"]); $j++)
                @if (array_key_exists($i,$yearAverages["information"][$j]["averages"]))
                  <th> {{ $yearAverages["information"][$j]["averages"][$i] }} </th>
                @else
                  <th> Sin registro </th>
                @endif
              @endfor
            </tr>
          @endfor
        </tbody>
      </table>
      <!-- Tabla para mediciones de gases por todos los años (Muestra promedios)-->
      <table>
        <thead>
          <tr>
            <th colspan="{{ sizeof($allAverages["gases"]) + 1 }}"> Mediciones por todos los años (Promedios)</th>
          </tr>
          <tr>
            <th class="service">Año</th>
            @for ($i=0; $i < sizeof($allAverages["gases"]); $i++)
              <th class="service">{{ $allAverages["gases"][$i] }}</th>
            @endfor
          </tr>
        </thead>
        <tbody>
          @for ($i=0; $i < sizeof($allAverages["information"][0]["dates"]); $i++)
            <tr>
              <th>{{ $allAverages["information"][0]["dates"][$i] }}</th>
              @for ($j=0; $j < sizeof($allAverages["gases"]); $j++)
                @if (array_key_exists($i,$allAverages["information"][$j]["averages"]))
                  <th> {{ $allAverages["information"][$j]["averages"][$i] }} </th>
                @else
                  <th> Sin registro </th>
                @endif
              @endfor
            </tr>
          @endfor
        </tbody>
      </table>
    </main>
  </body>
</html>
