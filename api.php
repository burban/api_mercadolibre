<?php
set_time_limit(0);
ini_set('max_execution_time', 0);
//ignore_user_abort();

error_log("Estamos en api.php");

if(!isset($_SESSION)) 
{ 
    session_start(); 
} 

//ini_set('display_startup_errors', 1);
//ini_set('display_errors', 1);
//error_reporting(-1);
//set_time_limit(0);
//ini_set('max_execution_time', 0);
//ignore_user_abort();

//CONSTANTS
//$client_id = "8018508322850186"; //1129320816236476
$client_id = "3886350143980387";
//$client_secret = "qiudXLcMvAUhxzjd6i1r4qGgA8UyhifF"; //sNyXOPPhOrfGkOdht7Bj4mx4JuXgCfwV
// $client_secret = "fNRRo2pRnlqbX47jdVBfBovCGgUbACdl"; 
$client_secret = "36VSxKnfQ8W98b0T6UMP1mPCfrLjRvkc"; 
$redirect_uri = "https://www.quindosmontalva.cl/api/index.php";
$sellerId = "292006485";
$categoriaInmubles = "MLC1459";

$arrayPublicacionesActualizadas = array();//Para información envío de correo
$cantidadRegistroAPI = 0;


require_once('../wp-load.php');



if (isset($_POST['inmuebles'])){
	echo getInmuebles($_SESSION['token-ml']);
}
else if (isset($_POST['addInmuebles'])){
	echo addInmuebles($_SESSION['token-ml']);
}
else if (isset($_POST['correos'])){
	echo getCorreos();
}
else if (isset($_POST['updateCorreos'])){
	echo updateCorreos($_POST['updateCorreos']);
}
else if (isset($_GET['autoMeli'])){
	if ($_GET['autoMeli']==true){
		echo automatico();
	}
}
else {
 //Else agregado como test...   
 error_log("api.php No tiene GET[] NI POST[]");
 
}


//FUNCIONES OK

function getOption($name){
	require_once('../wp-load.php');
	return get_option($name);
}

function getCorreos(){
	//addOption("correos_meli","");
	$emails = getOption("correos_meli");
	$arrayEmail = explode(";", $emails);
	if(count($arrayEmail) == 1){
		if ($arrayEmail[0] == ""){
			unset($arrayEmail[0]);
		}
	}
	$response = new stdClass;
	$response->data = array();
	$count = 1;
	foreach($arrayEmail as $email) {
		$object = new stdClass();
		$object -> email = $email;
		$object -> registro = $count;
		$response->data[] = $object;

		$count = $count + 1;
	}
	echo json_encode([
		"data" => $response->data,
	]);
}

function getAuthorization() {
	$authorize_url = "https://auth.mercadolibre.cl/authorization";
	$authorization_redirect_url = $authorize_url . "?response_type=code&client_id=" . $GLOBALS['client_id']. "&redirect_uri=" . $GLOBALS['redirect_uri'];
	error_log("authorization_redirect_url:: ".$authorization_redirect_url);
    header("Location:". $authorization_redirect_url);
  // echo "Go <a href='$authorization_redirect_url'>here</a>, copy the code, and paste it into the box below.<br /><form action=" . $_SERVER["PHP_SELF"] . " method = 'post'><input type='text' name='authorization_code' /><br /><input type='submit'></form>";
}

function getToken($code){
	$token_url = "https://api.mercadolibre.com/oauth/token";
	$curl = curl_init();
	
	$data = array('grant_type'=>'authorization_code',
              'client_id'=>$GLOBALS['client_id'],
			  'code'=> $code,
              'client_secret'=>$GLOBALS['client_secret'],
              'redirect_uri'=>$GLOBALS['redirect_uri']);
	$data = http_build_query($data);
	
	curl_setopt_array($curl, array(
	  CURLOPT_URL => $token_url,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS => $data,
	  CURLOPT_HTTPHEADER => array(
		'accept: application/json',
		'content-type: application/x-www-form-urlencoded'
	  ),
	));

	$result = curl_exec($curl);
	$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$error = curl_error($curl);
	curl_close($curl);
	
	if ($statusCode == 200){
		$result = json_decode($result);
		$token = $result->access_token;
		$_SESSION['token-ml']= $token;
		addOption('refresh_token',$result->refresh_token);
		echo "<script>console.log('token meli: " . $token . "' );</script>";
	}
	else{
		$result = json_decode($result);
		$error= $result->message;
		error_log("error api.php " .$error);
		echo $error;
	}
}

