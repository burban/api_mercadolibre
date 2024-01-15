<?php
session_start();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://www.algunsitio.cl/wp-json/jwt-auth/v1/token',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS => array('username' => $_POST['usuario'],'password' => $_POST['password']),
	));

	$result = curl_exec($curl);
	$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$error = curl_error($curl);
	
	error_log(" Estamos login.php : ".print_r($result,true));
	
	curl_close($curl);
	if ($statusCode == 200){
	    
		$result = json_decode($result);
		
		error_log(" login.php statuscode : ".$statusCode." TKN:".$result->token);
		
		$_SESSION['token']= $result->token;
		$_SESSION['userEmail']= $result->user_email;
		$_SESSION['username']= $result->user_display_name;
		echo "<script>location.href='index.php';</script>";
		//exit(header("Location: index.php"));
	}
	else {
		$result = json_decode($result);
		$error = $result->message;
		//echo $error;
		//exit();
	}
}else{
	session_destroy();
}
?>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login API MQ</title>
	<link href="css/mq-site-api.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login">
    <div class="login-dark">
        <form method="post" action="login.php">
            <h2 class="sr-only">Login Form</h2>
            <div class="text-center"><img src="img/logo.png" alt="MQ"></div>
            <div class="form-group"><input class="form-control" type="usuario" name="usuario" placeholder="Usuario"></div>
            <div class="form-group"><input class="form-control" type="password" name="password" placeholder="Contrase&ntilde;a"></div>
            <div class="form-group"><button id="send-form" class="btn btn-primary btn-block" type="submit">Ingresar</button></div>
            <div class="alert alert-danger" role="alert"><?php if(!empty($error)){echo $error;} ?></div>
		</form>
    </div>
	<script src="vendor/jquery/jquery.min.js"></script>
</body>
</html>