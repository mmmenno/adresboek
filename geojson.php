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
		$lps[$lp['lpnr']]['geometry'] = wkt2geojson($lp['wkt']);
		$lps[$lp['lpnr']]['occupants'][] = array(
			"label" => $label,
			"ocr" => $row['txt'],
			"part" => $row['part'],
			"scan" => $url
		);
		$found++;
	}else{
		//print_r($row);
		$notfound++;
	}

}

$colprops = array("nrfound"=>$found, "nrnotfound"=>$notfound, "searchedfor"=>$q);

$fc = array("type"=>"FeatureCollection", "properties"=>$colprops, "features"=>array());

foreach ($lps as $key => $value) {
	
	$adres = array("type"=>"Feature");
	$adres['geometry'] = $value['geometry'];
	$props = array(
		"occupants"=>$value['occupants']
	);
	$adres['properties'] = $props;
	$fc['features'][] = $adres;
	
}

//echo $i;
//print_r($streetlist);
//die;

$geojson = json_encode($fc);

header('Content-Type: application/json');
echo $geojson;





function wkt2geojson($wkt){
	$coordsstart = strpos($wkt,"(");
	$type = trim(substr($wkt,0,$coordsstart));
	$coordstring = substr($wkt, $coordsstart);

	switch ($type) {
	    case "LINESTRING":
	    	$geom = array("type"=>"LineString","coordinates"=>array());
			$coordstring = str_replace(array("(",")"), "", $coordstring);
	    	$pairs = explode(",", $coordstring);
	    	foreach ($pairs as $k => $v) {
	    		$coords = explode(" ", trim($v));
	    		$geom['coordinates'][] = array((double)$coords[0],(double)$coords[1]);
	    	}
	    	return $geom;
	    	break;
	    case "POLYGON":
	    	$geom = array("type"=>"Polygon","coordinates"=>array());
			preg_match_all("/\([0-9. ,]+\)/",$coordstring,$matches);
	    	//print_r($matches);
	    	foreach ($matches[0] as $linestring) {
	    		$linestring = str_replace(array("(",")"), "", $linestring);
		    	$pairs = explode(",", $linestring);
		    	$line = array();
		    	foreach ($pairs as $k => $v) {
		    		$coords = explode(" ", trim($v));
		    		$line[] = array((double)$coords[0],(double)$coords[1]);
		    	}
		    	$geom['coordinates'][] = $line;
	    	}
	    	return $geom;
	    	break;
	    case "MULTILINESTRING":
	    	$geom = array("type"=>"MultiLineString","coordinates"=>array());
	    	preg_match_all("/\([0-9. ,]+\)/",$coordstring,$matches);
	    	//print_r($matches);
	    	foreach ($matches[0] as $linestring) {
	    		$linestring = str_replace(array("(",")"), "", $linestring);
		    	$pairs = explode(",", $linestring);
		    	$line = array();
		    	foreach ($pairs as $k => $v) {
		    		$coords = explode(" ", trim($v));
		    		$line[] = array((double)$coords[0],(double)$coords[1]);
		    	}
		    	$geom['coordinates'][] = $line;
	    	}
	    	return $geom;
	    	break;
	    case "POINT":
			$coordstring = str_replace(array("(",")"), "", $coordstring);
	    	$coords = explode(" ", $coordstring);
	    	//print_r($coords);
	    	$geom = array("type"=>"Point","coordinates"=>array((double)$coords[0],(double)$coords[1]));
	    	return $geom;
	        break;
	    case "MULTIPOINT":
	    	$geom = array("type"=>"MultiPoint","coordinates"=>array());
	    	preg_match_all("/\([0-9. ,]+\)/",$coordstring,$matches);
	    	//print_r($matches);
	    	foreach ($matches[0] as $linestring) {
	    		$linestring = str_replace(array("(",")"), "", $linestring);
		    	$pairs = explode(",", $linestring);
		    	$points = array();
		    	foreach ($pairs as $k => $v) {
		    		//print_r($v);
		    		$coords = explode(" ", trim($v));
		    		$points[] = array((double)$coords[0],(double)$coords[1]);
		    	}
		    	$geom['coordinates'] = $points;
	    	}
	    	return $geom;
	    	break;
	}
}




?>