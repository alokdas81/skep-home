<!DOCTYPE html>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Forgot Email</title>
</head>
<body>

<table cellspacing="0" border="0" align="center" cellpadding="0" width="600" style="border:1px solid #ccc; margin-top:10px;">
			<tr>
				<td>
					<table cellspacing="0" border="0" align="center" cellpadding="20" width="100%">
				
						<tr align="center" >
							<td style="font-family:arial; padding-bottom:40px;"><strong>
							<!-- <img src="'.URL::to('/public/email/').'/'.'Logonew.jpg" alt="Kizz"></img> -->
							<h2>Skep</h2>
							</strong></td>
						</tr>
					</table>
					<table cellspacing="0" border="0" align="center" cellpadding="10" width="100%" style="border:0px solid #efefef; margin-top:0px; padding:40px;">
						<tr>
						<td><h2>Hello {{ $username }}</h2></td>
						</tr>
						<tr>
						<td><p><h4>Please click <a href="{{ $pass_url }}">here</a> to reset your password.<h4></p></td>
						</tr>
						<tr>
							<td>
								<table cellspacing="0" border="0" cellpadding="0" width="100%">	
									<tr>
										<td><h3>Best Regards,</h3>
											<h3>SKEP Team</h3>
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
							<b>SKEP Team</b></strong></td> 
						</tr>
					</table>
				</td>   
			</tr>
		</table>

</body>
</html>