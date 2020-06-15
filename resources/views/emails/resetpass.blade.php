<head>
  <title>Reset Password</title>
  <link rel="stylesheet" href="<?php echo url('/public/email_assets/css/bootstrap.min.css'); ?>" />
  <link rel="stylesheet" href="<?php echo url('/public/email_assets/css/custom.css'); ?>" />
  <link rel="icon" href="<?php echo url('/public/email_assets/icon.png'); ?>">
</head>
<div class="container custom">
  <h2>Reset Password</h2>

  @if(Session::has('message')) <p class="alert {{ Session::get('alert-class', 'alert-info') }}">{{ Session::get('message') }}</p> @endif
  
  <form class="form-horizontal" id="validationForm" name="validationForm" method="POST" action="{{url('/auth/saveResetPass')}}" >
    {{ csrf_field() }}
    {{ method_field('POST') }}
    <div class="form-group">
      <label class="control-label col-sm-6" for="pwd">New Password:</label>
      <div class="col-sm-10">
        <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" >
      </div>
    </div>

    <div class="form-group">
      <label class="control-label col-sm-6" for="pwd">Confirm Password:</label>
      <div class="col-sm-10">
        <input type="password" class="form-control" name="password_confirm" id="password_confirm" placeholder="Enter confirm password">
      </div>
    </div>

    <input type="hidden" name="user" value="{{ $user }}">
    <input type="hidden" name="usertoken" value="{{ $usertoken }}">

    <div class="form-group">
      <div class="col-sm-offset-2 col-sm-10">
        <button type="submit" name="reset" class="btn btn-default">Reset</button>
      </div>
    </div>
  </form>
</div>
<!--style for error class-->
<style>
    .error{
        color:red;
    }
</style>
<script src="<?php echo url('/public/email_assets/js/jquery.min.js'); ?>"></script>
<script src="<?php echo url('/public/email_assets/js/jquery.validate.js'); ?>"></script>
<script>
  $(function() {
    // Setup form validation on the #log-in element
    $("#validationForm").validate({
        // Specify the validation rules
        rules:
        {
            password:
            {
                required: true,
                minlength: 6,
                maxlength: 16
            },
            password_confirm : {
                required: true,
                equalTo : "#password"
              }
        },
        // Specify the validation error messages
        messages: {
        password: {
                required: "This field is required.",
                minlength: "Password must be of minimum 6 characters."
            },
            password_confirm : {
              equalTo: "Password and confirm password does not match."
            }
        }
    });
  });

</script>