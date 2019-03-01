function getGraphicByProbability(type, monthProbability, dataMonth, yearProbability, dataYear, allProbability, dataAll, monitor_model){
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

  document.getElementById("value-probability").innerHTML = '&nbsp;';
  switch(type){
    case "month":
      document.getElementById("value-probability").innerHTML = changeLabelColor(monitor_model,monthProbability,1);
      config.options.scales.xAxes[0].scaleLabel.labelString = "Dia";
      config.data.datasets[0].data.push(monthProbability);
      config.data.labels.push(dataMonth);
      break;
    case "year":
      document.getElementById("value-probability").innerHTML = changeLabelColor(monitor_model,Math.round(calculateAVG(yearProbability)),1);
      config.options.scales.xAxes[0].scaleLabel.labelString = "Mes";
      for(i=0;i<yearProbability.length;i++){
        config.data.datasets[0].data.push(yearProbability[i]);
        config.data.labels.push(dataYear[i]);
      }
      break;
    case "all":
      document.getElementById("value-probability").innerHTML = changeLabelColor(monitor_model,Math.round(calculateAVG(allProbability)),1);
      config.options.scales.xAxes[0].scaleLabel.labelString = "Año";
      for(i=0;i<allProbability.length;i++){
        config.data.datasets[0].data.push(allProbability[i]);
        config.data.labels.push(dataAll[i]);
      }
      break;
  }

  var ctx = document.getElementById('canvas').getContext('2d');
  if(window.myLine != undefined){
    window.myLine.destroy();
  }
  window.myLine = new Chart(ctx, config);
  //createTables(datos, type, idMonitor, idTransformer);
}

function calculateAVG(values){
  sum = 0;
  for(i=0;i<values.length;i++){
    sum += values[i];
  }
  return sum / values.length;
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
