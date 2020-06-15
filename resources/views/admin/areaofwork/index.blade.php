<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
@extends('layouts.admin')

@section('content')

<section class="content-header">
	<div class="total_area_work_section" style="width:100%;">
		<h1 style="text-align: center; display:inline-block;padding-left: 40px;">Select Area Of Work</h1>
		<!--<a class="check_all_regions" href="<?php echo URL::to("/")?>/admin/areaofwork/mapRegions" style="display:inline-block;" target="_blank">Check All Regions</a>-->
	</div>
</section>
<div class="background_display">
	<div class="map_sctn">
		<div id="map-canvas"></div>
		<div id="tags"></div>
		<input type="text" name="region_name" class="region_name" id="region_name_value" placeholder="Enter Region Name" required />
		<a href="javascript:void(0);" id="save_select_area" class="srch_btn">Save Selected Area</a>
	</div>
</div>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAOj0_u0DRE2dK8X9YptdCXtxt89UCqfoo"></script>
<script>
var map;
var poly;
var latlng = new google.maps.LatLng(43.647087, -79.373274);
var polygons = [];
var bounds = new google.maps.LatLngBounds();
var infowindow = new google.maps.InfoWindow();
function createMap() {
    map = new google.maps.Map(document.getElementById('map-canvas'), {
	center: latlng,
	zoom: 14,
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
    	],

	});

	poly = new google.maps.Polyline({
		editable: true,
		strokeColor: '#FF0000',
		strokeOpacity: 1.0,
		strokeWeight: 2,
	});

	poly.setMap(map);

    map.addListener('click', addLatLng);
	map.addListener('rightclick', deletePoly);

	<?php
		$i=1;
		if(!empty($regions)){
			foreach($regions as $region){
				$name = '';
			 	$allcoordinates =  json_decode($region->region_lat_lng);
			 	$id = $region->id;
			 	$name = $region->region_name;
			 	$region_id = $region->region_id;?>
			 	arr = [];
				<?php if(!empty($allcoordinates)){
			 		foreach ($allcoordinates as $value){
						$coordinateLat =  $value->lat;
						$coordinateLong =  $value->lng;?>

				    	var clat = <?php echo $coordinateLat;  ?>;
				    	var clong = <?php echo $coordinateLong;  ?>;
					    arr.push( new google.maps.LatLng(clat,clong));
					    bounds.extend(arr[arr.length - 1])
		  			<?php } ?>
					    var polygons = new google.maps.Polygon({
					      paths: arr,
					      strokeColor: '#FF0000',
					      strokeOpacity: 0.8,
					      strokeWeight: 2,
					      fillColor: '#FF0000',
					      fillOpacity: 0.35
					    });
					    polygons.setMap(map);
					    var name= '';
					    name = '<?php echo $name; ?>';
					    google.maps.event.addListener(polygons,'click', function(e) {
					      showArrays.call(this,
					        e,
					        '<?php echo $name; ?>',
					        '<?php echo $id;?>',
					        '<?php echo $region_id;?>'
					      );
					    });
			    		infoWindow = new google.maps.InfoWindow;
				<?php } 
			}?>
			map.fitBounds(bounds); 
		<?php }?>
			
			
		}
		function showArrays(event,name,id,region_id) {

	        var vertices = this.getPath();

	        var contentString = '<b> Region Name - '+name+'</b><br/><br/> <b>Region Id - '+region_id+'</b><br/><br/><button id="delete_regions" onclick="deleteRegion('+id+')">Delete Region</button>';

	        // Replace the info window's content and position.
	        infoWindow.setContent(contentString);
	        infoWindow.setPosition(event.latLng);

	        infoWindow.open(map);
      	}

		function addLatLng(event) {
			var path = poly.getPath();
			var contentLength = path.getLength();
			path.push(event.latLng);

		}

		function deletePoly() {
			var deleteMenu = new DeleteMenu();

			google.maps.event.addListener(poly, 'rightclick', function(e) {
				// Check if click was on a vertex control point
				if (e.vertex == undefined) {
					return;
				}
				deleteMenu.open(map, poly.getPath(), e.vertex);
			});
		}

		/**
		 * A menu that lets a user delete a selected vertex of a path.
		 * @constructor
		 */
		function DeleteMenu() {
			this.div_ = document.createElement('div');
			this.div_.className = 'delete-menu';
			this.div_.innerHTML = 'Delete';

			var menu = this;
			google.maps.event.addDomListener(this.div_, 'click', function() {
				menu.removeVertex();
			});
		}
		DeleteMenu.prototype = new google.maps.OverlayView();

		DeleteMenu.prototype.onAdd = function() {
			var deleteMenu = this;
			var map = this.getMap();
			this.getPanes().floatPane.appendChild(this.div_);

			// mousedown anywhere on the map except on the menu div will close the
			// menu.
			this.divListener_ = google.maps.event.addDomListener(map.getDiv(), 'mousedown', function(e) {
				if (e.target != deleteMenu.div_) {
					deleteMenu.close();
				}
			}, true);
		};

		DeleteMenu.prototype.onRemove = function() {
			google.maps.event.removeListener(this.divListener_);
			this.div_.parentNode.removeChild(this.div_);

			// clean up
			this.set('position');
			this.set('path');
			this.set('vertex');
		};

		DeleteMenu.prototype.close = function() {
			this.setMap(null);
		};

		DeleteMenu.prototype.draw = function() {
			var position = this.get('position');
			var projection = this.getProjection();

			if (!position || !projection) {
				return;
			}

			var point = projection.fromLatLngToDivPixel(position);
			this.div_.style.top = point.y + 'px';
			this.div_.style.left = point.x + 'px';
		};

		/**
		 * Opens the menu at a vertex of a given path.
		 */
		DeleteMenu.prototype.open = function(map, path, vertex) {
			this.set('position', path.getAt(vertex));
			this.set('path', path);
			this.set('vertex', vertex);
			this.setMap(map);
			this.draw();
		};

		/**
		 * Deletes the vertex from the path.
		 */
		DeleteMenu.prototype.removeVertex = function() {
			var path = this.get('path');
			var vertex = this.get('vertex');

			if (!path || vertex == undefined) {
				this.close();
				return;
			}

			path.removeAt(vertex);
			this.close();
		};

		google.maps.event.addDomListener(window, 'load', createMap);

		function deleteRegion(id){
			var url_val = "<?php echo URL::to('/');?>";
			$.ajax({
		        type:'POST',
		        dataType:"json",
		        data:{'id':id,'_token':$('meta[name="csrf-token"]').attr('content')},
		        url: url_val+"/admin/areaofwork/deleteMapRegion",
	        	success:function(data) {
	          		if (data){
	          			alert("Region Delete Successfully");
	          			window.location.reload();
	          		} else{
	          			alert("Region Not Delete");
	          			window.location.reload();
	          		}
	        	}
			});

		}

		$("#save_select_area").detach().insertAfter('.map_sctn');
		$("#save_select_area").on('click',function() {
			var latLngs = [];
			var lats = [];
			var lngs = [];
			var region_name = $('#region_name_value').val();
			if (region_name == "") {
		        alert("Enter The Region Name");
		        return false;
		    }

            var path = poly.getPath();
            var contentLength = path.getLength();
			for (var i=0;i< contentLength;i++) {
                var lat = path.getAt(i).lat();
                var lng  = path.getAt(i).lng();
				// var lat = markers[i].getPosition().lat();
				// var long = markers[i].getPosition().lng();
				lats.push(lat);
				lngs.push(lng);
				latLngs.push({lat: lat, lng: lng});
			}
			lats.sort();
		    lngs.sort();
		    lowx = lats[0];
		    highx = lats[contentLength - 1];
		    lowy = lngs[0];
		    highy = lngs[contentLength - 1];
		    center_x = lowx + ((highx-lowx) / 2);
		    center_y = lowy + ((highy - lowy) / 2);
		    center = new google.maps.LatLng(center_x, center_y);
			center_positions = ({lat: center.lat(), lng: center.lng()});
			if(latLngs=="")
			{
				alert('Please select area first');
				return false;
			}

			var url_val = "<?php echo URL::to('/');?>";
			$.ajax({
		        type:'POST',
		        dataType:"json",
		        data:{"latlongs":latLngs,"region_name":region_name,"center_positions":center_positions,'_token':$('meta[name="csrf-token"]').attr('content')},
		        url: url_val+"/admin/areaofwork/saveMapRegions",
	        	success:function(data) {
	          		if (data){
	          			alert("Region Save Successfully");
	          			window.location.reload();
	          		} else{
	          			alert("Region Not Saved");
	          			window.location.reload();
	          		}
	        	}
			});
		});

	</script>
@endsection