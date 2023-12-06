<?php


include("settings.php");
include("occupations.php");
include("streets.php");


?><!DOCTYPE html>
<html>
<head>
  
<title>Adresboek 1907</title>

  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <script
  src="https://code.jquery.com/jquery-3.2.1.min.js"
  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
  crossorigin="anonymous"></script>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.1.0/dist/leaflet.css" integrity="sha512-wcw6ts8Anuw10Mzh9Ytw4pylW8+NAD4ch3lqm9lzAsTxg0GFeJgoAtxuCLREZSC5lUXdVyo/7yfsqFjQ4S+aKw==" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.1.0/dist/leaflet.js" integrity="sha512-mNqn2Wg7tSToJhvHcqfzLMU6J4mkOImSPTxVZAdo+lcPlk+GhZmYgACEe0x35K7YzW1zJ7XyJV/TT1MrdXvMcA==" crossorigin=""></script>
  <link rel="stylesheet" href="styles.css" />

  
</head>
<body>

<div id="bigmap"></div>


<div id="legenda">
  <h1>&#10021; adresboek 1907 &#10021;</h1>

  <a class="profession">by profession</a> | 
  <a class="streets">by street</a> | 
  <a class="alphabetical">alphabetical</a> | 
  <a class="corps">corps</a> | 


  
  <div id="searchinfo"></div>

  <div id="adres"></div>
  <div id="occupants"></div>



  <form>
    <select style="width: 80%" name="q">
      <option value="">select an occupation / profession</option>
      <?php echo $options ?>
    </select>
    <button>go</button>
  </form>
  or
  <form>
    <select style="width: 80%" name="q">
      <option value="">select an Adamlink street</option>
      <?php echo $streetoptions ?>
    </select>
    <button>go</button>
  </form>
  or
  <form>
    <select style="width: 80%" name="q">
      <option value="">select a HISCO category</option>
      <?php echo $hiscocatoptions ?>
    </select>
    <button>go</button>
  </form>
  
  more info on <a href="https://github.com/mmmenno/adresboek">app on GitHub</a> and <a href="https://gitlab.com/uvacreate/amsterdam-time-machine/adresboeken">data on GitLab</a>

</div>

<script>
  $(document).ready(function() {

    createMap();
    refreshMap();

    document.body.onkeydown = function(e){
      if(e.keyCode == 32){
          tileLayer.setOpacity(0)
       }
    };
    document.body.onkeyup = function(e){
      if(e.keyCode == 32){
          tileLayer.setOpacity(1)
       }
    };



  });

  function createMap(){
    center = [52.370216, 4.895168];
    zoomlevel = 14;
    
    map = L.map('bigmap', {
          center: center,
          zoom: zoomlevel,
          minZoom: 1,
          maxZoom: 19,
          scrollWheelZoom: true,
          zoomControl: false
      });

    L.control.zoom({
        position: 'bottomright'
    }).addTo(map);

    /*
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 19
    }).addTo(map);
    */

    /*
    overviewLayer = L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}{r}.png', {
      maxZoom: 15,
      attribution: '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a>, &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
    }).addTo(map);
    */

    overviewLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
      attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ',
      maxZoom: 15,
      minZoom: 0
    }).addTo(map);
    

    baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      minZoom: 15,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);


    tileLayer = L.tileLayer('https://images.huygens.knaw.nl/webmapper/maps/pw-1909/{z}/{x}/{y}.png', {
      attribution: 'map provided by HicSuntLeones',
      maxZoom: 19,
      minZoom:15
    }).addTo(map);

  }


  

  function refreshMap(){

      


    $.ajax({
          type: 'GET',
          url: 'geojson.php',
          dataType: 'json',
          data: {
            q: "<?= $_GET['q'] ?>"
          },
          success: function(jsonData) {
            if (typeof lps !== 'undefined') {
              map.removeLayer(lps);
            }

            lps = L.geoJson(null, {
              pointToLayer: function (feature, latlng) {                    
                  return new L.CircleMarker(latlng, {
                      color: "#a50026",
                      radius:8,
                      weight: 0,
                      opacity: 0.6,
                      fillOpacity: 0.6,
                      clickable: true
                  });
              },
              onEachFeature: function(feature, layer) {
                layer.on({
                    click: whenClicked
                  });
                }
              }).addTo(map);

              lps.addData(jsonData).bringToFront();
          
              map.fitBounds(lps.getBounds());

              var geojsonprops = jsonData['properties'];
              console.log(geojsonprops);

              var infotext = "<br />searched for:<br />";
              infotext += "<strong>" + geojsonprops['searchedfor'] + "</strong><br /><br />";
              infotext += "<a target=\"_blank\" href=\"geojson.php?q=" + geojsonprops['searchedfor'] + "\">";
              infotext += geojsonprops['nrfound'] + " addresses</a> located on map, ";
              infotext += "<a target=\"_blank\" href=\"not-shown.php?q=" + geojsonprops['searchedfor'] + "\">";
              infotext += geojsonprops['nrnotfound'] + "";
              infotext += " addresses</a> could - for various reasons - not be shown on map";
              $('#searchinfo').html(infotext);
          },
          error: function() {
              console.log('Error loading data');
          }
      });
  }

  function getColor(props) {

    
      return '#a50026';
  }

  function getSize(props) {

    if(props['nlabel'] == null){
      return 6;
    }

    return 6;
  }

function whenClicked(){
   $("#intro").hide();

   var props = $(this)[0].feature.properties;
   console.log(props);
   $("#straatlabel").html('<h2><a target="_blank" href="' + props['wdid'] + '">' + props['label'] + '</a></h2>');

   var occupants = "";
   $.each(props['occupants'],function(index,value){
    occupants += "<a class=\"" + value['part'] + "\" target=\"_blank\" href=\"" + value['scan'] + "\">" + value['label'] + "</a><br />";
    occupants += "<span class=\"small\">[" + value['ocr'] + "]</span><br />";

   });
   $("#occupants").html(occupants);
    
    
}

</script>



</body>
</html>
