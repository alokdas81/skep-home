<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="{!! csrf_token() !!}">
  <title>SKEP</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  
  <link rel="shortcut icon" href="{{ URL::asset('public/images/favicon.png')}}" sizes="32x32" type="image/x-icon">
  
  <link rel="stylesheet" href="{{ URL::asset('public/css/bootstrap.min.css')}}">
  <!--link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"-->
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ URL::asset('public/css/font-awesome.min.css')}}">
  <link rel="stylesheet" href="{{ URL::asset('public/css/ionicons.min.css')}}">
  <link rel="stylesheet" href="{{ URL::asset('public/css/dataTables.bootstrap4.min.css')}}">

  <link rel="stylesheet" href="{{ URL::asset('public/css/AdminLTE.min.css')}}">
  <link rel="stylesheet" href="{{ URL::asset('public/css/_all-skins.min.css')}}"> 
  <link rel="stylesheet" href="{{ URL::asset('public/css/bootstrap3-wysihtml5.min.css')}}">
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="https://jqueryui.com/resources/demos/style.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.0/css/lightbox.css" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic"/>
  <link href="https://www.malot.fr/bootstrap-datetimepicker/bootstrap-datetimepicker/css/bootstrap-datetimepicker.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
  
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
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.3.7/js/tether.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.5/js/bootstrap.min.js"></script>

<script src="{{ URL::asset('public/js/jquery.dataTables.min.js')}}"></script>
<script src="{{ URL::asset('public/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{ URL::asset('public/js/jquery.slimscroll.min.js')}}"></script>

<script src="{{ URL::asset('public/js/fastclick.js')}}"></script>
<script src="{{ URL::asset('public/js/adminlte.min.js')}}"></script>
<script src="{{ URL::asset('public/js/demo.js')}}"></script>
<script src="{{ URL::asset('public/js/bootstrap3-wysihtml5.all.min.js')}}"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<script src="{{ URL::asset('public/js/custom.js')}}"></script>
<script src="{{ URL::asset('public/js/common.js')}}"></script>

<script src="{{ URL::asset('public/js/jquery.validate.js')}}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.0/js/lightbox.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>

<script src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/3/jquery.inputmask.bundle.js"></script>
<script src="https://www.malot.fr/bootstrap-datetimepicker/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js"></script>

<script type="text/javascript">
  $('#example1').DataTable();
  $( function() {
    $( "#date_of_birth" ).datepicker();
    $('#service_start_time').datetimepicker();
  } );
</script>
</body>
</html>
