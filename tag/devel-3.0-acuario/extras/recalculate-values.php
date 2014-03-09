<?php
require_once(dirname(__FILE__).'/../init.php');

$errors = array();
if (!isset($_SERVER['HTTP_REFERER']) or strpos($_SERVER['HTTP_REFERER'], 'extras/recalculate-values.php')) $errors[] = "Forbidden";

foreach ($_GET as $key => $value) {
	$_GET[$key] = mysql_real_escape_string($value);
}

$VariationID = $_GET['variationId'];
$ProductID = $_GET['productId'];

if (!isset($VariationID) || $VariationID == '' || !is_numeric($VariationID)) $errors[] = "Variation ID Error";
if (!isset($ProductID) || $ProductID == '' || !is_numeric($ProductID)) $errors[] = "Product ID Error";

if (empty($errors))
{	
$user = GetConfig('dbUser');
$password = GetConfig('dbPass');
$dbname = GetConfig('dbDatabase');
$host = GetConfig('dbServer');
$prefix = GetConfig('tablePrefix');

$connection = mysql_connect($host, $user, $password);

$db_selected = mysql_select_db($dbname, $connection);

$query_select_variations = "SELECT vcoptionids FROM ".$prefix."product_variation_combinations WHERE vcproductid = '".$ProductID."' AND vcvariationid='".$VariationID."'";
$result_select_variations = mysql_query($query_select_variations, $connection);

$variations = array();
while ($row_variations = mysql_fetch_assoc($result_select_variations))
{
	$variations[] = explode(',', $row_variations['vcoptionids']);
}

//print_r($variations);

$query_select_option = '';
foreach($variations as $key => $value)
{
	//echo "-----------------<br />\n";
	$query_select_option = 'select a0.voptionid AS voptionid0, a0.vcpricediff AS vcpricediff0, a0.vcprice AS vcprice0, a0.vcweightdiff AS vcweightdiff0, a0.vcweight AS vcweight0';
	
	$i = 0; $ip = 1;$query_join = ''; $query_where = 'WHERE vcproductid = "'.$ProductID.'" AND vcoptionids = "'.$value[0];
	foreach ($value as $keycombo => $valuecombo)
	{
		if ($keycombo != 0)
		{
			$query_select_option .= ', a'.$ip.'.voptionid AS voptionid'.$ip.', a'.$ip.'.vcpricediff AS vcpricediff'.$ip.', a'.$ip.'.vcprice AS vcprice'.$ip.', a'.$ip.'.vcweightdiff AS vcweightdiff'.$ip.', a'.$ip.'.vcweight AS vcweight'.$ip;
			$query_join .= ' join '.$prefix.'product_variation_options a'.$ip.' on  (a0.voptionid = '.$value[0].' AND a'.$ip.'.voptionid = '.$value[$ip].')';
			$query_where .= ",".$value[$ip];
			$i++; $ip++;
		}
	}
	$query_where .= '"';
	
	$query_from = ' from '.$prefix.'product_variation_options a0';
	
	$query_select_option .= $query_from;
	$query_select_option .= $query_join;
	//echo $query_select_option .";<br />\n";
	
	$result_select_option = mysql_query($query_select_option);
	$row_option = mysql_fetch_assoc($result_select_option);
	//print_r($row_option); echo "<br />\n";
	
	//echo "calculationg for ".$query_where."<br />\n";
	$vcprice = 0; $vcweight = 0;
	
	//echo "vcprice was:".$vcprice;
	switch ($row_option['vcpricediff0'])
	{
		case 'add':
			$vcprice += $row_option['vcprice0'];
			break;
		case 'subtract':
			$vcprice -= $row_option['vcprice0'];
			break;
		case 'fixed':
			$vcprice = $row_option['vcprice0'];
			break;
	}
	//echo "now is: ".$vcprice."--";
	
	//echo "vcprice was:".$vcprice;
	for($j=1;$j<$ip;$j++)
	{
	//echo "**".$row_option['vcprice'.$j]."**".$row_option['vcpricediff'.$j]."**";
	switch ($row_option['vcpricediff'.$j])
		{
			case 'add':
				$vcprice += $row_option['vcprice'.$j];
				break;
			case 'subtract':
				$vcprice -= $row_option['vcprice'.$j];
				break;
			case 'fixed':
				$vcprice += $row_option['vcprice'.$j];
				break;
		}
	}
	//echo "now is: ".$vcprice."<br />\n";
	
	if ($vcprice > 0) $vcpricediff = 'add';
	else if ($vcprice < 0) $vcpricediff = 'subtract';
	else if ($vcprice == 0) $vcpricediff = '';

	//echo "vcweight was:".$vcweight;
	switch ($row_option['vcweightdiff0'])
	{
		case 'add':
			$vcweight += $row_option['vcweight0'];
			break;
		case 'subtract':
			$vcweight -= $row_option['vcweight0'];
			break;
		case 'fixed':
			$vcweight = $row_option['vcweight0'];
			break;
	}
	//echo "now is: ".$vcweight."--";
	
	//echo "vcweight was:".$vcweight;
	for($j=1;$j<$ip;$j++)
	{
	switch ($row_option['vcweightdiff'.$j])
		{
			case 'add':
				$vcweight += $row_option['vcweight'.$j];
				break;
			case 'subtract':
				$vcweight -= $row_option['vcweight'.$j];
				break;
			case 'fixed':
				$vcweight += $row_option['vcweight'.$j];
				break;
		}
	}
	//echo "now is: ".$vcweight."<br />\n";
	
	if ($vcweight > 0) $vcweightdiff = 'add';
	else if ($vcweight < 0) $vcweightdiff = 'subtract';
	else if ($vcweight == 0) $vcweightdiff = '';
	
	$query_update_combination = 'UPDATE '.$prefix.'product_variation_combinations
	SET vcpricediff = "'.$vcpricediff.'",
	vcprice = "'.abs($vcprice).'",
	vcweightdiff = "'.$vcweightdiff.'",
	vcweight = "'.abs($vcweight).'" '.$query_where;
	
	//echo $query_update_combination."<br />";
	$result_update_combination = mysql_query($query_update_combination);
	if(!$result_update_combination) $errors[] = "Error en query:".$query_update_combination."<br />\n";
}
}

if (!empty($errors))
{
	foreach($errors as $key => $value)
	{
		echo "Error ".$key.": ".$value."<br />\n";
	}
}
else echo "<H1>Actualizacion realizada con exito!</H1><br />\nPuede cerrar esta ventana";
?>