<pre>
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
			LEFT JOIN create_adresboeken.beroepen AS b on o.profession = b.normalised
			WHERE b.hiscocat LIKE '" . $mysqli->real_escape_string($q) . "%' and txt_lastname <> ''";

}else{
	$sql = "SELECT * FROM `observations` 
		WHERE profession = '" . $mysqli->real_escape_string($q) . "' and txt_lastname <> ''";
}




$result = $mysqli->query($sql);

$found = 0;
$notfound = 0;
$lps = array();

while($row = $result->fetch_assoc()){ 

	$page = (int)substr($row['scan'],10,5);
	$modulus = $page%10;
	$start = $page-$modulus;

	$url = "https://archief.amsterdam/inventarissen/scans/30274/65/start/" . $start . "/limit/10/highlight/" . $modulus;

	
	if(strlen($row['lastname'])){
		$label = trim($row['txt_initials'] . " " . $row['txt_lastnameprefix'] . " " . $row['lastname']);
	}else{
		$label = trim($row['txt_initials'] . " " . $row['txt_lastnameprefix'] . " " . $row['txt_lastname']);
	}
	if(strlen($row['profession'])){
		$label .= ", " . $row['profession'];
	}
	

	if(strlen($row['number'])){
		$nr = $row['number'];
	}else{
		$nr = $row['txt_number'];
	}
	if(strpos($nr,"-")){
		$nrparts = explode("-", $nr);
		$nr = $nrparts[0];
	}
	

	$s = "select * from create_adresboeken.locatiepunten 
			where adamlink = '" . $row['uri_street'] . "'
			and huisnr = '" . $nr . "'";
	$r = $mysqli->query($s);

	if($lp = $r->fetch_assoc()){
		$found++;
	}else{
		echo '<br /><a href="' . $url . '">link to scan</a><br />';
		print_r($row);
		$notfound++;
	}

}

?>
</pre>