function showDetails(){
  if(document.getElementById('show-details').innerHTML == "Ver detalles"){
    document.getElementById("show-details").classList.remove('btn-success');
    document.getElementById("show-details").classList.add('btn-info');
    document.getElementById('show-details').innerHTML = "Ocultar detalles";
    $('#table').show();
    $('html,body').animate({
        scrollTop: $("#table").offset().top
    }, 2000);
  }else{
    document.getElementById("show-details").classList.remove('btn-info');
    document.getElementById("show-details").classList.add('btn-success');
    document.getElementById('show-details').innerHTML = "Ver detalles";
    $('#table').hide();
  }
}

function changeTransformer(){
  var monitor = document.getElementById('monitors').value;
  var url = "transformer/"+monitor;
  $.get(url,function(resul){
    var datos= jQuery.parseJSON(resul);
    document.getElementById("transformers").innerHTML = '&nbsp;'; //Overwrite new elements
    var select = document.getElementById("transformers");
    for(i=0;i<datos.transformers.length;i++){
      var option = document.createElement("option");
      option.setAttribute("value",datos.transformers[i]);
      var text = document.createTextNode(datos.names[i]);
      option.appendChild(text);
      select.appendChild(option);
    }
  })
}

function changeImage(input){
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function (e) {
      $('#image')
        .attr('src', e.target.result)
        .width(500)
        .height(400);
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function loadInformation(){
  $(document).ready(function() {
    function getRandValue(){
      $.ajax({
        type: "GET",
        url: "../import-information",
        data: {
          _token: '{!! csrf_token() !!}',
        },
        success: function(data) {
          console.log(data);
        }
      });
    }
    setInterval(getRandValue, 10000); //Imports the information each 10 seconds
  });
}

function changeGases(){
  var model = document.getElementById('models').value;
  switch (model) {
    case "CALISTO 1":
      document.getElementById('gases').value = "H2, WC";
      break;
    case "CALISTO 2":
      document.getElementById('gases').value = "H2, WC, CO";
      break;
    case "MHT410":
      document.getElementById('gases').value = "H2, WC, TEMPERATURA";
      break;
    case "CALISTO 9":
      document.getElementById('gases').value = "H2, CH4, C2H6, C2H4, C2H2, CO, CO2, O2, WC";
      break;
    case "OPT100":
      document.getElementById('gases').value = "H2, CH4, C2H6, C2H4, C2H2, CO, CO2, O2, WC";
      break;
  }
}

function typeStore(type){
  switch(type){
    case 'standard':
      $("#type").val("standard");
      $("#btn-standard").addClass("active");
      $("#btn-personalized").removeClass("active");

      //Required elements
      $("#model-text").prop('required',false);
      $("#multiple-gases").prop('required',false);
      $("#methods").prop('required',false);

      $("#div-multiple-gases").css("display", "none");
      $("#div-model-unique").css("display", "none");
      $("#div-methods").css("display", "none");

      $("#div-model").css("display", "block");
      $("#div-static-gases").css("display", "block");
      break;
    case 'personalized':
      $("#type").val("personalized");
      $("#btn-standard").removeClass("active");
      $("#btn-personalized").addClass("active");

      //Required elements
      $("#model-text").prop('required',true);
      $("#multiple-gases").prop('required',true);
      $("#methods").prop('required',true);

      $("#div-model").css("display", "none");
      $("#div-static-gases").css("display", "none");

      $("#div-multiple-gases").css("display", "block");
      $("#div-model-unique").css("display", "block");
      $("#div-methods").css("display", "block");
      $("#model-text").val($("#models").val());
      break;
  }
}

function showMethods(){
  if($("#multiple-gases").val().length != 0){
    var tags = "";
    if (validateCompatibleMethods([1,2])) {
      tags += "<option value='1'>Método para CALISTO 1 y MHT410</option>";
    }
    if (validateCompatibleMethods([1,2,3])) {
      tags += "<option value='2'>Método para CALISTO 2</option>";
    }
    if (validateCompatibleMethods([1,5,6,7,8,3,9,10,2])) {
      tags += "<option value='3'>Método para CALISTO 9</option>";
      tags += "<option value='4'>Método para OPT100</option>";
    }
    $('#methods').html(tags);
    if (tags == "") {
      $("#resultMethods").html("<span style='font-weight:bold;color:red;'>No hay métodos compatibles para estos gases.</span>");
    }else {
      $("#resultMethods").html("");
    }
  }else{
    $("#resultMethods").html("<span style='font-weight:bold;color:red;'>Debes seleccionar al menos 2 gases.</span>");
  }
}

function validateCompatibleMethods(gases){
  cont = 0;
  for (var i = 0; i < gases.length; i++) {
    for (var j = 0; j < $("#multiple-gases").val().length; j++) {
      if(gases[i] == $("#multiple-gases").val()[j]){
        cont++;
      }
    }
  }
  return gases.length==cont;
}
