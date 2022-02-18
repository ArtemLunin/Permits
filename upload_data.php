<?php
require_once "./connect_db.php";
define('PERMITS_PATH', 'https://secure.toronto.ca/ApplicationStatus/jaxrs/search');
define('PERMITS_PROPS', '/properties');
define('PERMITS_DETAIL', '/detail/');
define('PERMITS_FOLDERS', '/folders');
define("hours_shift_12",43200);

set_time_limit(0);

$sql_start = "UPDATE settings_ SET pos=0, date_start=:date_start, date_curr=:date_curr, blocked=0, upload_status=1 WHERE id>0";
$row_start = $pdo->prepare($sql_start);
$row_start->execute(['date_start'=> date('Y-m-d H:i:s'), 'date_curr' => date('Y-m-d H:i:s')]);
$sql = "SELECT ward, last_update, complete_update, last_address FROM wards ORDER BY complete_update, last_update, id";
$row_sel = $pdo->prepare($sql);
$row_sel->execute();
$wards_upload_count = 0;
if ($table_res = $row_sel->fetchall())
{
	foreach ($table_res as $row) 
	{
		$ward_file = 'wards_list/'.$row['ward'].'_address.csv';
		$rows_file = 'rows_list/'.$row['ward'].'_data.csv';
		getWardContent($row['ward'], $ward_file);
	    	//delete old data
	    	try
	    	{
	    		$pdo->beginTransaction();
		    	$sql_del = "DELETE FROM applications WHERE ward=:ward AND hidden=0";
		    	$row_del = $pdo->prepare($sql_del);
		    	$row_del->execute(['ward' => $row['ward']]);
		    	$sql_upd_wards = "UPDATE wards SET complete_update=0, last_address='' WHERE ward=:ward";
				$row_upd_wards = $pdo->prepare($sql_upd_wards);
				$row_upd_wards->execute(['ward' => $row['ward']]);
				$pdo->commit();
		    }
		    catch (PDOException $e)
	        {
	            $rows_error=fopen('logs/rows_error', 'a');
	            fwrite($rows_error, date('Y-m-d H:i:s').","."DELETE, sql_error:".$e->getMessage()."\n");
	            fwrite($rows_error, print_r($obj, TRUE));
	            fclose($rows_error);
	            $pdo->rollback();
	        }
		if (!uploadWardToBase($row['ward'], $ward_file, $rows_file))
		{
			break;
		}
		$wards_upload_count++;
		break;	
	}
	if (file_exists('wards_list/unreaded_rows'))
    {
    	$rows_error=fopen('logs/rows_error', 'a');
	    fwrite($rows_error, date('Y-m-d H:i:s').", finally upload rows_error, ".file_get_contents('wards_list/unreaded_rows')."\r\n");
    	fclose($rows_error);
    	uploadWardToBase($row['ward'], 'wards_list/unreaded_rows', $rows_file);
    }
	$out_res=array(
		'answer'=>'1',
		'ward'	=> $row['ward'],
	);
}
else
{
	$out_res=array(
		'error'	=> '1',
		'text'	=> 'no wards in database',
	);
}
header('Content-type: application/json');
echo json_encode($out_res);


function getWardContent($wardID, $ward_file)
{
	$wards="";
	$url = PERMITS_PATH.PERMITS_PROPS;
	$ch = curl_init($url);
    $data_string = '{"ward":"' . $wardID . '","folderYear":"","folderSequence":"","folderSection":"","folderRevision":"","folderType":"","address":"","searchType":"0","mapX":null,"mapY":null,"propX_min":"0","propX_max":"0","propY_min":"0","propY_max":"0"}';
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );
    $result = curl_exec($ch);
    if ($data = json_decode($result)) {
        foreach ($data as $index => $obj) {
			$streetType = $obj->streetType ?? '' ;
			$wards .= "\"{$wardID}\",\"{$obj->propertyRsn}\",\"{$obj->address}\",\"{$streetType}\",\"{$obj->house}\",\"{$obj->street}\"\n";
		}
    }
    curl_close($ch);
    if (file_exists('wards_list/unreaded_rows'))
    {
    	$wards = file_get_contents('wards_list/unreaded_rows').$wards;
    	@unlink('wards_list/unreaded_rows');
    }
    file_put_contents($ward_file, $wards);
	// error_log('get wards_list for '.$wardID);
    sleep(10);
}

