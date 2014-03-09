<?php

// TODO: Cambiar de mysql a mysqli

if(!isset($_POST['submitConv']))
{
	print('<FORM name="convertir_tablas_form" METHOD="POST" action="convertir_tablas.php">'.PHP_EOL);
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

$q_tables = 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = "'.$database.'" AND TABLE_NAME LIKE "'.$prefix.'_%" AND TABLE_NAME NOT IN ("'.$prefix.'_config")';

$query_tables = mysql_query($q_tables, $connection) or die("Error al traer tablas<br/>Query:".$q_tables."<br/>".mysql_error($connection));

while($array_tables = mysql_fetch_assoc($query_tables))
{
	$ais_table = array();
	$pks_table = array();
	
	// TODO: Al integrar a instalación, hacer que tome el prefijo de la variable, y no de aqui.
	    $table_name_no_prefix = str_ireplace($prefix.'_', "", $array_tables['TABLE_NAME']);
	$table_name = $array_tables['TABLE_NAME'];
	
	print('Tabla: '.$table_name_no_prefix."<br />");
	
	$q_ais = "SELECT COLUMN_NAME, COLUMN_TYPE, IFNULL( NULLIF(IFNULL( NULLIF( IS_NULLABLE, 'NO' ) , 'NOT NULL' ), 'YES'), 'NULL') AS IS_NULLABLE
	FROM information_schema.`COLUMNS`
	WHERE `TABLE_SCHEMA` = '".$database."'
	AND `TABLE_NAME` = '".$table_name."'
	AND EXTRA = 'auto_increment'
	AND COLUMN_KEY = 'PRI'";
	$query_ais = mysql_query($q_ais, $connection) or die("Error al obtener AIS<br/>Query:".$q_ais."<br/>".mysql_error($connection));
	
	while($array_ais = mysql_fetch_assoc($query_ais))
	{
		$ais_table[] = $array_ais;
	}
	
	$q_pks = "SELECT COLUMN_NAME
			FROM information_schema.`KEY_COLUMN_USAGE`
			WHERE `CONSTRAINT_SCHEMA` = '".$database."'
			AND `CONSTRAINT_NAME` = 'PRIMARY'
			AND `TABLE_NAME` = '".$table_name."'";
	$query_pks = mysql_query($q_pks, $connection) or die("Error al obtener PKS<br/>Query:".$q_pks."<br/>".mysql_error($connection));

	while($array_pks = mysql_fetch_assoc($query_pks))
	{
		$pks_table[] = $array_pks['COLUMN_NAME'];
	}
	
	$q_check_gid = "SELECT * FROM `information_schema`.`COLUMNS`" .
			" WHERE TABLE_SCHEMA = '".$database."'" .
			" AND TABLE_NAME = '".$table_name."'" .
			" AND COLUMN_NAME = '".$table_name_no_prefix."_gid'";
	$check_gid_query = mysql_query($q_check_gid, $connection);

	if(mysql_num_rows($check_gid_query) == 0) {	
	$q_add_gid = "ALTER TABLE `".$table_name."` ADD COLUMN ".$table_name_no_prefix."_gid CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NULL FIRST";
	$add_gid_query = mysql_query($q_add_gid, $connection) or die("Error al crear GID<br/>Query:".$q_add_gid."<br/>".mysql_error($connection));
	}
	
	$q_select_gids = "SELECT * FROM `".$table_name."`";
	$select_gids_query = mysql_query($q_select_gids, $connection) or die ("Error al seleccionar la tabla para actualizar GIDs<br/>Query:".$q_select_gids."<br/>".mysql_error($connection));

	if(mysql_num_rows($select_gids_query) > 0)
	{
		while($array_select_gids = mysql_fetch_assoc($select_gids_query))
		{
			$where_clause = "WHERE 1";
			foreach($array_select_gids as $key => $value)
			{
				if ($value != '') $where_clause .= " AND `" . $key ."` = '" . mysql_real_escape_string($value). "'";
			}
			$q_update_gids = "UPDATE `".$table_name."` SET ".$table_name_no_prefix."_gid = UUID() ". $where_clause;
			$update_gids_query = mysql_query($q_update_gids, $connection) or die("Error al actualizar GIDS<br/>Query:".$q_update_gids."<br/>".mysql_error($connection));
		}
	
		if (!empty($ais_table))
		foreach ($ais_table as $ais_array)
		{
			$q_remove_ais = "ALTER TABLE `".$table_name."` CHANGE `".$ais_array['COLUMN_NAME']."` `".$ais_array['COLUMN_NAME']."` ".$ais_array['COLUMN_TYPE']." ".$ais_array['IS_NULLABLE'];
			$alter_remove_ais_query = mysql_query($q_remove_ais, $connection) or die("Error al remover AIS<br/>Query:".$q_remove_ais."<br/>".mysql_error($connection));
		}
		
		if(!empty($pks_table))
		{
			$q_remove_pk = "ALTER TABLE `".$table_name."` DROP PRIMARY KEY";
			$remove_pk_query = mysql_query($q_remove_pk, $connection) or die("Error al tirar PK<br/>Query:".$q_remove_pk."<br/>".mysql_error($connection));
		}
		
		foreach ($pks_table as $field)
		{
			$q_add_index = "ALTER TABLE `".$table_name."` ADD INDEX ( `".$field."`)";
			$add_index_query = mysql_query($q_add_index, $connection) or die("Error al agregar Indice<br/>Query:".$q_add_index."<br/>".mysql_error($connection));
		}
		
		if (!empty($ais_table))
		foreach ($ais_table as $ais_array)
		{
			$q_restore_ais = "ALTER TABLE `".$table_name."` CHANGE `".$ais_array['COLUMN_NAME']."` `".$ais_array['COLUMN_NAME']."` ".$ais_array['COLUMN_TYPE']." ".$ais_array['IS_NULLABLE']." AUTO_INCREMENT"; 
			$alter_restore_ais_query = mysql_query($q_restore_ais, $connection) or die("Error al regresar AIS<br/>Query:".$q_restore_ais."<br/>".mysql_error($connection));
		}
	
	
		$q_make_pk = "ALTER TABLE `".$table_name."` ADD PRIMARY KEY (".$table_name_no_prefix."_gid)";
		$make_pk_query = mysql_query($q_make_pk, $connection) or die("Error al hacer GID PK<br/>Query:".$q_make_pk."<br/>".mysql_error($connection));
	}

	$q_drop_trigger = "DROP TRIGGER IF EXISTS tgGID_".$table_name;
	$trigger_drop_query = mysql_query($q_drop_trigger, $connection) or die("Error tirar Trigger<br/>Query:".$q_drop_trigger."<br/>".mysql_error($connection));

	$connection_i = mysqli_connect($host, $username, $password, $database, 3306);
	
	$q_create_trigger = "CREATE TRIGGER `tgGID_".$table_name."` BEFORE INSERT ON `".$table_name."`
					FOR EACH ROW
					BEGIN
						IF NEW.".$table_name_no_prefix."_gid IS NULL OR NEW.".$table_name_no_prefix."_gid = '' THEN 
						SET NEW.".$table_name_no_prefix."_gid = UUID();" .
						"END IF;
					END;";
	$trigger_create_query = mysqli_multi_query($connection_i, $q_create_trigger) or die("Error al crear Trigger<br/>Query:".$q_create_trigger."<br/>".mysqli_error($connection_i));

	unset($pks_table);
	unset($table_pk);
	print('<br />');
}//while 

}//else
?>
