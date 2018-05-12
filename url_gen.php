<?php
/*
*	URL list generator based on city and county data from a txt file
*	Author: Daniel Harris @TheWebAuthor
*/

$cities = [];
$file = fopen("markets.txt","r");
while (($data = fgetcsv($file)) !== FALSE) {
    $cities[] = strtolower(str_replace(" ", "-", $data[0]));
}

$counties = [];
$file = fopen("counties.txt","r");
while (($data = fgetcsv($file)) !== FALSE) {
    $counties[] = strtolower(str_replace(" ", "-", $data[0]));
}

$places = array_merge($cities, $counties);
sort($places);

foreach ($places as $place) {
    file_put_contents("urls.txt", "https://spacecoastsites.com/".place_url_encode($place)."-seo/\n", FILE_APPEND);
}

/*foreach ($places as $place) {
    file_put_contents("urls.txt", "https://spacecoastsites.com/".place_url_encode($place)."-web-design/\n", FILE_APPEND);
}*/

function place_url_encode($city) {
    return str_replace(" ", "-", urlencode(strtolower($city)));
}
?>