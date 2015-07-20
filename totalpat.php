<?php
/*
Plugin Name: Totalpat
Plugin URI: http://www.totalpat.com
Description: Comunicación entre la WP y Totalpat para la retroalimentación de estadísticas, formularios y cookies.
Version: 1.0
Author: Totalpat, S.A. de C.V.
Author URI: http://www.totalpat.com
License: Sistema Totalpat
*/

/*  
Copyright 2015 TOTALPAT, S.A. DE C.V.  (email : develop@totalpat.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
*	TRAKING COOKIE
*/
require_once "data.config.php";
require_once("webservice/totalpat_lib/soap/nusoap.php");
require_once("webservice/totalpat_lib/browser.lib.php");
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('WP_DEBUG', true);

add_action('init', function() {
	
    //información de browser
    $browser=new Browser();
    $plataforma=$browser->getPlatform();
    
    if(isset($_GET['campingTotalpat'])){
	$toCookie = array(
 	   array('campingTotalpat'=>$_GET['campingTotalpat'], 'botonTotalpat'=>$_GET['botonTotalpat'], 'linkTotalpat'=>$_GET['linkTotalpat'])
	);
	$json = json_encode($toCookie);
	setcookie("totalpat_user", $json, time() + (86400 * 3000), "/", false, 0);
    }

    if (!isset($_COOKIE['totalpat_traking'])) {
	
	//IP
	$client  = @$_SERVER['HTTP_CLIENT_IP'];
	$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
	$remote  = $_SERVER['REMOTE_ADDR'];
	if(filter_var($client, FILTER_VALIDATE_IP))			$ip = $client;
	elseif(filter_var($forward, FILTER_VALIDATE_IP))		$ip = $forward;
	else								$ip = $remote;
	
	//URL actual
	$url_cookie=$_SERVER['SCRIPT_URI'];
	if(isset($_SERVER['REDIRECT_QUERY_STRING'])){
		$url_cookie.='?'.$_SERVER['REDIRECT_QUERY_STRING'];
	}
	
	$toCookie = array(
 	   array('date'=>date('Y-m-d H:i:s'), 'session'=>session_id(), 'url'=>$url_cookie, 'url_referer'=>$_SERVER['HTTP_REFERER'], 'ip'=>$ip, 'plataforma'=>$plataforma, 'campingTotalpat'=>$_GET['campingTotalpat'], 'botonTotalpat'=>$_GET['botonTotalpat'], 'linkTotalpat'=>$_GET['linkTotalpat'], 'nivel'=>0)
	);
	
	$json = json_encode($toCookie);
	setcookie("totalpat_traking", $json, time() + (86400 * 3000), "/", false, 0);
    }
    else{
		//se lee, limpia y desencripta la cookie
		$cookieValueArr=array();
		$cookieValue = $_COOKIE['totalpat_traking'];
		$cookieValue=implode("",explode("\\",$cookieValue));
		$cookieValue=stripslashes(trim($cookieValue));
		$cookieValueArr=json_decode( $cookieValue, TRUE );
		
		//IP
		$client  = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote  = $_SERVER['REMOTE_ADDR'];
		if(filter_var($client, FILTER_VALIDATE_IP))		$ip = $client;
		elseif(filter_var($forward, FILTER_VALIDATE_IP))		$ip = $forward;
		else		$ip = $remote;	
	
		//URL
		$url_cookie=$_SERVER['SCRIPT_URI'];
		if(isset($_SERVER['REDIRECT_QUERY_STRING'])){
			$url_cookie.='?'.$_SERVER['REDIRECT_QUERY_STRING'];
		}
	
		$texto_nuevo=array();
		for($i=0; $i<count($cookieValueArr); $i++){
			$texto_nuevo[$i]['date']=$cookieValueArr[$i]['date'];
			$texto_nuevo[$i]['session']=$cookieValueArr[$i]['session'];
			$texto_nuevo[$i]['url']=$cookieValueArr[$i]['url'];
			$texto_nuevo[$i]['url_referer']=$cookieValueArr[$i]['url_referer'];
			$texto_nuevo[$i]['ip']=$cookieValueArr[$i]['ip'];
			$texto_nuevo[$i]['plataforma']=$cookieValueArr[$i]['plataforma'];
			$texto_nuevo[$i]['campingTotalpat']=$cookieValueArr[$i]['campingTotalpat'];
			$texto_nuevo[$i]['botonTotalpat']=$cookieValueArr[$i]['botonTotalpat'];
			$texto_nuevo[$i]['linkTotalpat']=$cookieValueArr[$i]['linkTotalpat'];
			$texto_nuevo[$i]['nivel']=$cookieValueArr[$i]['nivel'];
		}
		
		if(!isset($_SERVER['HTTP_REFERER'])){
			$_SERVER['HTTP_REFERER']="";
		}
		if(!isset($_GET['botonTotalpat'])){
			$_GET['botonTotalpat']="";
		}
		if(!isset($_GET['campingTotalpat'])){
			$_GET['campingTotalpat']="";
		}
		if(!isset($_GET['linkTotalpat'])){
			$_GET['linkTotalpat']="";
		}
		
		$texto_nuevo[$i]['date']=date('Y-m-d H:i:s');
		$texto_nuevo[$i]['session']=session_id();
		$texto_nuevo[$i]['url']=$url_cookie;
		$texto_nuevo[$i]['url_referer']=$_SERVER['HTTP_REFERER'];
		$texto_nuevo[$i]['ip']=$ip;
		$texto_nuevo[$i]['plataforma']=$plataforma;
		$texto_nuevo[$i]['campingTotalpat']=$_GET['campingTotalpat'];
		$texto_nuevo[$i]['botonTotalpat']=$_GET['botonTotalpat'];
		$texto_nuevo[$i]['linkTotalpat']=$_GET['linkTotalpat'];
		$texto_nuevo[$i]['nivel']=$i;
	
		$json = json_encode($texto_nuevo);
		setcookie("totalpat_traking", $json, time() + (86400 * 3000), "/", false, 0);
    }
});

