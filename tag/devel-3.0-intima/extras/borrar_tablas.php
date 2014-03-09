<?php

// TODO: Cambiar de mysql a mysqli

if(!isset($_POST['submitConv']))
{
	print('<FORM name="convertir_tablas_form" METHOD="POST" action="borrar_tablas.php">'.PHP_EOL);
	print('Host: <INPUT type="text" name="host" size="20" value="localhost" /><br/>'.PHP_EOL);
	print('User: <INPUT type="text" name="username" size="20" value="" /><br/>'.PHP_EOL);
	print('Password: <INPUT type="password" name="password" size="20" value="" /><br/>'.PHP_EOL);
	print('Database: <INPUT type="text" name="database" size="20" value="" /><br/>'.PHP_EOL);
	print('CompanyID: <INPUT type="text" name="prefix" size="10" value="" /><br/>'.PHP_EOL);
	print('<INPUT type="submit" name="submitConv" value="Convertir" />'.PHP_EOL);
	print('</FORM>'.PHP_EOL);
}
else
{

$host = $_POST['host'];
$username = $_POST['username'];
$password = $_POST['password'];
$database = $_POST['database'];
$prefix = $_POST['prefix'];

$connection = mysql_connect($host, $username, $password);

$db_selected = mysql_select_db($database, $connection);

$q_tables = 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = "'.$database.'" AND TABLE_NAME LIKE "'.$prefix.'_%"';

$query_tables = mysql_query($q_tables, $connection) or die("Error al traer tablas<br/>Query:".$q_tables."<br/>".mysql_error($connection));

while($array_tables = mysql_fetch_assoc($query_tables))
{
	print('Tabla: '.$array_tables['TABLE_NAME']."<br />");
	
	$q_drop = "DROP TABLE ".$array_tables['TABLE_NAME'];
	$query_ais = mysql_query($q_drop, $connection) or die("Error al tirar la 
tabla<br/>Query:".$q_drop."<br/>".mysql_error($connection));
		print('<br />');
}//while 

}//else
?>
