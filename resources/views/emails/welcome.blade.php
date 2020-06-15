<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head> 
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<link href="http://fonts.googleapis.com/css?family=PT+Sans" rel="stylesheet" type="text/css">
	<title>Email Template - Classic</title>
</head>

	<body marginheight="0" topmargin="0" marginwidth="0" style="bgcolor:blue ;margin: 0px; font:12px arial; color:#000;">
	
		<table cellspacing="0" border="0" align="center" cellpadding="0" width="600" style="border:1px solid #ccc; margin-top:10px;">
			<tr>
				<td>
					<table cellspacing="0" border="0" align="center" cellpadding="20" width="100%">
				
						<tr align="center" >
							<td style="font-family:arial; padding-bottom:40px;"><strong>
							<img src="{{ url('/').'/public/images/logo.png' }}" alt="IBREEZE"></img>
							</strong></td>
						</tr>
					</table>
					<table cellspacing="0" border="0" align="center" cellpadding="10" width="100%" style="border:0px solid #efefef; margin-top:0px; padding:40px;">
						<tr>
						<td><h2>Hello {{ $name }},</h2></td>
						</tr>
						<tr>
						<td><p><h4>You have send request get your password.<h4></p></td>
						</tr>
						<tr>
						<td><p><h4>Your password has been updated with auto generated system.<h4></p></td>
						</tr>
						<tr>
						<td><p><h4>You New passowrd is: {{ $password }} <h4></p></td>
						</tr>
						<tr>
							<td>
								<table cellspacing="0" border="0" cellpadding="0" width="100%">	
									<tr>
										<td><h3>Best Regard</h3>
											<h3>The Thrift Shopper</h3>
										</td>
									</tr>
								</table>
							</td>
							<td width="30"></td> 
						</tr>
					</table>
					<table cellspacing="0" border="0" align="center" cellpadding="0" width="100%" style="border:0px solid #efefef; margin-top:20px; padding:0px;">
						<tr>
							<td align="center" style="font-family:PT Sans,sans-serif; font-size:13px; padding:15px 0; border-top:1px solid #efefef;"> 
							<b>The Thrift Shopper</b></strong></td> 
						</tr>
					</table>
				</td>   
			</tr>
		</table>
	</body>
</html>
