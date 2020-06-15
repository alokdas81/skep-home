<!DOCTYPE html>
<html>
<head>
<title>Forgot Email</title>
</head>
<body>

<p>Dear {{ $user->username }},</p>

<p>You have recently requested a password reset. Your new password is : <b><?php echo $password; ?></b></p>

<p>Thank you for taking part in our community!  </p>
 

</body>
</html>