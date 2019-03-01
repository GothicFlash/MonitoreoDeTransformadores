function getGraphicByProbability(idMonitor, idTransformer, type, monitor_model){
  var config = {
    type: 'line',
    data: {
      labels: [],
      datasets: [{
        label: 'Probabilidad',
        backgroundColor: window.chartColors.blue,
        borderColor: window.chartColors.blue,
        data: []
        ,fill: false,
      },]
    },
    options: {
      responsive: true,
      title: {
        display: true,
        text: 'Probabilidad de falla'
      },
      tooltips: {
        mode: 'index',
        intersect: false,
      },
      hover: {
        mode: 'nearest',
        intersect: true
      },
      scales: {
        xAxes: [{
          display: true,
          scaleLabel: {
            display: true,
            labelString: 'Dia'
          }
        }],
        yAxes: [{
          display: true,
          scaleLabel: {
            display: true,
            labelString: 'Valor'
          }
        }]
      }
    }
  };
  
  var url;
  switch(type){
    case "month":
      url = "getMonthProbability/"+idTransformer;
      config.options.scales.xAxes[0].scaleLabel.labelString = "Dia";
      break;
    case "year":
      url = "getYearProbability/"+idTransformer;
      config.options.scales.xAxes[0].scaleLabel.labelString = "Mes";
      break;
    case "all":
      url = "getAllProbability/"+idTransformer;
      config.options.scales.xAxes[0].scaleLabel.labelString = "Año";
      break;
  }

  $.get(url, function(resul){
    var datos= jQuery.parseJSON(resul);
    for(i=datos.probabilities.length-1;i>=0;i--){
      config.data.datasets[0].data.push(datos.probabilities[i]);
      config.data.labels.push(datos.dates[i]);
    }

    var ctx = document.getElementById('canvas').getContext('2d');
    document.getElementById("value-probability").innerHTML = '&nbsp;';
    document.getElementById("value-probability").innerHTML = changeLabelColor(monitor_model,datos.lastProbability,1);
    if(window.myLine != undefined){
        window.myLine.destroy();
    }
    window.myLine = new Chart(ctx, config);
    createTables(datos, type, idMonitor, idTransformer);
    });
}

function changeLabelColor(monitor_model, probability, big){
  var label = "";
  if(monitor_model=="CALISTO 1" || monitor_model=="CALISTO 2"){
    if(probability>=0 && probability <=35){
      label = (big==1)?"<h1 class='text-success text-center'>"+probability+"%</h1>":"<h4 class='text-success text-center'>"+probability+"%</h4>";
    }else if (probability>=36 && probability <=63) {
      label = (big==1)?"<h1 class='text-warning text-center'>"+probability+"%</h1>":"<h4 class='text-warning text-center'><big>"+probability+"%</h4>";
    } else if (probability > 63) {
      label = (big==1)?"<h1 class='text-danger text-center'>"+probability+"%</h1>":"<h4 class='text-danger text-center'>"+probability+"%</h4>";
    }
  }else{
    if(probability>=0 && probability <=33){
      label = (big==1)?"<h1 class='text-success text-center'>"+probability+"%</h1>":"<h4 class='text-success text-center'>"+probability+"%</h4>";
    }else if (probability>=34 && probability <=61) {
      label = (big==1)?"<h1 class='text-warning text-center'>"+probability+"%</h1>":"<h4 class='text-warning text-center'>"+probability+"%</h4>";
    } else if (probability > 61) {
      label = (big==1)?"<h1 class='text-danger text-center'>"+probability+"%</h1>":"<h4 class='text-danger text-center'>"+probability+"%</h4>";
    }
  }
  return label;
}

function getLabelBy(type){
  switch(type){
    case "month":
      return "Dia";
    case "year":
      return "Mes";
    case "all":
      return "Año";
  }
}

function createTables(data, type, monitor, transformer){
  var url = "getAverages/"+transformer+"/"+type;

  $.get(url,function(resul){
    var datos= jQuery.parseJSON(resul);

    document.getElementById("table").innerHTML = '&nbsp;'; //Overwrite new elements
    var body = document.getElementById("table");
    // Crea un elemento <table> y un elemento <tbody>
    var tabla   = document.createElement("table");
                  tabla.classList.add('table');

    var tblHead = document.createElement("thead");

    var cabecera = document.createElement("tr");

    for (var i = 0; i < datos.gases.length + 1; i++) {
      var celda = document.createElement("th");
      var textoCelda;
      if(i==0){
        textoCelda = document.createTextNode(getLabelBy(type));
      }else{
        textoCelda = document.createTextNode(datos.gases[i-1]);
      }
      celda.appendChild(textoCelda);
      cabecera.appendChild(celda);
    }
    tblHead.appendChild(cabecera);
    tabla.appendChild(tblHead);

    var tblBody = document.createElement("tbody");

    if(datos.information[0].averages != undefined){
      for (var i = 0; i < datos.information[0].averages.length; i++) {
        var hilera = document.createElement("tr");
        for (var j = 0; j < datos.gases.length+1; j++) {
          var celda = document.createElement("td");
          var textoCelda;
          if(j==0){
            textoCelda = document.createTextNode(datos.information[0].dates[i]);
          }else{
            textoCelda = document.createTextNode(datos.information[j-1].averages[i]);
          }
          celda.appendChild(textoCelda);
          hilera.appendChild(celda);
        }
        tblBody.appendChild(hilera);
      }
      tabla.appendChild(tblBody);
      body.appendChild(tabla);
    }
  })
}

