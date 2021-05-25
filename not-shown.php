<!DOCTYPE html>
<html>
<head>
  
<title>Adresboek 1907</title>

  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <link rel="stylesheet" href="styles.css" />

  
</head>
<body>

<?

include("settings.php");

if(isset($_GET['q'])){
	$q = urldecode($_GET['q']);
}else{
	$q = "slijters en tappers";
}


if(preg_match("/^https:\/\/adamlink.nl\/geo\/street\//", $q)){
	$sql = "SELECT * FROM `observations` 
		WHERE uri_street = '" . $mysqli->real_escape_string($q) . "' and txt_lastname <> ''";

}elseif(preg_match("/^https:\/\/iisg.amsterdam\/resource\/hisco\/code\/hisco/", $q)){
	$sql = "SELECT * FROM `observations` AS o
			LEFT JOIN beroepen AS b on o.profession = b.normalised
			WHERE b.hiscocat LIKE '" . $mysqli->real_escape_string($q) . "%' and txt_lastname <> ''";

}else{
	$sql = "SELECT * FROM `observations` 
		WHERE profession = '" . $mysqli->real_escape_string($q) . "' and txt_lastname <> ''";
}




$result = $mysqli->query($sql);

$found = 0;
$notfound = 0;
$lps = array();
$i = 0;

echo "<table>";

while($row = $result->fetch_assoc()){ 

	$page = (int)substr($row['scan'],10,5);
	$modulus = $page%10;
	$start = $page-$modulus;

	$url = "<a href=\"https://archief.amsterdam/inventarissen/scans/30274/65/start/" . $start . "/limit/10/highlight/" . $modulus . "\">to scan</a>";

	
	if(strlen($row['lastname'])){
		$label = trim($row['txt_initials'] . " " . $row['txt_lastnameprefix'] . " " . $row['lastname']);
	}else{
		$label = trim($row['txt_initials'] . " " . $row['txt_lastnameprefix'] . " " . $row['txt_lastname']);
	}
	if(strlen($row['profession'])){
		$label .= ", " . $row['profession'];
	}
	

	$toev = "";
	if(strlen($row['number'])){
		preg_match("/([0-9-]+) ?([a-z])?/", $row['number'],$matches);
		$nr = $matches[1];
		$toev = $matches[2];
	}else{
		preg_match("/([0-9-]+) ?([a-z])?/", $row['txt_number'],$matches);
		$nr = $matches[1];
		$toev = $matches[2];
	}
	if(strpos($nr,"-")){
		$nrparts = explode("-", $nr);
		$nr = $nrparts[0];
	}
	

	$s = "select * from locatiepunten 
			where adamlink = '" . $row['uri_street'] . "'
			and huisnr = '" . $nr . "' and toevoeging = '" . $toev . "'";
	$r = $mysqli->query($s);

	if($lp = $r->fetch_assoc()){
		$found++;
	}else{

		unset($row['is_observation']);
		unset($row['entity_type']);
		unset($row['normalised']);
		unset($row['hiscocat']);
		unset($row['hiscocatname']);
		unset($row['hiscocatdesc']);
		
		$add = array('scanurl' => $url);
		$showrow = $add + $row;
		$i++;
		if($i==1){
			echo "<tr>";
			foreach ($showrow as $k => $v) {
				echo "<th>";
				echo $k;
				echo "</th>";
			}
			echo "</tr>";
		}

		echo "<tr>";
		foreach ($showrow as $k => $v) {
			echo "<td>";
			echo $v;
			echo "</td>";
		}
		echo "</tr>";
	}

}

echo "<table>";

?>
</body>
</html>