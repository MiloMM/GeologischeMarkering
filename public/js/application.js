function initialize() {
    var trymarker = false;
   
    var mapOptions = {
        center: { lat: 51.800, lng: 4.700},
        zoom: 7
      };
    
    var map = new google.maps.Map(document.getElementById('map-canvas'),
        mapOptions);
    
    var marker;

    function placeMarker(location) {
      if(trymarker === false){
        if ( marker ) {
                marker.setPosition(location);
                updatePosition();
            } else {
                marker = new google.maps.Marker({
                    position: location,
                    map: map,
                });
                showGuess();
                updatePosition();
            }
        }
    }

    if ($('#guess').length !== 0) {
        $('#guess').on('click', function(){
        $.ajax(url + "/game/ajaxGetStats/" + photo_id).done(function(result) {
          
          var latlong = result.split(',');
          var latilongi = new google.maps.LatLng(latlong[0], latlong[1]);
          
          console.log(latilongi);

          var secondMarker = new google.maps.Marker({
              position: latilongi,
              map: map
          });

          var flightPlanCoordinates = [
      new google.maps.LatLng(marker.getPosition().lat(), marker.getPosition().lng()),
         new google.maps.LatLng(secondMarker.getPosition().lat(), secondMarker.getPosition().lng())
          ];

          var flightPath = new google.maps.Polyline({
            path: flightPlanCoordinates,
             geodesic: true,
            strokeColor: '#FF0000',
            strokeOpacity: 1.0,
            strokeWeight: 2
          });


          flightPath.setMap(map);
          var getLatLng = marker.getPosition(); 
          var latilongi = secondMarker.getPosition();
          var distance = google.maps.geometry.spherical.computeDistanceBetween (getLatLng, latilongi) / 1000;
          var fixedDist = Math.round(distance) + " Kilometers";

          document.getElementById("distance").value = fixedDist;
          document.getElementById("photo_id").value = photo_id;
          trymarker = true;

          google.maps.event.addListener(secondMarker, 'click', function() {
              infowindow.open(map,secondMarker);
          });
          })
           .fail(function() {
          })
           .always(function() {
          });
      });
    }

    function showGuess(){
        $( "#form" ).fadeIn( "slow", function() {});
    }

    

    function updatePosition() {
        var getLatLng = marker.getPosition();
    }

    google.maps.event.addListener(map, 'click', function(event) {
        placeMarker(event.latLng);
    });
}
google.maps.event.addDomListener(window, 'load', initialize);

function submit(){
    document.getElementById("scoreBoard").submit();
}

function goToHome(){
    window.location.replace(url + "home/index");
}