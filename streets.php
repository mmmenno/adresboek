<?


$sql = "SELECT DISTINCT(uri_street) FROM `observations` where uri_street <> ''";
$result = $mysqli->query($sql);

$streetoptions = "";

while($row = $result->fetch_assoc()){ 
	$namepart =  str_replace("https://adamlink.nl/geo/street/", "", $row['uri_street']);
	$pos = strpos($namepart,"/");
	$namepart = substr($namepart, 0, $pos);
	if($row['uri_street']==$_GET['q']){
		$streetoptions .= "<option selected=\"s\" value=\"" . $row['uri_street'] . "\">" . $namepart . "</option>\n";
	}else{
		$streetoptions .= "<option value=\"" . $row['uri_street'] . "\">" . $namepart . "</option>\n";
	}
}