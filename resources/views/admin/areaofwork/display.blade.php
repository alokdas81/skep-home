@extends('layouts.admin')

@section('content')
<section class="content-header">
	<h1>All Regions</h1>
</section>
<div class="background_display">
	<div class="map_sctn">
		<div id="map-canvas"></div>
	</div>
</div>
  
<script> 
	 
	function initMap() {
		var bounds = new google.maps.LatLngBounds();
		var polygons = [];
	  	var map = new google.maps.Map(document.getElementById('map-canvas'), {
	    zoom: 14,
	    center: {lat: 43.647087, lng: -79.373274},
	    mapTypeId: google.maps.MapTypeId.ROADMAP,
			zoomControl: true,
			streetViewControl: false,
			zoomControlOptions: {
				style: google.maps.ZoomControlStyle.SMALL
			},
			styles: [
	          {elementType: 'geometry', stylers: [{color: '#ebe3cd'}]},
              {elementType: 'labels.text.fill', stylers: [{color: '#523735'}]},
              {elementType: 'labels.text.stroke', stylers: [{color: '#f5f1e6'}]},
              {
                featureType: 'administrative',
                elementType: 'geometry.stroke',
                stylers: [{color: '#c9b2a6'}]
              },
              {
                featureType: 'administrative.land_parcel',
                elementType: 'geometry.stroke',
                stylers: [{color: '#dcd2be'}]
              },
              {
                featureType: 'administrative.land_parcel',
                elementType: 'labels.text.fill',
                stylers: [{color: '#ae9e90'}]
              },
              {
                featureType: 'landscape.natural',
                elementType: 'geometry',
                stylers: [{color: '#dfd2ae'}]
              },
              {
                featureType: 'poi',
                elementType: 'geometry',
                stylers: [{color: '#dfd2ae'}]
              },
              {
                featureType: 'poi',
                elementType: 'labels.text.fill',
                stylers: [{color: '#93817c'}]
              },
              {
                featureType: 'poi.park',
                elementType: 'geometry.fill',
                stylers: [{color: '#a5b076'}]
              },
              {
                featureType: 'poi.park',
                elementType: 'labels.text.fill',
                stylers: [{color: '#447530'}]
              },
              {
                featureType: 'road',
                elementType: 'geometry',
                stylers: [{color: '#f5f1e6'}]
              },
              {
                featureType: 'road.arterial',
                elementType: 'geometry',
                stylers: [{color: '#fdfcf8'}]
              },
              {
                featureType: 'road.highway',
                elementType: 'geometry',
                stylers: [{color: '#f8c967'}]
              },
              {
                featureType: 'road.highway',
                elementType: 'geometry.stroke',
                stylers: [{color: '#e9bc62'}]
              },
              {
                featureType: 'road.highway.controlled_access',
                elementType: 'geometry',
                stylers: [{color: '#e98d58'}]
              },
              {
                featureType: 'road.highway.controlled_access',
                elementType: 'geometry.stroke',
                stylers: [{color: '#db8555'}]
              },
              {
                featureType: 'road.local',
                elementType: 'labels.text.fill',
                stylers: [{color: '#806b63'}]
              },
              {
                featureType: 'transit.line',
                elementType: 'geometry',
                stylers: [{color: '#dfd2ae'}]
              },
              {
                featureType: 'transit.line',
                elementType: 'labels.text.fill',
                stylers: [{color: '#8f7d77'}]
              },
              {
                featureType: 'transit.line',
                elementType: 'labels.text.stroke',
                stylers: [{color: '#ebe3cd'}]
              },
              {
                featureType: 'transit.station',
                elementType: 'geometry',
                stylers: [{color: '#dfd2ae'}]
              },
              {
                featureType: 'water',
                elementType: 'geometry.fill',
                stylers: [{color: '#b9d3c2'}]
              },
              {
                featureType: 'water',
                elementType: 'labels.text.fill',
                stylers: [{color: '#92998d'}]
              }
            ]
        }); 
<?php
 foreach($regions as $region){
 	$allcoordinates =  json_decode($region->region_lat_lng);?>
 	arr = [];
	<?php 
 	foreach ($allcoordinates as $value){
		$coordinateLat =  $value->lat;
		$coordinateLong =  $value->lng;?>

    	 var clat = <?php echo $coordinateLat;  ?>;
    	 var clong = <?php echo $coordinateLong;  ?>;
		 	
	    	arr.push( new google.maps.LatLng(clat,clong));

	    bounds.extend(arr[arr.length - 1])
	    <?php } ?>
	    polygons.push(new google.maps.Polygon({
	      paths: arr,
	      strokeColor: '#FF0000',
	      strokeOpacity: 0.8,
	      strokeWeight: 2,
	      fillColor: '#FF0000',
	      fillOpacity: 0.35
	    }));
	    polygons[polygons.length - 1].setMap(map);
	<?php } ?>
	map.fitBounds(bounds);
	} 

</script>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAOj0_u0DRE2dK8X9YptdCXtxt89UCqfoo&sensor=true&callback=initMap"></script>
@endsection
<style>
	#map-canvas {
	    height: 80%;
	    width: 100%;
	    margin: 0 auto;
	    display: block;
	    padding-top: 10%;
	}
	.background_display{
		width: 90%;
	    height: 90%;
	    background: #fff;
	    display: block;
	    margin: 0 auto;
	    box-shadow: 5px 10px 5px 10px #ddd;
	}
	.srch_btn{
		background: red;
	    padding: 8px 8px;
	    margin: 0 auto;
	    display: block;
	    width: 12%;
	    text-align: center;
	    margin-top: 4px;
	    border-radius: 7px;
	    color: #fff;
	    text-decoration: none;
	}
	.region_name{
		display: block;
	    margin: 0 auto;
	    padding: 8px 8px;
	    margin-top: 8px;
	    font-size: 17px;
	    font-weight: 600;
	}
</style>