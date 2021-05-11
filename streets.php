<?


$sql = "SELECT DISTINCT(uri_street) FROM `observations` where uri_street <> ''";
$result = $mysqli->query($sql);

$streetoptions = "";

while($row = $result->fetch_assoc()){ 
	if($row['uri_street']==$_GET['q']){
		$streetoptions .= "<option selected=\"s\">" . $row['uri_street'] . "</option>\n";
	}else{
		$streetoptions .= "<option>" . $row['uri_street'] . "</option>\n";
	}
}