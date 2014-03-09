<script type="text/javascript">

function checkNumeric ()
{
	var i;
	var errPrice = new Array();
	var errWeight = new Array();
	var fieldsPrice = document.frm_config_variations.elements["inp_vcprice[]"];
	var fieldsWeight = document.frm_config_variations.elements["inp_vcweight[]"];
	//alert("--"+fieldsPrice.toString()+"--");
	for (i=0;i<fieldsPrice.length;i++)
	{
		//alert("--"+i+"--"+fieldsPrice[i].name+"--"+fieldsPrice[i].value+"--<br />");
		if (isNaN(fieldsPrice[i].value) || fieldsPrice[i].value == "")
		{
			errPrice.push(i);			
		}
		if (isNaN(fieldsWeight[i].value) || fieldsWeight[i].value == "")
		{
			errWeight.push(i);			
		}
	}

	if (errPrice.length>0)
	{
		var errmsg = 'Los siguientes campos deben ser numericos con dos digitos\nMontos de Precio:\n';
		for(i in errPrice)
		{
			errmsg += "Opcion ID: " + document.frm_config_variations.elements["hdn_voptionid[]"][errPrice[i]].value + "\n";
			document.frm_config_variations.elements["inp_vcprice[]"][errPrice[i]].style.backgroundColor = "red";
		}
		errmsg += "Montos de Peso:\n";
		for(i in errWeight)
		{
			errmsg += "Opcion ID: " + document.frm_config_variations.elements["hdn_voptionid[]"][errPrice[i]].value + "\n";
			document.frm_config_variations.elements["inp_vcweight[]"][errWeight[i]].style.backgroundColor = "red";
		}
		alert(errmsg);
		return false;
	}
	else return true;
}

function InitializeZeroes()
{
	var ans = confirm("Esta seguro de querer inicializar la forma? Todos los valores se regresaran a cero");

	if (ans == true)
	{
		var i;
		var fieldsPrice = document.frm_config_variations.elements["inp_vcprice[]"];
		for (i=0;i<fieldsPrice.length;i++)
		{
			fieldsPrice[i].value = 0.0000;
		}
		var selectPrice = document.frm_config_variations.elements["sel_vcpricediff[]"];
		for (i=0;i<selectPrice.length;i++)
		{
			selectPrice[i].selectedIndex = 0;
		}
		
		var fieldsWeight = document.frm_config_variations.elements["inp_vcweight[]"];
		for (i=0;i<fieldsWeight.length;i++)
		{
			fieldsWeight[i].value = 0.0000;
		}
		var selectWeight = document.frm_config_variations.elements["sel_vcweightdiff[]"];
		for (i=0;i<selectWeight.length;i++)
		{
			selectWeight[i].selectedIndex = 0;
		}
	}
}

</script>

<?php

require_once(dirname(__FILE__).'/../init.php');
$user = GetConfig('dbUser');
$password = GetConfig('dbPass');
$dbname = GetConfig('dbDatabase');
$host = GetConfig('dbServer');
$prefix = GetConfig('tablePrefix');

$connection = mysql_connect($host, $user, $password);

$db_selected = mysql_select_db($dbname, $connection);

foreach ($_GET as $key => $value) {
	$_GET[$key] = mysql_real_escape_string($value);
}

isset($_GET['variationid']) ? $variation_id = $_GET['variationid'] : $variation_id = "";

if ($variation_id == "") echo "No se selecciono variacion\n";

