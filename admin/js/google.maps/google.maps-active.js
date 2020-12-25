
	
function initMap() {
        var chicago = new google.maps.LatLng(41.850, -87.650);

        var map = new google.maps.Map(document.getElementById('map'), {
          center: chicago,
          zoom: 3
        });

		var map1 = new google.maps.Map(document.getElementById('map1'), {
          zoom: 8,
          center: {lat: 35.717, lng: 139.731}
        });
		
		map2 = new google.maps.Map(document.getElementById('map2'), {
          center: {lat: -34.397, lng: 150.644},
          zoom: 8
        });
		
		
		 var mapOptions = {
                zoom: 12,
                scrollwheel: false,
                center: new google.maps.LatLng(20.192828, 85.853742),

            };
            var mapElement = document.getElementById('googleMap');
            var googleMap = new google.maps.Map(mapElement, mapOptions);
            var marker = new google.maps.Marker({
                position: new google.maps.LatLng(20.192828, 85.853742),
                googleMap: googleMap,
                title: 'Ramble!',
                icon: 'img/googlemap/1.png',
                animation: google.maps.Animation.BOUNCE

            });
			
			
			
			
		var cairo = {lat: 30.064742, lng: 31.249509};

        var maplan = new google.maps.Map(document.getElementById('maplan'), {
          scaleControl: true,
          center: cairo,
          zoom: 10
        });
        var infowindow = new google.maps.InfoWindow;
        infowindow.setContent('<b>???????</b>');
        var marker = new google.maps.Marker({map: map, position: cairo});
        marker.addListener('click', function() {
          infowindow.open(map, marker);
        });
		
		
		
        var myLatlng = {lat: -25.363, lng: 131.044};

        var map6 = new google.maps.Map(document.getElementById('map6'), {
          zoom: 4,
          center: myLatlng
        });

        var marker = new google.maps.Marker({
          position: myLatlng,
          map: map6,
          title: 'Click to zoom'
        });

        map.addListener('center_changed', function() {
          // 3 seconds after the center of the map has changed, pan back to the
          // marker.
          window.setTimeout(function() {
            map6.panTo(marker.getPosition());
          }, 3000);
        });

        marker.addListener('click', function() {
          map.setZoom(8);
          map.setCenter(marker.getPosition());
        });
      
		
		
		
        var map7 = new google.maps.Map(document.getElementById('map7'), {
          zoom: 4,
          center: {lat: -25.363882, lng: 131.044922 }
        });

        var bounds = {
          north: -25.363882,
          south: -31.203405,
          east: 131.044922,
          west: 125.244141
        };

        // Display the area between the location southWest and northEast.
        map.fitBounds(bounds);

        // Add 5 markers to map at random locations.
        // For each of these markers, give them a title with their index, and when
        // they are clicked they should open an infowindow with text from a secret
        // message.
        var secretMessages = ['This', 'is', 'the', 'secret', 'message'];
        var lngSpan = bounds.east - bounds.west;
        var latSpan = bounds.north - bounds.south;
        for (var i = 0; i < secretMessages.length; ++i) {
          var marker = new google.maps.Marker({
            position: {
              lat: bounds.south + latSpan * Math.random(),
              lng: bounds.west + lngSpan * Math.random()
            },
            map: map7
          });
          attachSecretMessage(marker, secretMessages[i]);
        }
      

      // Attaches an info window to a marker with the provided message. When the
      // marker is clicked, the info window will open with the secret message.
      function attachSecretMessage(marker, secretMessage) {
        var infowindow = new google.maps.InfoWindow({
          content: secretMessage
        });

        marker.addListener('click', function() {
          infowindow.open(marker.get('map'), marker);
        });
      }
		
		
		
		
        var originalMapCenter = new google.maps.LatLng(-25.363882, 131.044922);
        var map8 = new google.maps.Map(document.getElementById('map8'), {
          zoom: 4,
          center: originalMapCenter
        });

        var infowindow = new google.maps.InfoWindow({
          content: 'Change the zoom level',
          position: originalMapCenter
        });
        infowindow.open(map8);

        map.addListener('zoom_changed', function() {
          infowindow.setContent('Zoom: ' + map.getZoom());
        });
      
		
		
        var coordInfoWindow = new google.maps.InfoWindow();
        coordInfoWindow.setContent(createInfoWindowContent(chicago, map.getZoom()));
        coordInfoWindow.setPosition(chicago);
        coordInfoWindow.open(map);

        map.addListener('zoom_changed', function() {
          coordInfoWindow.setContent(createInfoWindowContent(chicago, map.getZoom()));
          coordInfoWindow.open(map);
        });
      }
	  
      var TILE_SIZE = 256;

      function createInfoWindowContent(latLng, zoom) {
        var scale = 1 << zoom;

        var worldCoordinate = project(latLng);

        var pixelCoordinate = new google.maps.Point(
            Math.floor(worldCoordinate.x * scale),
            Math.floor(worldCoordinate.y * scale));

        var tileCoordinate = new google.maps.Point(
            Math.floor(worldCoordinate.x * scale / TILE_SIZE),
            Math.floor(worldCoordinate.y * scale / TILE_SIZE));

        return [
          'Chicago, IL',
          'LatLng: ' + latLng,
          'Zoom level: ' + zoom,
          'World Coordinate: ' + worldCoordinate,
          'Pixel Coordinate: ' + pixelCoordinate,
          'Tile Coordinate: ' + tileCoordinate
        ].join('<br>');
      }

      // The mapping between latitude, longitude and pixels is defined by the web
      // mercator projection.
      function project(latLng) {
        var siny = Math.sin(latLng.lat() * Math.PI / 180);

        // Truncating to 0.9999 effectively limits latitude to 89.189. This is
        // about a third of a tile past the edge of the world tile.
        siny = Math.min(Math.max(siny, -0.9999), 0.9999);

        return new google.maps.Point(
            TILE_SIZE * (0.5 + latLng.lng() / 360),
            TILE_SIZE * (0.5 - Math.log((1 + siny) / (1 - siny)) / (4 * Math.PI)));
      }

	
 
