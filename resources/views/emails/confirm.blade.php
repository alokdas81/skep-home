<!DOCTYPE html>

<html>

<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<title>Welcome Email</title>

</head>

<body>



<p>Dear {{ @$user->first_name }},</p>



<p>You have recently registered to join the SKEP. Please verify your account by clicking on the link:</p>



<p><b><a href="{{ @$link }}">Link</a></b></p>

 

<p>We welcome you into our community with open arms. Have fun!</p>

<p>Thanks,<br>
Team Skep</p>
</body>

</html>