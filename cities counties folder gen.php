<?php
/*
*	URL folder generator based on city and county data from a txt file
*	Author: Daniel Harris @TheWebAuthor
*/

$cities = [];
$file = fopen("counties.txt","r");
while (($data = fgetcsv($file)) !== FALSE) {
    $cities[] = strtolower(str_replace(" ", "-", $data[0]));
}

foreach ($cities as $city) {
    recurse_copy("web-design", "$city-web-design");
}

function recurse_copy($src,$dst) { 
    $dir = opendir($src); 
    @mkdir($dst); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                recurse_copy($src . '/' . $file,$dst . '/' . $file); 
            } 
            else { 
                copy($src . '/' . $file,$dst . '/' . $file); 
            } 
        } 
    } 
    closedir($dir); 
} 
?>