function addOption($name, $value){
    
	require_once('../wp-load.php');
	if(get_option($name)){
		update_option($name, $value);
	}
	else {
		add_option($name, $value);
	}
}

function getInmuebles($token, $offSet = 0, $dataNew = array()){
        
    error_log("getInmuebles() api.php ");
    //$para = getOption("correos_meli");
    
    $userAgent = 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31';
    $curl = curl_init();
	$data = array('Authorization'=>'Bearer ' .$token);
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.mercadolibre.com/sites/MLC/search?category='.$GLOBALS['categoriaInmubles'].'&seller_id='.$GLOBALS['sellerId'].'&offset=' .$offSet,  
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_USERAGENT => $userAgent,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'GET',
	  CURLOPT_HTTPHEADER => $data
	));

	$result = curl_exec($curl);
	$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$error = curl_error($curl);
	curl_close($curl);
	if ($statusCode == 200){
		$result = json_decode($result);
		$dataResult = $result->results;
		
		//error_log("INFO PROPIEDADES OBTENIDAS:: ". print_r($dataResult,true)); /*23-01-2023 17:35*/
		
		$total = $result->paging->total;	
		$newOffSet = $offSet + 50;
		if (!in_range($total, $offSet, $newOffSet)){
			if ($offSet > 1){
				$dataResult = array_merge(json_decode(json_encode($dataResult),true) , $dataNew);
			}
			return getInmuebles($token, $newOffSet, $dataResult);
		}
		if($offSet > 1){
			$dataResult = array_merge(json_decode(json_encode($dataResult),true) , $dataNew);
		}
        
        //TEST ENV�0�1O MAIL
        //sendMail($para, "LXY - TEST API Sincronizacion Registros MQ-MercadoLibre", "Probando SINCRO... , </br></br><b>TOTAL REGISTROS:".$total."</b> </br></br> <b> <i> TEST : </i> </b> </br>" );
        
		return json_encode([
			"data" => $dataResult,
		]);
	}
	else if ($statusCode == 401){
		return tokenExpire();
	}
	else{
		$result = json_decode($result);
		$error= $result->message;
		error_log("se cae servicio getInmuebles error: " .$error);
		return $error;
	}	
}

function in_range($number=0, $value1=0, $value2=0){
    
	if($value1>$value2){
		$min = $value2;
		$max = $value1;
	}else{
		$min = $value1;
		$max = $value2;
	}
	if( $number <= $max AND $number >= $min ) return true;
	return false;
}

function getIndicadores(){
    
    error_log("getIndicadores(");
    
	$userAgent = 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31';
	$curl = curl_init();
	$hoy = date("d-m-Y");
	curl_setopt_array($curl, array(
	CURLOPT_URL => 'https://mindicador.cl/api/uf/' .$hoy,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_USERAGENT => $userAgent,
	CURLOPT_ENCODING => '',
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 0,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => 'GET',
	));

	$result = curl_exec($curl);
	$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$error = curl_error($curl);
	curl_close($curl);
	$valorUF = 0;
	if ($statusCode == 200){
		$result = json_decode($result);
		$valorUF = $result->serie[0]->valor;
	}else{
		error_log("servicio indicadores no responde");
	}

	return $valorUF;
}

function sendMail($para, $asunto, $mensaje){
	try{
	    
		$cabeceras  = 'MIME-Version: 1.0' . "\r\n";
		$cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$cabeceras .= 'From: No-Responder <no-reply@mq.cl>' . "\r\n";
		mail($para, $asunto, $mensaje, $cabeceras);
	}
	catch (Exception $e) {
		$error = "error mail " .$e->getMessage();
		error_log($error);
	}
}

