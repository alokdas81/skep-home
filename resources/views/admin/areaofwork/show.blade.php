<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<section class="content-header">
	<h1>All Regions</h1>
</section>
<div class="background_display">
	<div class="map_sctn">
		<div id="map-canvas"></div>
		<input type="text" name="region_name" class="region_name" id="region_name_value" placeholder="Enter Region Name" required />
		<a href="javascript:void(0);" id="save_select_area" class="srch_btn">Save Selected Area</a>
	</div>
</div>
	<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBb-WqNkVRHob7BVOeF2nbXBKNSLEALRWg&sensor=true"></script>
	<script> 
		var usaCenter = new google.maps.LatLng(46.3129391, -79.5833864);
		var poly, map;
		var path = new google.maps.MVCArray;
		function initialize() {
			var mapOptions = {
			    zoom: 5,
			    center: new google.maps.LatLng(46.3129391, -79.5833864),
			    mapTypeId: google.maps.MapTypeId.ROADMAP
			};
		  	var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
		}
		
	</script>
	<style>
	#map-canvas {
	    height: 70%;
	    width: 80%;
	    margin: 0 auto;
	    display: block;
	    padding-top: 10%;
	}
	.background_display{
		width: 80%;
	    height: 98%;
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