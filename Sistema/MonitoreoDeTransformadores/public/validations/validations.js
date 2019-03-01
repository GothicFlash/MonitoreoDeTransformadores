function showPassword(){
  if($('#pass').attr('type') === 'text') {
    $('#pass').attr('type', 'password');
  }else{
    $('#pass').attr('type', 'text');
  }
}

function verifyExists(attribute, type, old_value, url){
  var new_value = attribute.value;
  if(new_value.length>0){
    $("#result").delay(50).queue(function(n) {
      $.ajax({
        type: "GET",
        url: url+getUrlToVerify(type)+new_value+"/"+old_value,
        error: function(){
          console.log("error petición ajax");
        },
        success: function(data){
          if(data.length==0){
            $("#btn-send").attr('disabled', false);
          }else{
            $("#btn-send").attr('disabled', true);
          }
          $("#result").html(data);
          n();
        }
      });
    });
  }
}

function getUrlToVerify(type){
  switch(type){
    case "gas":
      return "/gases/gas-find/";
      break;
    case "user":
      return "/users/user-find/";
      break;
    case "monitor":
      return "/monitors/monitor-find/";
      break;
  }
}

function changeTransformers(url){
  var model = document.getElementById('models').value;
  $.ajax({
    type: "GET",
    url: url+"/validate-transformers/"+model,
    error: function(){
      console.log("error petición ajax");
    },
    success: function(resul){
      var data = jQuery.parseJSON(resul);
      if(data.transformers_id.length != 0){
        var tags = "<option value='nuevo'>Nuevo Transformador</option>";
        for (var i = 0; i < data.transformers_id.length; i++) {
          tags += "<option value='"+data.transformers_id[i]+"'>"+data.names[i]+"</option>";
        }
        $('#transformers').html(tags);
      }else{
        $('#transformers').html("<option value='nuevo'>Nuevo Transformador</option>");
      }
    }
  });
}