function deleteAllPropiedades(){
    
    error_log("deleteAllPropiedades(");
    
    $args = array('post_type'=> 'propiedad', 'posts_per_page'=> -1);
	$post = query_posts($args);
	foreach($post as $itempost) {
		deleteWP(0,$itempost->ID);
	}
    
    global $wpdb;
    //where id_mercadolibre = 'MLC1897870754'
    //$post = $wpdb->get_row($wpdb->prepare( "SELECT * FROM propiedades where  id_mercadolibre = 'MLC1413941603';"));
    $propS = $wpdb->get_results( "SELECT * FROM propiedades ;",ARRAY_A);
	
	foreach($propS as $item) {
	    
		deleteWP($item["id"],0);
	}
}

function deleteWP($idregistropropiedad,$postid){
    
    error_log(" deleteWP(".$idregistropropiedad. " ".$postid);
    
    global $wpdb;
    
    if($idregistropropiedad != 0) {
        // Elimina registro de propiedad
        $wpdb->delete('propiedades', array('id' => $idregistropropiedad ));
        
        //Elimina los atributos de la propiedad por "id_�0�7propiedad"
        $wpdb->delete('attributes_propiedad', array('id_propiedad' => $idregistropropiedad ));
        
        //Elimina los registros de Ubicaci��n Coordenadas GPS
        $wpdb->delete('location_propiedad', array('id_propiedad' => $idregistropropiedad ));
        
        //Elimina las fotografias de la propiedad
        $wpdb->delete('propiedades_galeria', array('id_propiedad' => $idregistropropiedad ));    
    }
    else if($postid != 0)
    {
        //require_once('../wp-load.php');
        if(wp_delete_post( $postid, true ))
		    return true;
    	else
    		return false;
    		
    }
    
}

function automatico(){
    
    error_log("automatico(");
    
	$para = getOption("correos_meli");
	global $arrayPublicacionesActualizadas;
	global $cantidadRegistroAPI;
	
	try{
	    
		error_log("inicio automatico");
		$token = refreshToken();
		addInmuebles($token);

       $thead = "<table style='border:1px solid black;border-collapse:collapse;'>
                    <tr>
                        <th style='border:1px solid black;padding:4px;' scope='col'>#</th>
                        <th style='border:1px solid black;padding:4px;' scope='col'>CODIGO</th>
                        <th style='border:1px solid black;padding:4px;' scope='col'>TITULO</th>
                        <th style='border:1px solid black;padding:4px;' scope='col'>LINK</th>
                        <th style='border:1px solid black;padding:4px;' scope='col'>CREADO</th>
                        <th style='border:1px solid black;padding:4px;' scope='col'>ACTUALIZADO</th>
                  </tr>
        ";
		
		$tbody = "";
		$i = 1;
		foreach($arrayPublicacionesActualizadas as $items) {
		    
		    $tbody .= "
		        <tr>
		            <td style='border:1px solid black;padding:5px;'>".$i."</td>
                    <td style='border:1px solid black;padding:5px;'>".$items["id"]."</td>
                    <td style='border:1px solid black;padding:5px;'>".wp_strip_all_tags($items["titulo"])."</td>
                    <td style='border:1px solid black;padding:5px;'>".$items["link"]."</td>
                    <td style='border:1px solid black;padding:5px;'>".$items["fcCreated"]."</td>
                    <td style='border:1px solid black;padding:5px;'>".$items["fcUpdated"]."</td>
                </tr>
		    ";
		 $i++;
		    
		}
		
		$thead .= $tbody . " </table> ";
		
		sendMail($para, "TEST TEST quindosmontalva.cl || API Sincronizacion Registros MQ-MercadoLibre", "Sincronizacion realizada correctamente, </br></br><b>TOTAL PUBLICACIONES:".$cantidadRegistroAPI."</b> </br></br> <b> <i> ULTIMAS PUBLICACIONES: </i> </b> </br>".$thead );
		
		//sendMail($para, "API Sincronizacion Registros MQ-MercadoLibre", "Sincronizacion realizada correctamente, </br> <b>MODIFICAR api.php automatico() </b> </br></br> <i> Propiedades: </i> </br>" );
		
		error_log("fin automatico");
	}
	catch (Exception $e) {
		$error = "error automatico " .$e->getMessage();
		error_log($error);
		sendMail($para,"API Sincronizacion Registros MQ-MercadoLibre con Errores", "Sincronizacion realizada con errores: " .$error);
	}
}