/*
*	CREACION DE LA BASE DE DATOS
*/
register_activation_hook( __FILE__, 'totalpat_install' );

global $totalpat_db_version;
$totalpat_db_version = '1.0';

function totalpat_install() {
	global $wpdb;
	global $jal_db_version;

	$table_name = $wpdb->prefix . 'totalpat';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		activo mediumint(9) NOT NULL,
		token varchar(200) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'totalpat_db_version', $totalpat_db_version );
}



/*
* CREACION DEL MENU
*/
add_action('admin_menu', 'totalpat_plugin_setup_menu');
function totalpat_plugin_setup_menu(){
        add_menu_page( 'Totalpat Plugin Page', 'Totalpat', 'manage_options', 'totalpat-plugin', 'totalpat_console_init', plugin_dir_url( __FILE__ ).'totalPat_icono.ico', 80 );
}

function totalpat_console_init(){
	
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
	if($_POST['ssid']=="realizarDesconexionTokenTotalpat"){
		realizarDesconexionTokenTotalpat($_POST['inputTokenTotalpat']);
	}
	else if($_POST['ssid']=="realizarActivacionTokenTotalpat"){
		realizarActivacionTokenTotalpat($_POST['inputTokenTotalpat']);
	}
		
	
	//nombre de la tabla
	global $wpdb;
	$table_name = $wpdb->prefix . 'totalpat';
	
	//se busca en la tabla
	$results = $wpdb->get_results( "SELECT * FROM ".$table_name, OBJECT );
	
	$http_prefix="http://";
	if($_SERVER['HTTPS']=="on"){
		$http_prefix="https://";
	}
	$http_prefix.=$_SERVER['SERVER_NAME'];
					
	echo "<br><br><center><img src='../../wp-content/plugins/totalpat/images/totalPat_logo.png'><br><br><br></center>";

	echo '<center>
			<table width="450px" border="0" cellspacing="5" cellpadding="5">
			  <tr>
				<td colspan="2"><h2>Administración de acceso y control Totalpat</h2></td>
			  </tr>
			  <tr>
				<td style="width:200px;">Estatus de la licencia</td>
				<td id="banderaToken">';
			if($results[0]->activo==1){
				echo '<div style="background-color:#4ed771; padding:5px; color:#FFFFFF; width:100px;"><center>Activado</center></div></td>';
			}
			else{
				echo '<div style="background-color:#e73636; padding:5px; color:#FFFFFF; width:100px;"><center>Desactivado</center></div></td>';
			}
		echo '</tr>';
			if($results[0]->activo==1){
				echo '<td>';
				
				echo '<form action="'.$http_prefix.'/wp-admin/admin.php?page=totalpat-plugin" method="post">
  <input type="hidden" name="ssid" value="realizarDesconexionTokenTotalpat">
  <input type="hidden" name="inputTokenTotalpat" value="'.$results[0]->token.'">
  <input type="text" id="inputTokenTotalpat" value="'.'***************'.substr($results[0]->token,15).'" style="width:250px;" disabled />
  <input type="submit" value="Desconectar">
</form>';
				
				echo '</td>';
			}
			else{
				echo '<td>';
				echo '<form action="'.$http_prefix.'/wp-admin/admin.php?page=totalpat-plugin" method="post">
  <input type="hidden" name="ssid" value="realizarActivacionTokenTotalpat">
 
 <table width="100%" border="0" cellspacing="5" cellpadding="5">
  <tr>
    <td>Token</td>
    <td><input type="text" name="inputTokenTotalpat" value="" style="width:250px;" /></td>
  </tr>
</table>

  
  
  <input type="submit" value="Conectar">
</form>';
				echo'</td>';
				
			}
		echo '<td>&nbsp;</td>
			  </tr>';
		
		if($results[0]->activo==1){
			echo '<tr>
						<td colspan="2"><h3>Plugin necesarios</h3></td>
					</tr>';
			if(is_plugin_active( 'hotspots/hotspots.php' )){
				echo '<tr>
							<td>Hotspot</td>
							<td><div style="background-color:#4ed771; padding:5px; color:#FFFFFF; width:100px;"><center>Activado</center></div></td>
						</tr>';
			}
			else{
				echo '<tr>
							<td>Hotspot</td>
							<td><div style="background-color:#e73636; padding:5px; padding-right:10px; color:#FFFFFF; width:180px;"><center>Desactivado <a href="'.$http_prefix.'/wp-admin/plugin-install.php?tab=search&type=term&s=hotspot" style="float:right; color:#FFFFFF;">instalar</a></center></div></td>
						</tr>';
			}
			
			if(is_plugin_active( 'wp-slimstat/wp-slimstat.php' )){
				echo '<tr>
						<td>Slimstat</td>
						<td><div style="background-color:#4ed771; padding:5px; color:#FFFFFF; width:100px;"><center>Activado</center></div></td>
					</tr>';
			}
			else{
				echo '<tr>
						<td>Slimstat</td>
						<td><div style="background-color:#e73636; padding:5px; padding-right:10px; color:#FFFFFF; width:180px;"><center>Desactivado <a href="'.$http_prefix.'/wp-admin/plugin-install.php?tab=search&type=term&s=wp+slimstat" style="float:right; color:#FFFFFF;">instalar</a></center></div></td>
					</tr>';
			}
		}
			  
		echo '<tr>
				<td colspan="2">';
				
			if($results[0]->activo==1){
				$date = date_create($results[0]->time);
				echo '<br><br>La licencia ha sido activada desde el '.date_format($date, 'd-m-Y').'</td>';
			}
			else{
				echo '</td>';
			}
				
				
			  echo '</tr>
			</table></center>';
	
}

