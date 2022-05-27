<?php
if(isset($_GET['ajax_csv'])) {

$user = shell_exec("awk -F: '/1000/{print $1}' /etc/passwd");
$home = shell_exec("awk -F: '/1000/{print $6}' /etc/passwd");
$home = trim($home);
$files = scandir($home."/BirdSongs/".date('F-Y')."/".date('j-l')."/", SCANDIR_SORT_ASCENDING);
$newest_file = $files[2];


if($newest_file == $_GET['newest_file']) {
  die();
} 

echo "file,".$newest_file."\n";

$row = 1;
if (($handle = fopen($home."/BirdSongs/".date('F-Y')."/".date('j-l')."/".$newest_file.".csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if($row != 1){
          $num = count($data);
          for ($c=0; $c < $num; $c++) {
              $exp = explode(';',$data[$c]);
              echo $exp[0].",".$exp[3]."\n";
          }
        }
        $row++;
    }
    fclose($handle);
}
die();
}
?>
<script>  
// CREDITS: https://codepen.io/jakealbaugh/pen/jvQweW

// UPDATE: there is a problem in chrome with starting audio context
//  before a user gesture. This fixes it.
var started = null;
var player = null;
const ctx = null;
let fps =[];
let avgfps;
let requestTime;
window.onload = function(){
  // if user agent includes iPhone or Mac use legacy mode
  if(window.navigator.userAgent.includes("iPhone") || window.navigator.userAgent.includes("Mac")) {
    document.getElementById("spectrogramimage").style.display="";
    document.body.querySelector('canvas').remove();
    document.getElementById('player').remove();
    document.body.querySelector('h1').remove();

    <?php 
    if (file_exists('./scripts/thisrun.txt')) {
    $config = parse_ini_file('./scripts/thisrun.txt');
  } elseif (file_exists('./scripts/firstrun.ini')) {
    $config = parse_ini_file('./scripts/firstrun.ini');
  }
  $refresh = $config['RECORDING_LENGTH'];
  $time = time();
  ?>
    // every $refresh seconds, this loop will run and refresh the spectrogram image
  window.setInterval(function(){
    document.getElementById("spectrogramimage").src = "/spectrogram.png?nocache="+Date.now();
  }, <?php echo $refresh; ?>*1000);
  } else {
    document.getElementById("spectrogramimage").remove();

  var audioelement =  window.parent.document.getElementsByTagName("audio")[0];
  if (typeof(audioelement) != 'undefined') {

    document.getElementById('player').remove();

    player = audioelement;
  } else {
    player = document.getElementById('player');
  }
  player.play();
  if (started) return;
    started = true;
    initialize();
  }
};

function fitTextOnCanvas(text,fontface,yPosition){    
    var fontsize=300;
    do{
        fontsize--;
        CTX.font=fontsize+"px "+fontface;
    }while(CTX.measureText(text).width>document.body.querySelector('canvas').width)
    CTX.font = CTX.font=(fontsize*0.35)+"px "+fontface;
    CTX.fillText(text,document.body.querySelector('canvas').width - (document.body.querySelector('canvas').width * 0.50),yPosition);
}

function applyText(text,x,y) {
  console.log(text+" "+parseInt(x)+" "+y)
    CTX.fillStyle = 'white';
  CTX.font = '15px Roboto Flex';
  //fitTextOnCanvas(text,"Roboto Flex",document.body.querySelector('canvas').scrollHeight * 0.35)
  CTX.fillText(text,parseInt(x),y)
  CTX.fillStyle = 'hsl(280, 100%, 10%)';
}

