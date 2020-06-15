<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
@extends('layouts.admin')

@section('content')

<section class="content-header">
	<div class="total_area_work_section" style="width:100%;">
		<h1 style="text-align: center; display:inline-block;padding-left: 40px;">Select Area Of Work</h1>
	</div>
</section>
<div class="background_display">
	<div class="map_sctn">
		<div id="map" style="width: 100%; height: 400px;"></div>
	</div>
</div>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo env("GOOGLE_MAP_API_KEY");?>
"></script>
<script>
	var iconBase = "@php echo asset('public/images/mapsofjobs'); @endphp";	
	var icons = {
		past: {
		    icon: iconBase + '/bookings-past.png'
		},
	  	upcomings: {
	    	icon: iconBase + '/bookings.png'
	  	},
	};
	var features = [
	<?php foreach($upcoming_services as $key=>$data){?>	
		{
			position: new google.maps.LatLng(<?php echo $data['latitude']?>, <?php echo $data['longitude'];?>),
        	type: 'upcomings'
    	},
	<?php }?>
	];

	var features1 = [
		<?php foreach($past_services as $key=>$past_data){?>	
		{
			position: new google.maps.LatLng(<?php echo $past_data['latitude'];?>, <?php echo $past_data['longitude'];?>),
        	type: 'past'
    	},
	<?php }?>
	];

	var map = new google.maps.Map(document.getElementById('map'), {
      zoom: 1,
      center: new google.maps.LatLng(43.647087, -79.373274),
      mapTypeId: google.maps.MapTypeId.ROADMAP
    });

    //var infowindow = new google.maps.InfoWindow();

    var marker, i;

    for (var i = 0; i < features.length; i++) {
      var marker = new google.maps.Marker({
        position: features[i].position,
        icon: icons[features[i].type].icon,
        map: map
      });
    }
    for (var i = 0; i < features1.length; i++) {
      var marker = new google.maps.Marker({
        position: features1[i].position,
        icon: icons[features1[i].type].icon,
        map: map
      });
    }
  </script>
</script>
@endsection