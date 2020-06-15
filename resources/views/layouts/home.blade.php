<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Thrift Shopper</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  
  <link rel="shortcut icon" href="{{ URL::asset('public/images/favicon.png')}}" sizes="32x32" type="image/x-icon">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="{{ URL::asset('public/css/main.css')}}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ URL::asset('public/css/custom.css')}}">
 
</head>
<body class="hold-transition skin-blue sidebar-mini">
@include('front.elements.header-home')

    @yield('content')
  
@include('front.elements.footer-home')



<div class="popup" pd-popup="SignUpPopup">
    <div class="popup-inner">
      <div class="popup-heading">
        <h3>Sign Up</h3>
      </div>
      <div class="popup-info">
        <form>
          <div class="form-group text-center">
            <div class="my-custom-radio-group">
              <div class="my-custom-radio-control">
                <label><input type="radio" name="as" checked><span class="custom-checkbox"></span> As User</label>
              </div>
              <div class="my-custom-radio-control">
                <label><input type="radio" name="as"><span class="custom-checkbox"></span> As Business</label>
              </div>

            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <input type="text" class="form-control" placeholder="First Name">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <input type="text" class="form-control" placeholder="Last Name">
              </div>
            </div>
            <div class="col-sm-12">
              <div class="form-group">
                <input type="text" class="form-control" placeholder="Email">
              </div>
            </div>
            <div class="col-sm-12">
              <div class="form-group">
                <input type="text" class="form-control" placeholder="Phone (Optional)">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <input type="text" class="form-control" placeholder="Password">
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <input type="text" class="form-control" placeholder="Confirm Password">
              </div>
            </div>
            
          </div>
          <div class="text-center">
              <input type="submit" value="Sign Up" class="btn">
            </div>
        </form>
        <div class="or">
          <p>Or Sign Up with</p>
        </div>
        <div class="sign-group">
          <a href="#" class="fb-btn"><i class="fa fa-facebook"></i> Facebook</a>
          <a href="#" class="google-btn"><i class="fa fa-google-plus"></i> Google</a>
        </div>
        <div class="already-acc">
          <p><a href="#" pd-popup-close="SignUpPopup" pd-popup-open="LogInPopup">Already have an Account? Log In</a></p>
        </div>
      </div>

      <a class="popup-close" pd-popup-close="SignUpPopup" href="#"> </a>
    </div>
  </div>

  <div class="popup" pd-popup="LogInPopup">
      <div class="popup-inner">
        <div class="popup-heading">
          <h3>Log In</h3>
        </div>
        <div class="popup-info">
          <form>
            <div class="form-group text-center">
              <div class="my-custom-radio-group">
                <div class="my-custom-radio-control">
                  <label><input type="radio" name="as" checked id="users"><span class="custom-checkbox"></span> As User</label>
                </div>
                <div class="my-custom-radio-control">
                  <label><input type="radio" name="as" id="business"><span class="custom-checkbox"></span> As Business</label>
                </div>
  
              </div>
            </div>
            <div class="row">
              
              <div class="col-sm-12">
                <div class="form-group">
                  <input type="text" class="form-control" placeholder="Email">
                </div>
              </div>
              <div class="col-sm-12">
                <div class="form-group">
                  <input type="text" class="form-control" placeholder="Password">
                  <div class="text-right ">
                      <p><a href="#">Forgot Password?</a></p>
                  </div>
                  
                </div>
                
              </div>
              
              
            </div>
            <div class="text-center">
                <input type="submit" value="Log In" class="btn">
              </div>
          </form>
          <div class="or">
            <p>Or Sign Up with</p>
          </div>
          <div class="sign-group">
            
            <a href="{{url('/facebook/redirect')}}" class="fb-btn" id="facebooklogin"><i class="fa fa-facebook"></i> Facebook</a>
			
			<a href="{{ url('/redirect') }}" class="btn btn-primary" id="gmaillogin" ><i class="fa fa-google-plus"></i> Google</a>
          </div>
          <div class="already-acc">
            <p><a href="#" pd-popup-close="LogInPopup" pd-popup-open="SignUpPopup">Don’t have an Account? Sign Up</a></p>
          </div>
        </div>
  
        <a class="popup-close" pd-popup-close="LogInPopup" href="#"> </a>
      </div>
    </div>

  
<!-- jQuery 3 -->
<script src="{{ URL::asset('public/js/jquery.min.js')}}"></script>
<script src="{{ URL::asset('public/js/popper.min.js')}}"></script>
<script src="{{ URL::asset('public/js/bootstrap.min.js')}}"></script>
<script src="{{ URL::asset('public/js/owl.carousel.min.js')}}"></script>
<script src="{{ URL::asset('public/js/custom.js')}}"></script>
<script>
$('#users').click(function() {
    $('#gmaillogin').attr('href', "{{ url('/redirect1') }}");
});

$('#business').click(function() {
    $('#gmaillogin').attr('href', "{{ url('/redirect') }}");
});

</script>

<!-- page script -->
</body>
</html>