var newest_file;
function loadDetectionIfNewExists() {
  const xhttp = new XMLHttpRequest();
  xhttp.onload = function() {
    // if there's a new detection that needs to be updated to the page
    if(this.responseText.length > 0 && !this.responseText.includes("Database")) {
      
      var split = this.responseText.split("\n")
      for(var i = 1;i < split.length; i++) {
        if(parseInt(split[i].split(",")[0]) >= 0){

          newest_file =  split[0].split(",")[1]
          //applyText(split[i].split(",")[1],document.body.querySelector('canvas').width - ((parseInt(split[i].split(",")[0]))*avgfps), (document.body.querySelector('canvas').height * 0.50))
          
          d1 = new Date(newest_file.split("-")[0]+"/"+newest_file.split("-")[1]+"/"+newest_file.split("-")[2]+ " "+newest_file.split("-")[4].replace(".wav",""))
          console.log(d1)
          d2 = new Date();
          timeDiff = (d2-d1)/1000;
          // Date csv file was created + relative detection time of bird + mic delay
          secago = Math.abs(timeDiff) - split[i].split(",")[0] - 6.8;
          console.log(Math.abs(timeDiff) + " - " + split[i].split(",")[0] + " - 6.8"); 
          applyText(split[i].split(",")[1],document.body.querySelector('canvas').width - ((parseInt(secago))*avgfps), (document.body.querySelector('canvas').height * 0.50))
        }
        
      }
    }
  }
  xhttp.open("GET", "spectrogram.php?ajax_csv=true&newest_file="+newest_file, true);
  xhttp.send();
}

window.setInterval(function(){
   loadDetectionIfNewExists();
}, 500);

function initialize() {
  document.body.querySelector('h1').remove();
  const CVS = document.body.querySelector('canvas');
  CTX = CVS.getContext('2d');
  const W = CVS.width = window.innerWidth;
  const H = CVS.height = window.innerHeight;

  const ACTX = new AudioContext();
  const ANALYSER = ACTX.createAnalyser();

  ANALYSER.fftSize = 2048;  
  
  process();

  function process() {
    const SOURCE = ACTX.createMediaElementSource(player);
    SOURCE.connect(ANALYSER);
    SOURCE.connect(ACTX.destination)
    const DATA = new Uint8Array(ANALYSER.frequencyBinCount);
    const LEN = DATA.length;
    const h = (H / LEN + 0.9);
    const x = W - 1;
    CTX.fillStyle = 'hsl(280, 100%, 10%)';
    CTX.fillRect(0, 0, W, H);

    loop();

    function loop(time) {
      if (requestTime) {
          fpsval = Math.round(1000/((performance.now() - requestTime)))
          if(fpsval > 0){
              fps.push( fpsval);
          }
      }
      if(fps.length > 0){
          avgfps = fps.reduce((a, b) => a + b) / fps.length;
      }
      requestTime = time;
      window.requestAnimationFrame((timeRes) => loop(timeRes));
      let imgData = CTX.getImageData(1, 0, W - 1, H);

      CTX.fillRect(0, 0, W, H);
      CTX.putImageData(imgData, 0, 0);
      ANALYSER.getByteFrequencyData(DATA);
      for (let i = 0; i < LEN; i++) {
        let rat = DATA[i] / 196 ;
        let hue = Math.round((rat * 120) + 280 % 360);
        let sat = '100%';
        let lit = 10 + (70 * rat) + '%';
        CTX.beginPath();
        CTX.strokeStyle = `hsl(${hue}, ${sat}, ${lit})`;
        CTX.moveTo(x, H - (i * h));
        CTX.lineTo(x, H - (i * h + h));
        CTX.stroke();
      }
    }
  }
}

</script>
<style>
html, body {
  height: 100%;
}

canvas {
  display: block;
  height: 85%;
  width: 100%;
}

h1 {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  margin: 0;
}
</style>

<img id="spectrogramimage" style="width:100%;height:100%;display:none" src="/spectrogram.png?nocache=<?php echo $time;?>">

<audio style="display:none" controls="" crossorigin="anonymous" id='player' preload="none"><source src="/stream"></audio>
<h1>Loading...</h1>
<canvas></canvas>
