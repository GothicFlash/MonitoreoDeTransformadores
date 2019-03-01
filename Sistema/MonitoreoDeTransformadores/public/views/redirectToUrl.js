function ajaxRenderSection(url) { //Prueba para cargar vistas con Ajax
  $.ajax({
    type: 'GET',
    url: url,
    dataType: 'json',
    success: function (data) {
      $('#content').empty().append($(data));
    },
    error: function (data) {
      var errors = data.responseJSON;
      if (errors) {
        $.each(errors, function (i) {
          console.log(errors[i]);
        });
      }
    }
  });
}

function cargarDiv(url)
{
  console.log("Prueba: "+url);
  $('#content').load(url);
}