function uploadWardToBase($wardID, $ward_file, $rows_file)
{
	global $pdo;
	$wards_arr = file($ward_file);
	$count_wards = count($wards_arr);
	$ward_rows = implode('', $wards_arr);
	$date_start = date('Y-m-d H:i:s');
	$upload_dt = new DateTime('62 days ago');
	$upload_time = strtotime($upload_dt->format('Y-m-01 00:00:00'));
	$pos = 0;
	$rows = "";
	$duplicate_rows = "";
	$global_ward_name = "";
	$blocked_status = 0;
	$sql = "INSERT INTO applications (ward, statusCode, folderYear, folderSequence, folderName, folderAddress, folderRsn, folderType, folderSection, folderRevision, statusDesc, folderTypeDesc, inDate, app_descr) VALUES (:ward, :statusCode, :folderYear, :folderSequence, :folderName, :folderAddress, :folderRsn, :folderType, :folderSection, :folderRevision, :statusDesc, :folderTypeDesc, :inDate, :app_descr)";
	$row_ins = $pdo->prepare($sql);
	$sql_upd = "UPDATE settings_ SET ward=:ward, pos=:pos, amount_apps=:amount, date_start=:date_start, date_curr=:date_curr, upload_status=:upload_status, street=:street";
	$row_upd = $pdo->prepare($sql_upd);
	$sql_upd_wards = "UPDATE wards SET last_address=:street, complete_update=0 WHERE ward=:ward";
	$row_upd_wards = $pdo->prepare($sql_upd_wards);
	@unlink('rows_temp');
	@unlink('wards_list/unreaded_rows');
	// $url_descr='http://app.toronto.ca/ApplicationStatus/jaxrs/search/detail/';
	$url_descr = PERMITS_PATH.PERMITS_DETAIL;
	// $url = 'http://app.toronto.ca/ApplicationStatus/jaxrs/search/folders';
	$url = PERMITS_PATH.PERMITS_FOLDERS;
	foreach ($wards_arr as $row)
	{
		$sql_sel_sett = "SELECT id, blocked FROM settings_ ORDER BY id LIMIT 1";
		$row_sel_sett = $pdo->prepare($sql_sel_sett);
		$row_sel_sett->execute();
		if ($row_sett = $row_sel_sett->fetch())
		{
			if ($row_sett['blocked'] == 1)
			{
				$blocked_status = 1;
				break;
			}
		}
		$pos++;
	    $data = preg_split('/[\,\"]+/', $row);
	 //open connection
    	$ch = curl_init($url);
	    $data_string = '{"ward":"' . $data[1] . '","folderYear":"","folderSequence":"","folderSection":"","folderRevision":"","folderType":"","address":"' . $data[5] . ' ' . $data[6] . '","searchType":"0","propX_min":"0","propX_max":"0","propY_min":"0","propY_max":"0","propertyRsn":"' . $data[2] . '"}';
    	$ward = $data[1];
    	$street = trim($data[6]);
    	$folderAddress = trim($data[3]);
    	$last_address = "";
	    if ($global_ward_name != "" && $global_ward_name != $ward)
	    {
	        file_put_contents('rows_list/'.$global_ward_name.'_data.csv', $rows);
	        $global_ward_name = $ward;
	        $rows = "";
	    }
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        'Content-Type: application/json',
	        'Content-Length: ' . strlen($data_string))
	    );
    //execute post
    	$result = curl_exec($ch);
    	$curl_error = curl_error($ch);
    	curl_close($ch);
    	if ($curl_error != '')
    	{
    		$curl_error_fd = fopen('logs/curl_error.txt','a');
    		fwrite($curl_error_fd, date('Y-m-d H:i:s').", MAIN INFO, ".$curl_error."\r\n");
    		fclose($curl_error_fd);
    		file_put_contents('wards_list/unreaded_rows', $row, FILE_APPEND);
    	}
	    if ($data = json_decode($result))
	    {
	        $rows = "";
	        $rows_temp = fopen('logs/rows_temp', 'a');
	        foreach ($data as $index => $obj)
	        {
	        	$intime = strtotime($obj->inDate) + hours_shift_12; 
				// print_r($obj);
	            if ($intime < $upload_time) {
	            	continue; 
				}
	        	sleep(2);
	            $ch_descr = curl_init();
	            curl_setopt($ch_descr, CURLOPT_URL, $url_descr.$obj->folderRsn);
	            curl_setopt($ch_descr, CURLOPT_CUSTOMREQUEST, "GET");
	            curl_setopt($ch_descr, CURLOPT_RETURNTRANSFER, 1);
	            curl_setopt($ch_descr, CURLOPT_TIMEOUT, 30);
	           	$result_descr = str_replace('\r\n', '',curl_exec($ch_descr));
	            $curl_error = curl_error($ch_descr);
	            curl_close($ch_descr);
	            if ($curl_error!='')
	            {
		    		$curl_error_fd=fopen('logs/curl_error.txt','a');
		    		fwrite($curl_error_fd, date('Y-m-d H:i:s').", DESCR, ".$curl_error."\r\n");
		    		fclose($curl_error_fd);
		    	}
	            $app_descr="";
	            if($data_descr = json_decode($result_descr))
	            {
	            	if (isset($data_descr->folderDescription))
	            	{
	                	$app_descr=trim($data_descr->folderDescription);
	            	}
	                else
	                {
	                	$rows_error=fopen('logs/curl_error.txt', 'a');
		            	fwrite($rows_error, date('Y-m-d H:i:s').", DESCR, "."app_descr: no description\n");
		                fwrite($rows_error, print_r($result_descr, TRUE)."\r\n");
		                fwrite($rows_error, $row."\r\n");
		                fclose($rows_error);
		            }
	            }
	            else
	            {
	            	$rows_error=fopen('logs/curl_error.txt', 'a');
	            	fwrite($rows_error, date('Y-m-d H:i:s').", DESCR, "."app_descr: no json in answer\r\n");
	                fwrite($rows_error, print_r($row, TRUE));
	                fclose($rows_error);
	            }
	            try
	            {
	        		foreach ($obj as $prop => $val)
	                {
	        		    $rows .= "\"{$obj->$prop}\",";
	                    fwrite($rows_temp, "\"{$obj->$prop}\",");
	                    fflush($rows_temp);
	        		}   
	        		$rows .= "\"{$app_descr}\",";    
	                $row_ins->execute([
	                    'ward'          => $ward,
	                    'statusCode'    => $obj->statusCode, 
	                    'folderYear'    => $obj->folderYear,
	                    'folderSequence'    => $obj->folderSequence,
	                    'folderName'    => $obj->folderName,
	                    'folderAddress'	=> $folderAddress,
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
	                   'street'	=> $street,
	                    'ward'	=> $ward,
	                ]);
	            }
	            catch (PDOException $e)
	            {
	                if(preg_match('/Duplicate entry/i', $e->getMessage()) == 1) 
	                {
	                    $duplicate_rows.=$e->getMessage()."\r\n".print_r($obj, TRUE)."\r\n";
	                }
	                else
	                {    
	                    $rows_error=fopen('logs/rows_error', 'a');
	                    fwrite($rows_error, date('Y-m-d H:i:s').","."INSERT, sql_error:".$e->getMessage()."\r\n");
	                    fwrite($rows_error, print_r($obj, TRUE));
	                    fclose($rows_error);
	                }
	            }
				$rows .= "\"\"\r\n";
			    fwrite($rows_temp, "\"\"\r\n");
			    fflush($rows_temp);
			}
		    fclose($rows_temp);
	    }
    //close connection
	    sleep(3);
	}
	if (!$blocked_status)
	{
		try
		{
		    $sql_upd_fin = "UPDATE settings_ SET pos=:pos, amount_apps=:amount, date_curr=:date_curr, upload_status=:upload_status";
		    $row_upd_fin = $pdo->prepare($sql_upd_fin);
		    $row_upd_fin->execute([
				'pos'   => $pos,
				'amount' => $count_wards,
				'date_curr' => date('Y-m-d H:i:s'),
				'upload_status' => 0,
			]);
			$sql_upd_wards = "UPDATE wards SET last_update=:date_curr, complete_update=1, last_address='' WHERE ward=:ward";
			$row_upd_wards = $pdo->prepare($sql_upd_wards);
			$row_upd_wards->execute([
				'date_curr' => date('Y-m-d H:i:s'),
				'ward'      => $ward,
			]);
		}
		catch (PDOException $e)
		{
		    $rows_error = fopen('logs/rows_error', 'a');
		    fwrite($rows_error, date('Y-m-d H:i:s').','.'UPDATE, sql_error:'.$e->getMessage().'\n');
		    fwrite($rows_error, print_r($obj, TRUE));
		    fclose($rows_error);
		}
	}
	file_put_contents($rows_file, $rows);
	if ($duplicate_rows != '')
	{
	    file_put_contents('logs/duplicate_rows', $duplicate_rows, FILE_APPEND);
	}
	return !$blocked_status;
}