add_action( 'wpcf7_mail_sent', 'your_wpcf7_mail_sent_function' );
function your_wpcf7_mail_sent_function($contact_form) {
	
	//mail('iarellano@totalpat.com', 'WP detectado', "El titulo: " . print_r($_POST, true));
	
	//librerias
	require_once "data.config.php";
	require_once("webservice/totalpat_lib/soap/nusoap.php");
	
	//se limpia datos
	unset($datos);
	
	
	//se genera el arreglo de envio
	$i=0;
	$banderaGuardar=0;
	foreach($_POST as $field_id => $user_value ){
		if(strtolower($field_id)=="cliente" or $banderaGuardar==1){
			$banderaGuardar=1;
			
			$datos[0]['label_'.$i]=$field_id;
			$datos[0]['id_'.$i]=$user_value;
			$i++;
		}
	}

	
	//mail('iarellano@totalpat.com', 'WP detectado', "El titulo: " . print_r($datos, true));
	
	//cookies
	$cookieValue = $_COOKIE['totalpat_traking'];
	$datos[0]['cookie']=$cookieValue;
	//se limpia la cookie
	setcookie("totalpat_traking", '', time() + (86400 * 3000), "/", false, 0);
	
	if(isset($_COOKIE['totalpat_user'])){
	     $datos[0]['cookie_user']=$_COOKIE['totalpat_user'];
	}

	//llave
	$datos[0]['API_USER']=API_USER;
	$datos[0]['API_KEY']=API_KEY;

	//Conexión SOAP
	$client=new nusoap_client(SERVER_URL."/".SERVER_SCRIPT."?wsdl");
	$client->setCredentials(API_USER,API_KEY, SERVER_TYPE);

	//se manda el error
	$err = $client->getError();
	if ($err) {
		$mensaje="<h2>Mensaje de error en la conexión</h2><br>Sitio: ".$_SERVER['HTTP_HOST'].'<br>Fecha: ' . date('Y-m-d');
		mail('soporte@totalpat.com', 'WP desconectado', $mensaje);
	}
	else{
		$result = $client->call('addTotalpatComment', $datos);
	}
	
	
}