if (isset($_POST['sbt_configure_variations']) && $_POST['sbt_configure_variations'] = 'Guardar')
{
	//print_r($_REQUEST);
	//print_r($_REQUEST['hdn_voptionid']);
	foreach($_REQUEST['hdn_voptionid'] as $key => $value)
	{
		$_REQUEST['sel_vcpricediff'][$key] == '' ? $_REQUEST['inp_vcprice'][$key] = '0.00' : $_REQUEST['inp_vcprice'][$key];
		$_REQUEST['sel_vcweightdiff'][$key] == '' ? $_REQUEST['inp_vcweight'][$key] = '0.00' : $_REQUEST['inp_vcweight'][$key];
		$query_update_variations = "UPDATE ".$prefix."product_variation_options
		SET
		`vcpricediff` = '".$_REQUEST['sel_vcpricediff'][$key]."',
		`vcprice` = '".$_REQUEST['inp_vcprice'][$key]."',
		`vcweightdiff` = '".$_REQUEST['sel_vcweightdiff'][$key]."',
		`vcweight` = '".$_REQUEST['inp_vcweight'][$key]."'
		WHERE `vovariationid` = '".$variation_id."' AND `voptionid` = '".$value."'";
		
		//echo $query_update_variations ."<br />\n";
		$result_update_variations = mysql_query($query_update_variations, $connection) or die("Error al actualizar. Result:".$result_update_variations.". Query:".$query_update_variations."<br />");
		
		if(!$result_update_variations) $err[] = "Error al actualizar ID: ".$value.".<br />"; 
	}
	if (isset($err)) echo "Error al actualizar.";
		else echo "Actualizado con exito";
}

if ($variation_id != "")
{

$query_select_variations = "SELECT * FROM ".$prefix."product_variation_options WHERE vovariationid = '".$variation_id."' ORDER BY voname, voptionid";

$result_select_variations = mysql_query($query_select_variations, $connection);

echo "<FORM onSubmit='return checkNumeric();' id='frm_config_variations' name='frm_config_variations' method='POST' action='configure-variations.php?variationid=".$variation_id."'>\n";

echo "<table>\n
<th>ID de opcion</th>\n
<th>Nombre</th>\n
<th>Valor</th>\n
<th>Accion precio</th>
<th>Monto accion precio</th>\n
<th>Accion peso</th>\n
<th>Monto accion peso</th>\n";

while ($line = mysql_fetch_assoc($result_select_variations))
{
	//print_r($line);
	echo "<tr>\n";
	echo "\t<td>".$line['voptionid']."</td>\n";
	echo "\t<INPUT type='hidden' name='hdn_voptionid[]' value='".$line['voptionid']."' />\n";
	echo "\t<td>".$line['voname']."</td>\n";
	echo "\t<td>".$line['vovalue']."</td>\n";
	echo "\t<INPUT type='hidden' name='hdn_vovalue[]' value='".$line['vovalue']."' />\n";
	
	echo "\t<td>\n";
	echo "\t<SELECT name='sel_vcpricediff[]'>\n";
	echo "\t\t<OPTION "; if($line['vcpricediff'] == '') echo "selected='selected' "; echo "value=''>Sin Accion</option>\n";
	echo "\t\t<OPTION "; if($line['vcpricediff'] == 'add') echo "selected='selected' "; echo "value='add'>Agregar</option>\n";
	echo "\t\t<OPTION "; if($line['vcpricediff'] == 'subtract') echo "selected='selected' "; echo "value='subtract'>Restar</option>\n";
	echo "\t</SELECT>\n";
	echo "\t</td>\n";
	
	echo "\t<td>\n";
	echo "<INPUT type='text' name='inp_vcprice[]' size='10' value='".$line['vcprice']."' />\n";
	echo "\t</td>\n";
	
	echo "\t<td>\n";
	echo "\t<SELECT name='sel_vcweightdiff[]'>\n";
	echo "\t\t<OPTION "; if($line['vcweightdiff'] == '') echo "selected='selected' "; echo "value=''>Sin Accion</option>\n";
	echo "\t\t<OPTION "; if($line['vcweightdiff'] == 'add') echo "selected='selected' "; echo "value='add'>Agregar</option>\n";
	echo "\t\t<OPTION "; if($line['vcweightdiff'] == 'subtract') echo "selected='selected' "; echo "value='subtract'>Restar</option>\n";
	echo "\t</SELECT>\n";
	echo "\t</td>\n";
	
	echo "\t<td>\n";
	echo "<INPUT type='text' name='inp_vcweight[]' size='10' value='".$line['vcweight']."' />\n";
	echo "\t</td>\n";
	
	echo "</tr>\n";
}
echo "</table>";

echo "<INPUT type='submit' name='sbt_configure_variations' value='Guardar' />\n";
echo "<INPUT type='reset' value='Deshacer cambios' />\n";
echo "<a href=\"#\" onclick=\"InitializeZeroes();\">Inicializar Forma</a>\n";

echo "</FORM>\n";

}//else
?>
