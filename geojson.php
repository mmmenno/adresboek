<?

include("settings.php");

if(isset($_GET['q']) && strlen($_GET['q']) > 1){
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
		$lps[$lp['lpnr']]['geometry'] = wkt2geojson($lp['wkt']);
		$lps[$lp['lpnr']]['occupants'][] = array(
			"id" => "https://resolver.clariah.org/atm/addressbooks/1907/" . $row['id'],
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

$contextjson = '{
    "geojson": "https://purl.org/geojson/vocab#",
    "Feature": "geojson:Feature",
    "FeatureCollection": "geojson:FeatureCollection",
    "GeometryCollection": "geojson:GeometryCollection",
    "LineString": "geojson:LineString",
    "MultiLineString": "geojson:MultiLineString",
    "MultiPoint": "geojson:MultiPoint",
    "MultiPolygon": "geojson:MultiPolygon",
    "Point": "geojson:Point",
    "Polygon": "geojson:Polygon",
    "bbox": {
      "@container": "@list",
      "@id": "geojson:bbox"
    },
    "coordinates": {
      "@container": "@list",
      "@id": "geojson:coordinates"
    },
    "features": {
      "@container": "@set",
      "@id": "geojson:features"
    },
    "geometry": "geojson:geometry",
    "id": "@id",
    "properties": "geojson:properties",
    "type": "@type",
    "description": "http://purl.org/dc/terms/description",
    "title": "http://purl.org/dc/terms/title",
    "label": "http://schema.org/name",
	"occupants": { "@reverse": "http://schema.org/address" }
}';
$context = json_decode($contextjson);

$fc = array("@context"=>$context,"type"=>"FeatureCollection", "properties"=>$colprops, "features"=>array());

foreach ($lps as $key => $value) {
	
	$adres = array("type"=>"Feature");
	//$adres['id'] = "http://resolver.clariah.org/hisgis/lp/geometry/" . $key;
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