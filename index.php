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
      <option value="">select a Adamlink street</option>
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
  
  more info on app and data on GitHub

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

    overviewLayer = L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}{r}.png', {
      maxZoom: 15,
      attribution: '&copy; <a href="https://stadiamaps.com/">Stadia Maps</a>, &copy; <a href="https://openmaptiles.org/">OpenMapTiles</a> &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
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
                      color: "#FC2211",
                      radius:4,
                      weight: 1,
                      opacity: 0.8,
                      fillOpacity: 0.8
                  });
              },
              style: function(feature) {
                return {
                    color: getColor(feature.properties),
                    radius: getSize(feature.properties),
                    clickable: true
                };
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
              infotext += geojsonprops['nrfound'] + " entries</a> located on map, ";
              infotext += "<a target=\"_blank\" href=\"not-shown.php?q=" + geojsonprops['searchedfor'] + "\">";
              infotext += geojsonprops['nrnotfound'] + "";
              infotext += " entries</a> could - for various reasons - not be shown on map";
              $('#searchinfo').html(infotext);
          },
          error: function() {
              console.log('Error loading data');
          }
      });
  }

  function getColor(props) {

    
      if(props['aanlegjaar'] == null){
        return '#a50026';
      }

      var j = props['aanlegjaar'];
      return j > 2000 ? '#4575b4' :
             j > 1980 ? '#74add1' :
             j > 1960  ? '#abd9e9' :
             j > 1940  ? '#ffffbf' :
             j > 1920  ? '#fee090' :
             j > 1900  ? '#fdae61' :
             j > 1870   ? '#f46d43' :
                       '#a50026';

    
    
    return '#1DA1CB';
  }

  function getSize(props) {

    if(props['nlabel'] == null){
      return 4;
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