//FUNCIONES OK


function tokenExpire(){
	error_log("token expire");
	if (isset($_SESSION['token-ml'])){
		session_destroy();
		header('Location:login.php');
		die();
	}else{
		throw new Exception('Token Expiro, debe ingresar al login de carga para generar un token nuevo');
	}
}

function refreshToken(){
    error_log("refreshToken(");
    
	$token_url = "https://api.mercadolibre.com/oauth/token";
	$refreshTK = getOption('refresh_token');
	$curl = curl_init();
	
	$data = array('grant_type'=>'refresh_token',
              'client_id'=>$GLOBALS['client_id'],
              'client_secret'=>$GLOBALS['client_secret'],
              'refresh_token'=>$refreshTK);
	$data = http_build_query($data);
	
	curl_setopt_array($curl, array(
	  CURLOPT_URL => $token_url,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS => $data,
	  CURLOPT_HTTPHEADER => array(
		'accept: application/json',
		'content-type: application/x-www-form-urlencoded'
	  ),
	));

	$result = curl_exec($curl);
	$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$error = curl_error($curl);
	curl_close($curl);
	
	
	
	if ($statusCode == 200){
		$result = json_decode($result);
		$token = $result->access_token;
		addOption('refresh_token',$result->refresh_token);
		return $token;
	}
	else{
		$result = json_decode($result);
		$error= $result->message;
		$messageError = "Error al refrescar el token " .$error;
		error_log($messageError);
		throw new Exception($messageError);
	}
}


function getCountriesCL($token){
    
    error_log("getCountriesCL(");
    
	$curl = curl_init();	
	$data = array('Authorization'=>'Bearer ' .$token);
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.mercadolibre.com/classified_locations/countries/CL',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'GET',
	  CURLOPT_HTTPHEADER => $data
	));

	$result = curl_exec($curl);
	$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$error = curl_error($curl);
	curl_close($curl);
	if ($statusCode == 200){
		$result = json_decode($result);
	}
	else if ($statusCode == 401){
		return tokenExpire();
	}
	else{
		$result = json_decode($result);
		$error= $result->message;	
		echo $error;
	}	
}

function getDescription($token, $id){
    error_log("getDescription(");
    
    $userAgent = 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31';
	$curl = curl_init();
	$data = array('Authorization'=>'Bearer ' .$token);
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://api.mercadolibre.com/items/'.$id.'/description',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_USERAGENT => $userAgent,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_HTTPHEADER =>  $data
	));

	$result = curl_exec($curl);
	$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$error = curl_error($curl);
	curl_close($curl);
	if ($statusCode == 200){
		$result = json_decode($result);
		$dataResult = $result->plain_text;
		return json_encode([
			"data" => $dataResult,
		]);
	}
	else if ($statusCode == 401){
		return tokenExpire();
	}
	else if ($statusCode == 404){
		return json_encode([
			"data" => "sin informacion de descripcion",
		]);
	}
	else{
		$result = json_decode($result);
		$error= $result->message;
		error_log("se cae servicio getDescription " .$id. " error: " .$error);
		return $error;
	}
}

function getItemML($token, $id){
    
    error_log("getItemML(");
    
    $userAgent = 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31';
	$curl = curl_init();
	$data = array('Authorization'=>'Bearer ' .$token);
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://api.mercadolibre.com/items/' .$id,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_USERAGENT => $userAgent,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_HTTPHEADER => $data
	));

	$result = curl_exec($curl);
	$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$error = curl_error($curl);
	curl_close($curl);
	if ($statusCode == 200){
		$result = json_decode($result);
		return json_encode([
			"data" => $result,
		]);
	}
	else if ($statusCode == 401){
		return tokenExpire();
	}
	else{
		$result = json_decode($result);
		$error= $result->message;	
		error_log("se cae servicio getItemML " .$id. " error: " .$error);
		return $error;
	}
}