/*
*	DETECT NINJA FORMS
*/
add_action('ninja_forms_email_admin', 'enviarFormularioTotalpat');
function enviarFormularioTotalpat(){
	
	//librerias
	require_once "data.config.php";
	require_once("webservice/totalpat_lib/soap/nusoap.php");	
	
	//variables globales de ninja form
	global $ninja_forms_loading, $ninja_forms_processing;

	//Se obtienen las variables enviadas por el usuario
	$all_fields = $ninja_forms_processing->get_all_fields();
	
	//se limpia datos
	unset($datos);

	//se genera el arreglo de envio
	foreach( $all_fields as $field_id => $user_value ){
		$datos[0]['label_'.$field_id]=$ninja_forms_processing->get_field_setting($field_id, 'label');
		$datos[0]['id_'.$field_id]=$user_value;
	}
	
	//cookies
	$cookieValue = $_COOKIE['totalpat_traking'];
	$datos[0]['cookie']=$cookieValue;
	//se limpia la cookie
	setcookie("totalpat_traking", '', time() + (86400 * 3000), "/", false, 0);
	
	if(isset($_COOKIE['totalpat_user'])){
	     $datos[0]['cookie_user']=$_COOKIE['totalpat_user'];
	}

	//llave
	$datos[0]['API_USER']=API_USER;
	$datos[0]['API_KEY']=API_KEY;

	//Conexión SOAP
	$client=new nusoap_client(SERVER_URL."/".SERVER_SCRIPT."?wsdl");
	$client->setCredentials(API_USER,API_KEY, SERVER_TYPE);

	//se manda el error
	$err = $client->getError();
	if ($err) {
		$mensaje="<h2>Mensaje de error en la conexión</h2><br>Sitio: ".$_SERVER['HTTP_HOST'].'<br>Fecha: ' . date('Y-m-d');
		mail('soporte@totalpat.com', 'WP desconectado', $mensaje);
	}
	else{
		$result = $client->call('addTotalpatComment', $datos);
	}
}


function realizarActivacionTokenTotalpat($token){
	//llave
	$datos[0]['API_USER']=API_USER;
	$datos[0]['API_KEY']=API_KEY;

	//Conexión SOAP
	$client=new nusoap_client(SERVER_URL."/".SERVER_SCRIPT."?wsdl");
	$client->setCredentials(API_USER,API_KEY, SERVER_TYPE);
	
	//se manda el error
	$err = $client->getError();
	
	if ($err) {
		$mensaje="<h2>Mensaje de error en la conexión</h2><br>Sitio: ".$_SERVER['HTTP_HOST'].'<br>Fecha: ' . date('Y-m-d');
		mail('soporte@totalpat.com', 'WP desconectado', $mensaje);
		return 0;
	}
	else{
		$datos_activacion[0]['token_acceso']=$token;
		$datos_activacion[0]['url_peticion']=$_SERVER['HTTP_HOST'];			//desarrollo.totalpat.com
		$result = $client->call('activateTotalpat', $datos_activacion);
		
		//se guarda en la base de datos
		if($result==1){
			
			//nombre de la tabla
			global $wpdb;
			$table_name = $wpdb->prefix . 'totalpat';
			
			//se limpia la tabla
			$delete = $wpdb->query("TRUNCATE TABLE `".$table_name."`");


			//se guarda la info
			$token = $_POST['inputTokenTotalpat'];
			$activo = 1;
			$wpdb->insert( 
				$table_name, 
				array( 
					'time' => current_time( 'mysql' ), 
					'activo' => $activo, 
					'token' => $token, 
				) 
			);
			return 1;
		}
		else{
			return 2;
		}
	}
}

function realizarDesconexionTokenTotalpat($token){
	
	//Conexión SOAP
	$client=new nusoap_client(SERVER_URL."/".SERVER_SCRIPT."?wsdl");
	$client->setCredentials(API_USER,API_KEY, SERVER_TYPE);
	
	//se manda el error
	$err = $client->getError();
	if ($err) {
		$mensaje="<h2>Mensaje de error en la conexión</h2><br>Sitio: ".$_SERVER['HTTP_HOST'].'<br>Fecha: ' . date('Y-m-d');
		mail('soporte@totalpat.com', 'WP desconectado', $mensaje);
		echo 0;
	}
	else{
		$datos_activacion[0]['token_acceso']=$token;
		$datos_activacion[0]['url_peticion']=$_SERVER['HTTP_HOST'];			//desarrollo.totalpat.com
		$result = $client->call('desactivateTotalpat', $datos_activacion);
	}
	
	//nombre de la tabla
	global $wpdb;
	$table_name = $wpdb->prefix . 'totalpat';
	
	//se limpia la tabla
	$delete = $wpdb->query("TRUNCATE TABLE `".$table_name."`");
}


?>