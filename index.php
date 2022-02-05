
  <!DOCTYPE html>
  <html>
    <head>
      <!--Import Google Icon Font-->
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
      <!--Import materialize.css-->
      <link type="text/css" rel="stylesheet" href="css/materialize.min.css"  media="screen,projection"/>

      <!--Let browser know website is optimized for mobile-->
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	  

    </head>

    <body>

	  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
      <script type="text/javascript" src="js/materialize.min.js"></script>
	  <script type="text/javascript">$(document).ready(function() {
  $('.collapsible').collapsible({
    accordion: false
  });
});
</script>

<script type="text/javascript">
function calcLP(totalLP, total1, total2, token1, token2, pool) {


	var yourLP1 = document.getElementById(pool+"-ownedLP").value;
	if (yourLP1 === undefined) {
		yourLP1 = 0;
	}
	var yourLP = yourLP1.replace(/[^0-9\.]/g,'');
	var yourtoken1 = yourLP * (total1 / totalLP);
	var yourtoken2 = yourLP * (total2 / totalLP);
	var token1id =  pool + "---" + token1;

	
	var token1display = document.getElementById(pool + "---" + token1 + "-sum");
	var token2display = document.getElementById(pool + "---" + token2 + "-sum");
	
	token1display.innerText = yourtoken1.toFixed(5);
	token2display.innerText = yourtoken2.toFixed(5);
}
</script>
<style>
h6 {
    display: inline-block;
}
</style>
	  
	  <div class="container">
        <!-- Page Content goes here -->
	 
	  <div class="row">
    <div class="col s12 m6 offset-m3 center">
	<br>
	<a href="https://scrthost.xiphiar.com/myliquidity">Click here to try the new Keplr-connected version!</a>
 	</div></div>
  <div class="row">
    <div class="col s12 m6 offset-m3">
      <ul class="collapsible">




<?php
include('config.php');

#$host = '127.0.0.1';
$host = $host;
$db   = $database;
$user = $username;
$pass = $password;
$charset = 'utf8mb4';

$backend_url = $backendurl;

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];


try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}


$str = file_get_contents('https://api-bridge-mainnet.azurewebsites.net/secretswap_pools');
$json = json_decode($str, true); // decode the JSON into an associative array



$pools = $json['pools'];

foreach($pools as $key => $data) {
	
	$c1 = $data['assets'][0]['info']['token']['contract_addr'];
	if (is_null($c1)) {
		$c1 = $data['assets'][0]['info']['native_token']['denom'];
	}
	$stmt = $pdo->prepare('SELECT label, decimals FROM contracts WHERE address = ?');
	$stmt->execute([$c1]);
	$sqlout = $stmt->fetch();
	if (!$sqlout) {
		#echo 'Didnt find '.$c1.' in DB, fetching...';
		#$str = file_get_contents('https://lcd-secret.keplr.app/wasm/contract/'.$c1);
		$str = file_get_contents($backend_url.'tokens/address/'.$c1);
		$c1json = json_decode($str, true); // decode the JSON into an associative array
		$label = $c1json['data'][0]['label'];
		$decimals = $c1json['data'][0]['decimals'];
	} else {
		$label = $sqlout['label'];
		$divisor1 = pow(10, $sqlout['decimals']);
	}
	
	
	$c1label = $label;
	
	
	$c2 = $data['assets'][1]['info']['token']['contract_addr'];
	if (is_null($c2)) {
		$c2 = $data['assets'][1]['info']['native_token']['denom'];
	}
	$stmt = $pdo->prepare('SELECT label, decimals FROM contracts WHERE address = ?');
	$stmt->execute([$c2]);
	$sqlout = $stmt->fetch();
	if (!$sqlout) {
		#echo 'Didnt find '.$c2.' in DB, fetching...';
		#$str = file_get_contents('https://lcd-secret.keplr.app/wasm/contract/'.$c2);
		$str = file_get_contents($backend_url.'tokens/address/'.$c2);
		$c1json = json_decode($str, true); // decode the JSON into an associative array
		$label = $c1json['data'][0]['label'];
		$divisor2 = pow(10, $c1json['data'][0]['decimals']);
		
		try {
			$stmt = $pdo->prepare('INSERT INTO contracts (address, label) VALUES (?, ?)');
			$stmt->execute([$c2, $label]);
			echo 'Added '.$label.' to DB.';
		} catch (PDOException $e) {
			$existingkey = "Integrity constraint violation: 1062 Duplicate entry";
			if (strpos($e->getMessage(), $existingkey) !== FALSE) {

				// Take some action if there is a key constraint violation, i.e. duplicate name
			} else {
				throw $e;
			}
		}
	} else {
		$label = $sqlout['label'];
		$divisor2 = pow(10, $sqlout['decimals']);
	}
	#echo '<pre>' . print_r($sqlout, true) . '</pre>';
	#print $sqlout['decimals'].'  --  '.$divisor2.'<br>';
	
	
	$c2label = $label;
	#var_dump($label);
	
	$newkey = $c1label.' -- '.$c2label;

	
	
	#echo 'done<br>';
	unset($pools[$key]);
	#$pools[$newkey] = $data;
	$test = $data['assets'][0]['amount'];
	$asset1total = $test / $divisor1;
	
	$asset2total = ($data['assets'][1]['amount']) / $divisor2;
	$pools[$newkey] = array($c1label=>$asset1total,$c2label=>$asset2total,"total_lp_tokens"=>($data['total_share']/1000000));
}
#echo '<pre>' . print_r($pools, true) . '</pre>';

ksort($pools);
#echo(json_encode($pools));
#exit;

foreach ($pools as $key => $data) {
	$token1 = array_keys($data)[0];
	$token2 = array_keys($data)[1];

echo '   <li>
		  <div class="collapsible-header"><i class="material-icons">swap_horiz</i><b>'.$key.'</b></div>
		  <div class="collapsible-body">
			<span>
			  
				<p><b>Total LP Tokens:  </b>'.$data['total_lp_tokens'].'</p>
				<p><b>Total Pooled '.$token1.':  </b>'.$data[$token1].'</p>
				<p><b>Total Pooled '.$token2.':  </b>'.$data[$token2].'</p>
			  <br><br>
			  <p>
			  <div class="row">
				<b>Your LP tokens: </b>
			  </div>
			  <div class="row">
				<div class="col s8 m6">
					<input type="number" id="'.str_replace(' ', '', $key).'-ownedLP" oninput=\'calcLP('.$data['total_lp_tokens'].', '.$data[$token1].', '.$data[$token2].', "'.$token1.'", "'.$token2.'", "'.str_replace(' ', '', $key).'")\'>
				</div>
			  </div>
			  </p>
			  <div class="row">
				<div class="col s6">
					<p><b>Your Pooled '.$token1.':</b> <h6 id="'.str_replace(' ', '', $key).'---'.$token1.'-sum">0.00000</h6></p>
				</div>
				<div class="col s6">
					<p><b>Your Pooled '.$token2.':</b> <h6 id="'.str_replace(' ', '', $key).'---'.$token2.'-sum">0.00000</h6></p>
				</div>
			  </div>
			</span>
		  </div>
        </li>';

}

?>
			</ul>
		</div>
    </body>
  </html>