function addInmuebles($token){
    
    error_log("addInmuebles(");
    $sellerId = "292006485";
    global $cantidadRegistroAPI;
	$valorUF = getIndicadores();
	//error_log("valor uf::" . $valorUF);
	
	$registros = getInmuebles($token);
	$registros = json_decode($registros, true);
	$cantidadRegistroAPI = count($registros["data"]);
	
	error_log("CANTIDAD REGISTROS:: ".$cantidadRegistroAPI);
    deleteAllPropiedades(); // debe estar activo

    error_log("inicio proceso");
    
    $diaSemana = date('N', strtotime(date("Y-m-d H:i:00"))); // D��a de la semana NUMERO => [1 (para lunes) hasta 7 (para domingo)]
    if($diaSemana == 1) {
        $textCantidadDias_Menos = "-2 day";
    }
    else 
    {
        $textCantidadDias_Menos = "-1 day";
    }
    
    $i = 0;
	foreach($registros["data"] as $item) {
		try {
		    $i++;
		    global $arrayPublicacionesActualizadas;
		        
		    $id = $item["id"];
			//error_log("procesando meli " .$id);
			$detalle = getItemML($token, $id);
			$detalle = json_decode($detalle, true);
			$descripcion = json_decode(getDescription($token, $id),true);

			if($detalle["data"]["status"] == "closed"){//publicacion pausada
				continue;
			}
			$cod = "";
			if (multiKeyExists($detalle["data"]["attributes"], "id", 'PROPERTY_CODE') !== FALSE){
				$key = array_search('PROPERTY_CODE', array_column($detalle["data"]["attributes"], 'id'));
				$cod = $detalle["data"]["attributes"][$key]["value_name"];
			}else{
				$cod = $id;
			}
			
			$descripcion = $descripcion["data"];
			$tmp_arr = array();
			foreach($detalle["data"]["pictures"] as $img) {
				$tmp_arr[] = $img["secure_url"];
			}
			
			$title = $item["title"];
			$operacion = "";
			$tipo = "";
			$siteId = $detalle["data"]["site_id"];
			$categoryID = $detalle["data"]["category_id"];
			$precio_post = $detalle["data"]["price"];
			$tipo_precio_post = $detalle["data"]["currency_id"];
			$permalink = $detalle["data"]["permalink"];

			$precio =  moneyFormat($item["price"], "CLP", $item["prices"]["prices"][0]["currency_id"]); // PESOS CON FORMATO $..
			$precio_ref = moneyFormat($item["price"], "CLP", $item["prices"]["prices"][0]["currency_id"]);  //PRECIO UF CON FORMATO UF ..
			$precio_busc = $item["price"]; // PRECIO $ SIN FORMATO
			$precio_ref_busc = $item["price"]; // PRECIO UF SIN FORMATO
			
			$precio_pesos = $item["price"];
			if($valorUF != 0){
				//if($item["prices"]["prices"][0]["currency_id"] == "CLF"){
				if($item["currency_id"] == "CLF"){
					//CLF == UF
					$precio = moneyFormat($valorUF * $item["price"], "CLP", "CLP");
					$precio_busc = round($valorUF * $item["price"]);
					$precio_pesos = round($valorUF * $item["price"]);
				}
			}
			
            // ######################################
            // PARA ENVIO MAIL 2023-01-26
	         
                
            //error_log(" publcaiones;:: ".print_r( $arrayPublicacionesActualizadas,true));
            
            /*error_log(" date_created:: ".date("d-m-Y H:i:s",strtotime($detalle["data"]["date_created"])));
                error_log(" last_updated:: ".date("d-m-Y H:i:s",strtotime($detalle["data"]["last_updated"])));
                error_log(" id:: ".$detalle["data"]["id"]);
                error_log(" id regsitros:: ".$id);
                error_log(" Fecha hoy:: ".date("d-m-Y H:i:00",time()));
                error_log(" Fecha last_updated -10dias ".date("d-m-Y H:i:s",strtotime('-10 day', strtotime($detalle["data"]["last_updated"]))));
                error_log(" Fecha hoy -10 dias ". date("d-m-Y H:i:s", strtotime('-10 day', strtotime(date("Y-m-d H:i:00"))) ));
            */
            
            //  strtotime($detalle["data"]["date_created"]) >  strtotime('-5 day', strtotime(date("Y-m-d H:i:00"))) || 
            //error_log("MENOS 2 dias::".  date("d-m-Y H:i:s", strtotime('-2 day', strtotime(date("Y-m-d"))) ));
            //error_log("DATE CREATED::".  date("d-m-Y H:i:s", strtotime($detalle["data"]["date_created"]) ));
            
            error_log("COD UPDATED :: " .$id );
            
            if( strtotime($detalle["data"]["date_created"]) > strtotime($textCantidadDias_Menos, strtotime(date("Y-m-d H:i:00"))) ) {
                
                array_push($arrayPublicacionesActualizadas,array(
                                                "id" => $id
                                                ,"titulo" => wp_strip_all_tags($title)
        			                            ,"link" => $permalink
        			                            , "fcCreated" => date("d-m-Y",strtotime($detalle["data"]["date_created"]))
        			                            , "fcUpdated" => date("d-m-Y",strtotime($detalle["data"]["last_updated"]))
            			                     ));
                
            }

            // ######################################
            
			$direccion = $item["location"]["address_line"] . ', ' . $item["location"]["city"]["name"] . ', ' . $item["location"]["state"]["name"];
			$mapa = "";
			$comuna = $item["location"]["city"]["name"];
			$state = $item["location"]["state"]["name"];
			$country = $item["location"]["state"]["country"];
			if($country == "")
			    $country = "Chile";
			    
			$address_line = $item["location"]["address_line"];
			$dormitorios = "0";
			$banos = "0";
			$area_util = "0";
			$areatotal = "0";
			$latx = $item["location"]["latitude"];
			$lonx = $item["location"]["longitude"];
			
			$start_time = $detalle["data"]["start_time"];
			if($start_time == "")
			    $start_time = "0";
			    
            $stop_time = $detalle["data"]["stop_time"];
            if($stop_time == "")
                $stop_time = "0";
                
            $end_time = $detalle["data"]["end_time"];
            if($end_time == "")
                $end_time = "0";
                
            $secure_thumbnail = $detalle["data"]["secure_thumbnail"];
            if($secure_thumbnail == "")
                $secure_thumbnail = "N/A";
                
            $status = $detalle["data"]["status"];
			
			foreach($item["attributes"] as $attributes) {
				if ($attributes["id"] == "OPERATION"){
					$operacion = $attributes["value_name"];
				}
				else if($attributes["id"] == "PROPERTY_TYPE"){
					$tipo = $attributes["value_name"];
				}
				else if($attributes["id"] == "BEDROOMS"){
					$dormitorios = $attributes["value_name"];
				}
				else if($attributes["id"] == "FULL_BATHROOMS"){
					$banos = $attributes["value_name"];
				}
				else if($attributes["id"] == "TOTAL_AREA"){
					$area_util = $attributes["value_struct"]["number"];
				}
				else if($attributes["id"] == "COVERED_AREA"){
					$areatotal = $attributes["value_struct"]["number"];
				}
			}
			
			$tmp_attributes = $detalle["data"]["attributes"];
			
			$propiedad = array(
		                    "codigo" => $cod,
							"titulo" => $title,
							"subtitle" =>  "",
							"seller_id" => $sellerId,
							"site_id" => $siteId,
							"category_id" => $categoryID,
							"price" => $precio_post,
							"tipo_precio" => $tipo_precio_post,
                            "start_time" => $start_time,
                            "stop_time" => $stop_time,
                            "end_time" => $end_time, 
                            "permalink" => $permalink,
                            "secure_thumbnail" => $secure_thumbnail,
                            "descriptions" => $descripcion,
                            "status" => $status,
                            "origen" => "mercadolibre",
                            "city" => $comuna,
                            "state" => $state,
                            "country" => $country,
                            "latitude" => $latx,
							"longitude" => $lonx,
							"address_line" => $address_line,
							"tmp_attributes" => $tmp_attributes,
							"precio" => $precio,
							"precio_ref" => $precio_ref,
							"precio_busc" => $precio_busc,
							"precio_ref_busc" => $precio_ref_busc,
							"tipo" => $tipo,
							"operacion" => $operacion,
							"direccion" => $direccion,
							"mapa" => $mapa,
							"dormitorios" => $dormitorios,
							"banos" => $banos,
							"area_util" => $area_util,
							"area_total" => $areatotal,
							"imagenes" => $tmp_arr,
							"precio_pesos" => $precio_pesos
						);
					
					//$user->data->ID
					
			addWP($propiedad,$propiedad);
			
			/*
			error_log(" ".print_r($propiedad,true));
			
			if($i > 2) {
			    break;
			}
			*/
			
			//var_dump($propiedad);
			
		}
		catch (Exception $e) {
			error_log("error data archivos " .$e->getMessage());
			throw $e;
		}
	}
	error_log("fin proceso");
	http_response_code(200);
	return http_response_code();
	//echo $registros;
	
}

