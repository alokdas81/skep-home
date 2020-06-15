$(document).ready(function(){
$('#profile-img-tag').css('display','none');   
$("#service_image").change(function (e) {
        $('.upload_image').css('display','none');
        
	    for (var i = 0; i < e.originalEvent.srcElement.files.length; i++) {
	    var file = e.originalEvent.srcElement.files[i];
	    var img = document.createElement("img");
	    var reader = new FileReader();
	    reader.onloadend = function () {
	    img.src = reader.result;
	    $('#profile-img-tag').css('display','block');
	    $('#profile-img-tag').attr('src', img.src);
	    }
	    reader.readAsDataURL(file);
	    e.preventDefault();
	    }
	});

	$('#unselected-img-tag').css('display','none');   
	$("#unselected_image").change(function (e) {
	        $('.unselected_image').css('display','none');
	        
		    for (var i = 0; i < e.originalEvent.srcElement.files.length; i++) {
		    var file = e.originalEvent.srcElement.files[i];
		    var img = document.createElement("img");
		    var reader = new FileReader();
		    reader.onloadend = function () {
		    img.src = reader.result;
		    $('#unselected-img-tag').css('display','block');
		    $('#unselected-img-tag').attr('src', img.src);
		    }
		    reader.readAsDataURL(file);
		    e.preventDefault();
		    }
		});
	$('#service_start_time').datetimepicker();
	$('#service_end_time').datetimepicker();
	$('#booking_date').datepicker();
	$('#extra_services').select2();

	$("#user_id").change(function() {
	  if ($(this).data('options') === undefined) {
	    /*Taking an array of all options-2 and kind of embedding it on the select1*/
	    $(this).data('options', $('#space_id option').clone());
	  }
	  var id = $(this).val();
	  var options = $(this).data('options').filter('[user-id=' + id + ']');
	  $('#space_id').html(options);
	});

	
	
	$('#send_message').on('click',function(){
		var email = $('.user_email').val();
		var title = $('.title_value').val();
		var description = $('.description_value').val();
		var job_id = $('#job_id').val();
		
		var user_id = $('#user_id').val();
		//alert(job_id+'==========='+user_id);
		var csrf_token = "<?php echo csrf_token();?>";
		$.ajax({
        type:'POST',
        data:{"_token":$('meta[name="csrf-token"]').attr('content'),"email":email,'title':title,'description':description,'job_id':job_id,'user_id':user_id},
        url: "sendResponseMail",
        success:function(data) {
          alert(data);
        }
    });
	});	

	var windowURL = window.location.href;
	pageURL = windowURL.substring(0, windowURL.lastIndexOf('/'));
	var x= $('a[href="'+pageURL+'"]');
	x.addClass('active');
	x.parent().addClass('active');
	var y= $('a[href="'+windowURL+'"]');
	y.addClass('active');
	y.parent().addClass('active');	
});
