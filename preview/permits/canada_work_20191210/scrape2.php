<?php
require_once "./connect_db.php";

$rowsJSON = "";
$rows = "";
$global_ward_name="";
$wards_arr=file('wards');
$count_wards=count($wards_arr);
$ward_rows = implode('',$wards_arr);//file_get_contents('wards');
//$curr_year=date('Y');
$date_start=date('Y-m-d H:i:s');
$upload_time=strtotime(date('Y').'-01-01')-62*24*3600;
echo date('Y-m-d', $upload_time)."\n";
$pos=0;
$duplicate_rows="";
$sql="INSERT INTO applications (ward, statusCode, folderYear, folderSequence, folderName, folderRsn, folderType, folderSection, folderRevision, statusDesc, folderTypeDesc, inDate, app_descr) VALUES (:ward, :statusCode, :folderYear, :folderSequence, :folderName, :folderRsn, :folderType, :folderSection, :folderRevision, :statusDesc, :folderTypeDesc, :inDate, :app_descr)";
$row_ins=$pdo->prepare($sql);
$sql_upd="UPDATE settings_ SET ward=:ward, pos=:pos, amount_apps=:amount, date_start=:date_start, date_curr=:date_curr, upload_status=:upload_status, street=:street";
$row_upd=$pdo->prepare($sql_upd);
$sql_upd_wards="UPDATE wards SET last_update=:date_curr, complete_update=0, last_address=:street WHERE ward=:ward";
$row_upd_wards=$pdo->prepare($sql_upd_wards);