function addWP($propiedad, $post){
    
    error_log("addWP(");
    /********************************************/
    /****Creamos post wordpress de propiedades***/
    /********************************************/
    error_log(' CREAMOS PROP�0�3EDAD ID:'.$propiedad["codigo"]);
    
    $my_post = array(
      'post_type' => 'propiedad',
      'post_title'    => wp_strip_all_tags($propiedad["titulo"]),
      'post_content'  => wp_strip_all_tags($propiedad["descriptions"]),
      'post_status'   => 'publish',
      'post_author'   => 1,
    );
     
    //Se crear nuevo post propiedad wordpress
    $postId =  wp_insert_post( $my_post );
    
    /*************************************/
    /**** Insertamos en tablas de API ****/
    /*************************************/
    global $wpdb;
    
    $wpdb->insert('propiedades', array(
          'id_mercadolibre' =>  $propiedad["codigo"],
          'site_id' => $propiedad["site_id"],
          'title' => wp_strip_all_tags($propiedad["titulo"]),
          'subtitle' =>  $propiedad["subtitle"], 
          'seller_id' => $propiedad["seller_id"], 
          'category_id' => $propiedad["category_id"], 
          'price' => $propiedad["price"], 
          'tipo_precio' => $propiedad["tipo_precio"],
          'start_time' => $propiedad["start_time"], 
          'stop_time' => $propiedad["stop_time"], 
          'end_time' => $propiedad["end_time"], 
          'permalink' => $propiedad["permalink"],
          'secure_thumbnail' => $propiedad["secure_thumbnail"],
          'descriptions' => wp_strip_all_tags($propiedad["descriptions"]), 
          'status' => $propiedad["status"],
          'origen' => $propiedad["origen"],
          'post_id' => $postId
    ));

    $lastid = $wpdb->insert_id;            

    $wpdb->insert('location_propiedad', array(
          'id_propiedad' =>  $lastid,
          'address_line' => $propiedad["address_line"],
          'city' => $propiedad["city"],
          'state' => $propiedad["state"], 
          'country' => $propiedad["country"], 
          'latitude' => $propiedad["latitude"], 
          'longitude' => $propiedad["longitude"]
    ));

    $propiedad_id_post = $postId;
    $propiedad_id = $lastid;
    
    // Atributos de la propiedad -- OK
    if(!empty($propiedad["tmp_attributes"])){
        foreach($propiedad["tmp_attributes"] as $attrs) {
            $wpdb->insert('attributes_propiedad', array(
              'id_propiedad' =>  $propiedad_id,
              'name' => $attrs["name"],
              'value_name' => $attrs["value_name"]
            ));
        }
    }

    //Im��genes de la propiedad -- OK
    $galeria = $propiedad["imagenes"];
    
    if(!empty($galeria)){
        for($i=0; $i < count($galeria); $i++){
            $wpdb->insert('propiedades_galeria', array(
              'id_propiedad' =>  $propiedad_id,
              'url_imagen' => $galeria[$i]
            ));
        }
    }
    
    
    /********************************************/
    /****Creamos post Meta wordpress de propiedades***/
    /********************************************/
    //se agregan custom fiends a la propiedad
    update_post_meta( $propiedad_id_post, 'id_propiedad', $propiedad_id );
    update_post_meta( $propiedad_id_post, 'precio', $propiedad["price"] );
    update_post_meta( $propiedad_id_post, 'tipo_precio',  $propiedad["tipo_precio"]);
    update_post_meta( $propiedad_id_post, 'secure_thumbnail', $propiedad["secure_thumbnail"]);
    update_post_meta( $propiedad_id_post, 'status', $propiedad["status"] );
    update_post_meta( $propiedad_id_post, 'direccion', $propiedad["address_line"] );
    update_post_meta( $propiedad_id_post, 'ciudad', $propiedad["city"] );
    update_post_meta( $propiedad_id_post, 'estado', $propiedad["state"]);
    update_post_meta( $propiedad_id_post, 'pais', $propiedad["country"]);
    update_post_meta( $propiedad_id_post, 'latitud', $propiedad["latitude"] );
    update_post_meta( $propiedad_id_post, 'longitud', $propiedad["longitude"]);

    if(!empty($propiedad["state"])){
        wp_set_post_terms( $propiedad_id_post, $propiedad["state"], 'region_propiedad' );
    }

    //Insertamos atributos en la tabla wp_postmeta WP
    $n = 0;
    if(!empty($atributtes)){
        $cantidad = count($atributtes);
        foreach($atributtes as $attrib){
            if ($attrib["id"] == "PROPERTY_TYPE") {
                wp_set_post_terms( $propiedad_id_post, $attrib["value_name"], 'tipo_propiedad' );
            }

            if ($attrib["id"] == "OPERATION") {
                wp_set_post_terms( $propiedad_id_post, $attrib["value_name"], 'categoria_propiedad' );
            }
            
            update_post_meta( $propiedad_id_post, 'atributos', $cantidad);
            update_post_meta( $propiedad_id_post, 'atributos_'.$n.'_atributo_nombre', $attrib["name"] );
            update_post_meta( $propiedad_id_post, 'atributos_'.$n.'_atributo_valor', $attrib["value_name"] );
            $n++;
        }
    }

    //Insertamos Galeria de imagenes en la tabla wp_postmeta WP
    $l = 0;
    if(!empty($galeria)){
        $cantidad = count($galeria);
        foreach($galeria as $img){
            
            update_post_meta( $propiedad_id_post, 'galeria', $cantidad);
            update_post_meta( $propiedad_id_post, 'galeria_'.$l.'_url_imagen', $img );
            $l++;
        }
    }
	
	return true;
}

