<?php
/*
*	Script that gets nearby cities and inserts them into a MySQL database. Gets city data from a txt file
*	Author: Daniel Harris @TheWebAuthor
*/

error_reporting(0);
ini_set('memory_limit','-1');
require 'simple_html_dom.php';

$cities = [];
$file = fopen("markets.txt","r");
while (($data = fgetcsv($file)) !== FALSE) {
    foreach($data as $d) {
        $cities[] = str_replace(" ", "-", $d);
    }
}

$cities2 = [];
$file = fopen("markets.txt","r");
while (($data = fgetcsv($file)) !== FALSE) {
    foreach($data as $d) {
        $cities2[] = $d;
    }
}

$mysqli = mysqli_connect("localhost", "", "", "");

$aContext = array(
    'https' => array(
        'follow_location' => false
    ),
    'ssl' => array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    )
);

$cxContext = stream_context_create($aContext);

$cities_out = "";
foreach ($cities as $city) {
    if (!strstr($city, "County") && $city != "Florida" && $city != "Space Coast") {
        try {
			echo str_replace("-", " ", $city)."\t\t";
			$data = @file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=$city,%20Florida", false, $cxContext);
			if ($data === false) {
				echo "Failed to get Google data";
			}
			else {
				$geocodeObject = json_decode($data);

				$latitude = $geocodeObject->results[0]->geometry->location->lat;
				$longitude = $geocodeObject->results[0]->geometry->location->lng;

				$responseStyle = 'short'; // the length of the response
				$citySize = 'cities15000'; // the minimal number of citizens a city must have
				$radius = 50; // the radius in KM
				$maxRows = 5; // the maximum number of rows to retrieve
				$username = 'daharris'; // the username of your GeoNames account
				
				// get nearby cities based on range as array from The GeoNames API
				$data2 = @file_get_contents('http://api.geonames.org/findNearbyPlaceNameJSON?lat='.$latitude.'&lng='.$longitude.'&style='.$responseStyle.'&cities='.$citySize.'&radius='.$radius.'&maxRows='.$maxRows.'&username='.$username, false, $cxContext);
				if ($data2 === false) {
					echo "Failed to get Geoname";
				}
				else {
					$nearbyCities = json_decode($data2);
					
					$nearbycs = [];

					foreach($nearbyCities->geonames as $cityDetails)
					{
						if ($cityDetails->name != $city && in_array($cityDetails->name, $cities2)) {
							$nearbycs[] = $cityDetails->name;
						}
					}

					$nearby = implode(", ", $nearbycs);
					$nearby = addslashes($nearby);

					echo str_replace("-", " ", $city)."\t\t".$headers[0]."\t\t";

					echo (q("UPDATE markets SET nearby_markets='$nearby' WHERE `name`='".str_replace("-", " ", $city)."'") ? "Success" : "Failed");  					
				}
			}
        }
        catch (Exception $e) {
            echo "Error: {$e->getMessage()}";
        }
    }
    echo "\n";
}



function q($sql, $notify_me=true){
    global $mysqli;

    $result = mysqli_query($mysqli, $sql);
    
    if (sql_error($result, $sql)){
        return false;
    }
    else {	
        if ($result === true){
            return true;
        }
        else {
            switch(mysqli_num_rows($result)){
                case 0:
                    return false;
                break;			
                default:
                    if ($single){
                        $data = mysqli_num_fields($result) > 1 ? mysqli_fetch_assoc($result) : mysqli_result($result, 0);
                    }
                    else {
                        while($data[]=mysqli_fetch_assoc($result));
                        $data = array_filter($data);
                    }
                break;	
            }
            return $data;
        }
    }
}

function sql_error($result, $sql='') {
    global $mysqli;
    
    switch (true) {
        case mysqli_connect_error():
            $info = debug_backtrace();		
        
            echo (date("n-d-Y g:i a")."\t".$_SERVER['REQUEST_URI']."\nLine: ".$info[1]['line']."\nError #:".mysqli_connect_errno()."\nError Message:".mysqli_connect_error()."\nQuery: ".$sql."\n\n");

            return true;			
        break;
        case mysqli_error($mysqli):
            $info = debug_backtrace();		
        
            echo (date("n-d-Y g:i a")."\t".$_SERVER['REQUEST_URI']."\nLine: ".$info[1]['line']."\nError #:".mysqli_errno($mysqli)."\nError Message:".mysqli_error($mysqli)."\nQuery: ".$sql."\n\n");
            
            return true;			
        break;
        default:
            return false;
        break;
    }
}
?>