function graphicGases(idTransformer){
  var url = "getGasesByMonitor/"+idTransformer;
  var configuration = [];
  $.get(url,function(resul){
    var datos= jQuery.parseJSON(resul);
    for(i=0;i<datos.gases.length;i++){
      configuration[i] = getConfiguration(datos,i);
    }
    for(i=0;i<configuration.length;i++){
      var idGraphic = "canvas-gas-"+datos.gases[i];
      var ctx = document.getElementById(idGraphic).getContext('2d');
      if(window.myLine != undefined){
          window.myLine.destroy();
      }
      new Chart(ctx, configuration[i]); // Si quito el window.myLine = solo grafica la ultima gráfica
    }
  })
}

function getConfiguration(datos, i){
  var config = {
    type: 'line',
    data: {
      labels: [],
      datasets: [{
        label: 'ppm',
        backgroundColor: window.chartColors.blue,
        borderColor: window.chartColors.blue,
        data: []
        ,fill: false,
      },]
    },
    options: {
      responsive: true,
      title: {
        display: true,
        text: ''
      },
      tooltips: {
        mode: 'index',
        intersect: false,
      },
      hover: {
        mode: 'nearest',
        intersect: true
      },
      scales: {
        xAxes: [{
          display: true,
          scaleLabel: {
            display: true,
            labelString: 'Hora'
          }
        }],
        yAxes: [{
          display: true,
          scaleLabel: {
            display: true,
            labelString: 'Valor'
          }
        }]
      }
    }
  };
  config.options.title.text = "Medición "+datos.names[i];
  if(datos.information[i].averages != null){
    for(j=0;j<datos.information[i].averages.length;j++){
      config.data.datasets[0].data.push(datos.information[i].averages[j]);
      config.data.labels.push(datos.information[i].dates[j]);
    }
  }
  return config;
}

function getGasesBy(idMonitor, idTransformer, type, gas, name){
  var config = {
    type: 'line',
    data: {
      labels: [],
      datasets: [{
        label: '',
        backgroundColor: window.chartColors.blue,
        borderColor: window.chartColors.blue,
        data: []
        ,fill: false,
      },]
    },
    options: {
      responsive: true,
      title: {
        display: true,
        text: ''
      },
      tooltips: {
        mode: 'index',
        intersect: false,
      },
      hover: {
        mode: 'nearest',
        intersect: true
      },
      scales: {
        xAxes: [{
          display: true,
          scaleLabel: {
            display: true,
            labelString: 'Mes'
          }
        }],
        yAxes: [{
          display: true,
          scaleLabel: {
            display: true,
            labelString: 'Valor'
          }
        }]
      }
    }
  };

  var url;
  switch(type){
    case "day":
      url = "getGasesByDay/"+idMonitor+"/"+gas;
      config.options.scales.xAxes[0].scaleLabel.labelString = "Hora";
      config.data.datasets[0].label = "ppm";
      break;
    case "month":
      url = "getGasesByMonth/"+idMonitor+"/"+gas;
      config.options.scales.xAxes[0].scaleLabel.labelString = "Dia";
      config.data.datasets[0].label = "Promedio";
      break;
    case "year":
      url = "getGasesByYear/"+idMonitor+"/"+gas;
      config.options.scales.xAxes[0].scaleLabel.labelString = "Mes";
      config.data.datasets[0].label = "Promedio";
      break;
    case "all":
      url = "getGasesByAll/"+idMonitor+"/"+gas;
      config.options.scales.xAxes[0].scaleLabel.labelString = "Año";
      config.data.datasets[0].label = "Promedio";
      break;
  }
  config.options.title.text = "Medición "+name;
  $.get(url,function(resul){
    var datos= jQuery.parseJSON(resul);
    for(i=0;i<datos.averages.length;i++){
      config.data.datasets[0].data.push(datos.averages[i]);
      config.data.labels.push(datos.dates[i]);
    }
    document.getElementById("container-canvas-"+gas).innerHTML = '&nbsp;'; //Overwrite new elements
    document.getElementById("container-canvas-"+gas).innerHTML = "<canvas id='canvas-gas-"+gas+"'></canvas>";
    var ctx = document.getElementById("canvas-gas-"+gas).getContext('2d');
    new Chart(ctx, config);
  })
}

function getProbabilities(){
  var url = 'home/getProbabilities';
  $.get(url,function(resul){
    var datos= jQuery.parseJSON(resul);
    for(i=0;i<datos.probabilities.length;i++){
      if(datos.probabilities[i] == -1){
        document.getElementById("monitor-"+datos.monitors[i]).innerHTML = "<h4 class='text-dark text-center'>Sin registro</h4>";
      }else{
        document.getElementById("monitor-"+datos.monitors[i]).innerHTML = changeLabelColor(datos.monitors_models[i],datos.probabilities[i],0);
      }
    }
  })
}