function destacados($data){
    
    error_log("destacados(");
    
	$test = getOption("theme_settings");
	var_dump($test);
	echo "200";
}

function updateCorreos($data){
    
    error_log("updateCorreos(");
    
	addOption("correos_meli", $data);
	echo "200";
}

function moneyFormat($price, $curr, $tipo) {
    
    error_log("moneyFormat(");
    
	$separador = 0;
	if ($tipo == "CLF"){
		$separador = 2;
	}
    $currencies['EUR'] = array(2, ',', '.');        // Euro
    $currencies['ESP'] = array(2, ',', '.');        // Euro
    $currencies['USD'] = array(2, '.', ',');        // US Dollar
    $currencies['COP'] = array(2, ',', '.');        // Colombian Peso
    $currencies['CLP'] = array($separador,  ',', '.');        // Chilean Peso

	$number = number_format($price, ...$currencies[$curr]);
	if ($tipo == "CLP"){
		$number = "$ " .$number;
	}
	else{
		$number = "UF " .$number;
	}
	return $number;
}

function multiKeyExists($products, $field, $value)
{
    error_log("multiKeyExists(");
    
   foreach($products as $key => $product)
   {
      if ( $product[$field] === $value )
         return $key;
   }
   return false;
}

function getUser($field, $value){
    
    error_log("getUser(");
    
    require_once('../wp-load.php');
    return get_user_by($field, $value);
}



?>
