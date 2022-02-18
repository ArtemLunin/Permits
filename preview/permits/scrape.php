<?php

//for ($i=1; $i<= 25; $i++){
$i=0;
$wardsJSON = "";
$wards = "";
//$wardList = array('W01','W02','W03','S04','W05','N06','W07','N08','S09','S10','S11','S12','S13','S14','N15','N16','N17','N18','S19','E20','E21','E22','E23','E24','E25');
$wardList = array('W02');

foreach($wardList as $wardID){

    $url = 'http://app.toronto.ca/ApplicationStatus/jaxrs/search/properties';
    //$fields = array(
    //    'lname' => urlencode($_POST['last_name']),
    //    'fname' => urlencode($_POST['first_name']),
    //    'title' => urlencode($_POST['title']),
    //    'company' => urlencode($_POST['institution']),
    //    'age' => urlencode($_POST['age']),
    //    'email' => urlencode($_POST['email']),
    //    'phone' => urlencode($_POST['phone'])
    //);

    ////url-ify the data for the POST
    //foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
    //rtrim($fields_string, '&');

    //open connection
    $ch = curl_init($url);

    $data_string = '{"ward":"' . $wardID . '","folderYear":"","folderSequence":"","folderSection":"","folderRevision":"","folderType":"","address":"","searchType":"0","mapX":null,"mapY":null,"propX_min":"0","propX_max":"0","propY_min":"0","propY_max":"0"}';
    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    //curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );
print "I=$wardID\n";
    //execute post
    $result = curl_exec($ch);
print curl_error($ch);
    $wardsJSON .= "$result\n";
    if ($data = json_decode($result)){
        foreach ($data as $index => $obj){
print "$index ";
		$wards .= "\"{$wardID}\",\"{$obj->propertyRsn}\",\"{$obj->address}\",\"{$obj->streetType}\",\"{$obj->house}\",\"{$obj->street}\"\n";
	}
    }

    //close connection
    curl_close($ch);
    sleep(20);
}

file_put_contents('wards_raw', $wardsJSON, FILE_APPEND);
file_put_contents('wards', $wards, FILE_APPEND);

