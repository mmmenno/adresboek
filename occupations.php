<?

$options = "";
$hiscocats = array();

if (($handle = fopen("data/beroepen-hisco.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if($data[1]=="normalised"){
            continue;
        }
    	if($data[1]==$_GET['q']){
    		$options .= "<option selected=\"s\">" . $data[1] . "</option>\n";
    	}else{
    		$options .= "<option>" . $data[1] . "</option>\n";
    	}
        $hiscocats[$data[2]] = $data[3];

    }
    fclose($handle);
}

asort($hiscocats);

$hiscocatoptions = "";
foreach ($hiscocats as $key => $value) {
    if($key==$_GET['q']){
        $hiscocatoptions .= "<option value=" . $key . " selected=\"s\">" . $value . "</option>\n";
    }else{
        $hiscocatoptions .= "<option value=" . $key . ">" . $value . "</option>\n";
    }
}