<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Thrift Shopper</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  
  <link rel="shortcut icon" href="{{ URL::asset('public/images/favicon.png')}}" sizes="32x32" type="image/x-icon">
  
  <link rel="stylesheet" href="{{ URL::asset('public/css/bootstrap.min.css')}}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ URL::asset('public/css/font-awesome.min.css')}}">
  <link rel="stylesheet" href="{{ URL::asset('public/css/ionicons.min.css')}}">
  <link rel="stylesheet" href="{{ URL::asset('public/css/dataTables.bootstrap.min.css')}}">
  <link rel="stylesheet" href="{{ URL::asset('public/css/AdminLTE.min.css')}}">
  <link rel="stylesheet" href="{{ URL::asset('public/css/_all-skins.min.css')}}"> 
  <link rel="stylesheet" href="{{ URL::asset('public/css/bootstrap3-wysihtml5.min.css')}}">

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic"/>
  
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <header class="main-header">
    @include('admin.elements.header')
  </header>
  <!-- Left side column. contains the logo and sidebar -->
  <aside class="main-sidebar">
   @include('admin.elements.sidebar')
  </aside>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    @yield('content')
  </div>  
  <footer class="main-footer">
    <div class="pull-right hidden-xs">
     
    </div>
    <strong>Copyright &copy; 2018-2019 .</strong> All rights reserved.
  </footer>

</div>
<!-- ./wrapper -->

<!-- jQuery 3 -->
<script src="{{ URL::asset('public/js/jquery.min.js')}}"></script>
<script src="{{ URL::asset('public/js/bootstrap.min.js')}}"></script>

<script src="{{ URL::asset('public/js/jquery.dataTables.min.js')}}"></script>
<script src="{{ URL::asset('public/js/dataTables.bootstrap.min.js')}}"></script>
<script src="{{ URL::asset('public/js/jquery.slimscroll.min.js')}}"></script>

<script src="{{ URL::asset('public/js/fastclick.js')}}"></script>
<script src="{{ URL::asset('public/js/adminlte.min.js')}}"></script>
<script src="{{ URL::asset('public/js/demo.js')}}"></script>
<script src="{{ URL::asset('public/js/ckeditor/ckeditor.js')}}"></script>
<script src="{{ URL::asset('public/js/bootstrap3-wysihtml5.all.min.js')}}"></script>


<script src="{{ URL::asset('public/js/custom.js')}}"></script>

<script src="{{ URL::asset('public/js/jquery.validate.js')}}"></script>

<script src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/3/jquery.inputmask.bundle.js"></script>



<!-- page script -->

<script type="text/javascript">
  
  jQuery().ready(function() {

    // validate form on keyup and submit
    var v = jQuery("#basicform").validate({
      rules: {
        storeName: {
          required: true,
          minlength: 2,
          maxlength: 16
        },
		storeStreet: {
          required: true,
          minlength: 2,
          maxlength: 100
        },
        storeCity: {
          required: true,
          minlength: 2,
          maxlength: 16
        },
		storeState: {
          required: true,
          minlength: 2,
          maxlength: 16
        },
         storeZip: {
          required: true,
          minlength: 5,
          maxlength: 8,
		  number: true

        },
         storePhone: {
          required: true,
         /*  minlength: 8, */
          /* maxlength: 12, */
		  /* number: true */
        },
         storeWeb: {
          required: true,
          minlength: 2,
          maxlength: 100
        },
        storeEmail: {
          required: true,
          minlength: 2,
          email: true,
          maxlength: 100,
        },
        storeHours: {
          required: true,
          minlength: 6,
          maxlength: 100,
        },
        storeDonateHours: {
          required: true,
          minlength: 6,
          maxlength: 100,
        },
        storeCharity: {
          required: true,
          minlength: 6,
          maxlength: 100,
        },
        storeFax: {
          required: true,
          /* minlength: 8,
          maxlength: 12,
		  number: true */
        }

      },
      errorElement: "span",
      errorClass: "help-inline-error",
    });

	jQuery("#storePhone").inputmask({"mask": "(999) 999-9999"});
	jQuery("#storeFax").inputmask({"mask": "(999) 999-9999"});
	
	jQuery("#storeEmail").inputmask({ alias: "email"});

	
    $(".open1").click(function() {
      if (v.form()) {
        $(".frm").hide("fast");
        $("#sf2").show("slow");
      }
    });

    $(".open2").click(function() {
      if (v.form()) {
        $(".frm").hide("fast");
        $("#sf3").show("slow");
      }
    });
	
	$(".open3").click(function() {
      if (v.form()) {
        $(".frm").hide("fast");
        $("#sf4").show("slow");
      }
    });
    
    /* $(".open4").click(function() {
      if (v.form()) {
        $("#loader").show();
         setTimeout(function(){
           $("#basicform").html('<h2>Thanks for your time.</h2>');
         }, 1000);
        return false;
      }
    }); */
    
    $(".back2").click(function() {
      $(".frm").hide("fast");
      $("#sf1").show("slow");
    });

    $(".back3").click(function() {
      $(".frm").hide("fast");
      $("#sf2").show("slow");
    });
	
	$(".back4").click(function() {
      $(".frm").hide("fast");
      $("#sf3").show("slow");
    });

  });
</script>


<script>
  var SITE_URL='<?php echo URL::to('/'); ?>';
  $(function () {
    $('#example1').DataTable()
    $('#example2').DataTable({
      'paging'      : true,
      'lengthChange': false,
      'searching'   : false,
      'ordering'    : true,
      'info'        : true,
      'autoWidth'   : false
    })
  })
</script>
</body>
</html>