unlink('rows_temp');
$url_descr='http://app.toronto.ca/ApplicationStatus/jaxrs/search/detail/';
foreach (preg_split('/[\r\n]+/', $ward_rows) as $row){

    $data = preg_split('/[\,\"]+/', $row);


    $url = 'http://app.toronto.ca/ApplicationStatus/jaxrs/search/folders';

    //open connection
    $ch = curl_init($url);

    $data_string = '{"ward":"' . $data[1] . '","folderYear":"","folderSequence":"","folderSection":"","folderRevision":"","folderType":"","address":"' . $data[5] . ' ' . $data[6] . '","searchType":"0","propX_min":"0","propX_max":"0","propY_min":"0","propY_max":"0","propertyRsn":"' . $data[2] . '"}';
    $ward=$data[1];
    $street=trim($data[6]);
    $last_address="";
    $sql_check_ward="SELECT last_address FROM wards WHERE ward=:ward AND complete_update=0";
    $row_check=$pdo->prepare($sql_check_ward);
    $row_check->execute(['ward' => $ward]);
    if($row_address=$row_check->fetch())
    {
        $last_address=$row_address['last_address'];
    }
    if (strcasecmp($street, $last_address)<0)
    {
        echo "skipped:".$data[3]."\n";
        continue;
    }
    if ($global_ward_name!="" && $global_ward_name!=$ward)
    {
        file_put_contents($global_ward_name.'_data.csv', $rows);
        $global_ward_name=$ward;
        $rows="";
    }
    //set the url, number of POST vars, POST data
    curl_setopt($ch,CURLOPT_URL, $url);
    //curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );
    //execute post
    $result = curl_exec($ch);
print curl_error($ch);
    $rowsJSON .= "$result\n";
print "$result\n";

    if ($data = json_decode($result)){
        $pos++;
        $rows_temp=fopen('rows_temp', 'a');
        foreach ($data as $index => $obj){
            $ch_descr = curl_init();
            curl_setopt($ch_descr,CURLOPT_URL, $url_descr.$obj->folderRsn);
            curl_setopt($ch_descr, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch_descr, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch_descr, CURLOPT_TIMEOUT, 2);
            //curl_setopt($ch_descr,CURLOPT_POSTFIELDS, '');
            //curl_setopt($ch_descr, CURLOPT_HTTPHEADER, array(
            //    'Content-Type: application/json',
            //    'Content-Length: ' . strlen(''))
            //);
            $result_descr = curl_exec($ch_descr);
            //var_dump($result_descr)."\n\n";
            $app_descr="";
            if($data_descr = json_decode($result_descr))
            {
                $app_descr=$data_descr->folderDescription;
            }
            try
            {
        		foreach ($obj as $prop => $val)
                {
        		    $rows .= "\"{$obj->$prop}\",";
                    fwrite($rows_temp, "\"{$obj->$prop}\",");
        		}   
                $intime=strtotime($obj->inDate)+43200; 
                if ($intime<$upload_time)
                    continue;     
                $row_ins->execute([
                    'ward'          => $ward,
                    'statusCode'    => $obj->statusCode, 
                    'folderYear'    => $obj->folderYear,
                    'folderSequence'    => $obj->folderSequence,
                    'folderName'    => $obj->folderName,
                    'folderRsn'     => $obj->folderRsn,
                    'folderType'    => $obj->folderType,
                    'folderSection' => $obj->folderSection,
                    'folderRevision'=> $obj->folderRevision,
                    'statusDesc'    => $obj->statusDesc,
                    'folderTypeDesc'=> $obj->folderTypeDesc,
                    'inDate'        => date("Y-m-d H:i:s", $intime),
                    'app_descr'     => $app_descr,
                ]);
                $row_upd->execute([
                    'ward'  => $ward,
                    'pos'   => $pos,
                    'amount' => $count_wards,
                    'date_start'=> $date_start,
                    'date_curr' => date('Y-m-d H:i:s'),
                    'upload_status' => 1,
                    'street'    => $street,
                ]);
                $row_upd_wards->execute([
                    'date_curr' => date('Y-m-d H:i:s'),
                    'street'    => $street,
                    'ward'      => $ward,
                ]);
            }
            catch (PDOException $e)
            {
                if(preg_match('/Duplicate entry/i', $e->getMessage())==1) 
                {
                    $duplicate_rows.=$e->getMessage()."\n".print_r($obj, TRUE)."\n";
                }
                else
                {    
                    $rows_error=fopen('rows_error', 'a');
                    fwrite($rows_error, "sql_error:".$e->getMessage()."\n");
                    fwrite($rows_error, print_r($obj, TRUE));
                    fclose($rows_error);
                }
            }
	$rows .= "\"\"\n";
    fwrite($rows_temp, "\"\"\n");
    fflush($rows_temp);
	}
    fclose($rows_temp);
    }
    //close connection
    curl_close($ch);
    sleep(5);
}
try
{
    $sql_upd_fin="UPDATE settings_ SET pos=:pos, amount_apps=:amount, date_curr=:date_curr, upload_status=:upload_status";
    $row_upd_fin=$pdo->prepare($sql_upd_fin);
    $row_upd_fin->execute([
                        'pos'   => $pos,
                        'amount' => $count_wards,
                        'date_curr' => date('Y-m-d H:i:s'),
                        'upload_status' => 0,
                    ]);
    $sql_upd_wards="UPDATE wards SET last_update=:date_curr, complete_update=1, last_address='' WHERE ward=:ward";
    $row_upd_wards=$pdo->prepare($sql_upd_wards);
    $row_upd_wards->execute([
        'date_curr' => date('Y-m-d H:i:s'),
        'ward'      => $ward,
    ]);
}
catch (PDOException $e)
{
    $rows_error=fopen('rows_error', 'a');
    fwrite($rows_error, "sql_error:".$e->getMessage()."\n");
    fwrite($rows_error, print_r($obj, TRUE));
    fclose($rows_error);
}

file_put_contents('rows_raw', $rowsJSON, FILE_APPEND);

file_put_contents($ward.'_data.csv', $rows);
if ($duplicate_rows!='')
{
    file_put_contents('duplicate_rows', $duplicate_rows, FILE_APPEND);
}

