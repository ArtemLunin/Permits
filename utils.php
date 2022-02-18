<?php
require_once "./connect_db.php";
define('PERMITS_PATH', 'https://secure.toronto.ca/ApplicationStatus/jaxrs/search');
define('PERMITS_PROPS', '/properties');
define('PERMITS_DETAIL', '/detail/');
define('PERMITS_FOLDERS', '/folders');

$out_res=[];
$appTableData=[];

if (isset ($_REQUEST['sign_in']))
{
	if (isset($_POST['username']) && isset($_POST['password']))
	{
	 	// Rfyflf2019
	 	if ($_POST['username']=='app_view' && password_verify($_POST['password'],'$2y$10$dDA31SnWzs0rzhVbKratwe0odNiGJV4f5YGf4SHRjbUejaPfyYs9C'))
	 	{
			$unauthorized=FALSE;
			session_start();
			$_SESSION['logged_user']=$_POST['username'];
			session_write_close();
			$out_res=array('answer'=>'1');
		}
	}
}
$unauthorized=FALSE; // for local use
/*

			session_start();
			$_SESSION['logged_user']=$_POST['username'];
			session_write_close();
			$out_res=array('answer'=>'1');
*/
if (!$unauthorized){

if (isset ($_REQUEST['doShowAppTable']))
{
	$sql="SELECT applications.id, wards.ward_text, statusCode, folderYear, folderSequence, folderName, folderAddress, folderRsn, folderType, folderSection, folderRevision, statusDesc, folderTypeDesc, inDate, app_descr FROM applications, wards WHERE wards.ward=:ward AND hidden=0 AND wards.ward=applications.ward ORDER BY id";
	$row_sel=$pdo->prepare($sql);
	$row_sel->execute(['ward' => trim($_REQUEST['doShowAppTable'])]);
	if ($table_res=$row_sel->fetchall())
	{
		foreach ($table_res as $row) {
			$app_descr="no_data";
			if (isset($row['app_descr']) && $row['app_descr']!="")
				$app_descr=$row['app_descr'];
			$appTableData[]=array(
				'id'			=> (int)$row['id'],
				'folderRsn'		=> $row['folderRsn'],
				'application'	=> $row['folderYear']." ".$row['folderSequence']." ".$row['folderSection']." ".$row['folderRevision'],
				'ward'			=> $row['ward_text'],
				'address'		=> $row['folderAddress'],
				'applicationType'=> $row['folderTypeDesc'],
				'description'	=> $app_descr,
				'date'			=> date("Y-m-d", strtotime($row['inDate'])),
				//'statusCode'	=> (int)$row['statusCode'],
				'status'		=> $row['statusDesc'],

			);
		}
	}
	$out_res=array('answer'=>'1', 'appTableData' => $appTableData);
}
elseif (isset ($_REQUEST['doHideApplication']) && isset($_REQUEST['row_id'])) 
{
	/*
	$count_deleted=0;
	$sql="SELECT COUNT(id) AS count_id FROM applications WHERE hidden=1;";
	$row_sel=$pdo->prepare($sql);
	$row_sel->execute();
	if($row_id=$row_sel->fetch())
	{
		$count_deleted=$row_id['count_id'];
	}
	if ($count_deleted<100)
	{
		$sql="UPDATE applications SET hidden=1 WHERE id=:row_id";
		$row_udp=$pdo->prepare($sql);
		$row_udp->execute(['row_id' => $_REQUEST['row_id']]);
		$out_res=array('answer'=>'1', 'row_id' => (int)$_REQUEST['row_id']);
	}
	else
	{
		$out_res=array('error'=>'1', 'msg' => 'deletion limit exceeded');
	}
	*/
	$sql="UPDATE applications SET hidden=1 WHERE id=:row_id";
	$row_udp=$pdo->prepare($sql);
	$row_udp->execute(['row_id' => $_REQUEST['row_id']]);
	$out_res=array('answer'=>'1', 'row_id' => (int)$_REQUEST['row_id']);
}
elseif (isset($_REQUEST['doGetPos']))
{
	$sql="SELECT settings_.id, wards.ward_text, pos, amount_apps, blocked, date_start, date_curr, TIMESTAMPDIFF(SECOND,date_start, date_curr) AS time_work, upload_status FROM settings_, wards WHERE wards.ward=settings_.ward ORDER BY settings_.id ASC LIMIT 1";
	$row=$pdo->prepare($sql);
	$row->execute();
	if($row_sel=$row->fetch())
	{
		if ($row_sel['upload_status']==1 && $row_sel['blocked']==0)
		{
			$datetime1 = new DateTime($row_sel['date_curr']);
			$datetime2 = new DateTime();
			$interval = $datetime2->getTimestamp()-$datetime1->getTimestamp();
			if (abs($interval)<1800)
			{
				$out_res=array(
					'answer'=>'1',
					'ward'	=> $row_sel['ward_text'],
					'pos'	=> $row_sel['pos'],
					'amount'=> $row_sel['amount_apps'],
					'date_start'=> $row_sel['date_start'],
					'time_work'	=> $row_sel['time_work'],
				);
			}
			else
			{
				$out_res=array(
					'answer'=>'0',
					'ward'	=> '',
					'pos'	=> '',
					'amount'=> '',
					'date_start'=> '',
					'time_work'	=> '',
				);
			}
		}
		else
		{
			$out_res=array(
				'answer'=>'0',
				'ward'	=> '',
				'pos'	=> '',
				'amount'=> '',
				'date_start'=> '',
				'time_work'	=> '',
			);
		}
	}
	else
	{
		$out_res=array('error'=>'1', 'ward' => 'no info');
	}
}
elseif (isset($_REQUEST['stopUpload']))
{
	try
	{
		$sql_stop="UPDATE settings_ SET blocked=1 WHERE id>0";
		$row_stop=$pdo->prepare($sql_stop);
		$row_stop->execute();
		$out_res=array('answer'=>'1');
	}
	catch (PDOException $e)
	{
	    $rows_error=fopen('logs/rows_error', 'a');
	    fwrite($rows_error, date('Y-m-d H:i:s').','.'sql_error:'.$e->getMessage().'\n');
	    fclose($rows_error);
	    $out_res=array('error'=>'1', 'text' => $e->getMessage());
	}
}
elseif (isset($_REQUEST['doUpdDescr']))
{
	try
	{
		$url_descr = PERMITS_PATH.PERMITS_DETAIL;
		$ch_descr = curl_init();
	    curl_setopt($ch_descr,CURLOPT_URL, $url_descr.trim($_REQUEST['doUpdDescr']));
	    curl_setopt($ch_descr, CURLOPT_CUSTOMREQUEST, "GET");
	    curl_setopt($ch_descr, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch_descr, CURLOPT_TIMEOUT, 10);
	    $result_descr = str_replace('\r\n', '',curl_exec($ch_descr));
	    $curl_error=curl_error($ch_descr);
	    curl_close($ch_descr);
	    $app_descr="no_data";
	    if($data_descr = json_decode($result_descr))
	    {
	    	if (isset($data_descr->folderDescription))
	    	{
		       	$app_descr=trim($data_descr->folderDescription);
		    	$sql_descr="UPDATE applications SET app_descr=:app_descr WHERE folderRsn=:folderRsn";
				$row_descr=$pdo->prepare($sql_descr);
				$row_descr->execute(['app_descr' => $app_descr, 'folderRsn' => trim($_REQUEST['doUpdDescr'])]);
			}
        }
        $out_res=array('answer'=>'1', 'description' => $app_descr);
		
	}
	catch (PDOException $e)
	{
	    $rows_error=fopen('logs/rows_error', 'a');
	    fwrite($rows_error, date('Y-m-d H:i:s').','.'sql_error:'.$e->getMessage().'\n');
	    fclose($rows_error);
	    $out_res=array('error'=>'1', 'text' => $e->getMessage());
	}
}
elseif (isset($_REQUEST['doSetNextWard']))
{
	$sql="SELECT min(last_update) as minDate FROM wards";
	$row=$pdo->prepare($sql);
	$row->execute();
	if($row_sel=$row->fetch())
	{
		$datetime1 = new DateTime($row_sel['minDate']);
		$new_date=date("Y-m-d H:i:s", $datetime1->getTimestamp()-60*60);
		$new_date_out=date("Y-m-d", $datetime1->getTimestamp()-60*60);
		try
		{
			$sql_upd="UPDATE wards SET last_update=:new_date WHERE id=:ward";
			$row_upd=$pdo->prepare($sql_upd);
			$row_upd->execute(['new_date' => $new_date, 'ward' => trim($_REQUEST['doSetNextWard'])]);
			$out_res=array('answer'=>'1', 'new_date' => $new_date_out);

		}
		catch (PDOException $e)
		{
		    $rows_error=fopen('logs/rows_error', 'a');
		    fwrite($rows_error, date('Y-m-d H:i:s').','.'Ward_cron, sql_error:'.$e->getMessage().'\n');
		    fclose($rows_error);
		    $out_res=array('error'=>'1', 'text' => $e->getMessage());
		}

	}
}
elseif (isset($_REQUEST['doShowWards']))
{
	$sql="SELECT id, ward_text, last_update, complete_update, last_address FROM wards ORDER BY complete_update, last_update, id";
	$row_sel=$pdo->prepare($sql);
	$row_sel->execute();
	if ($table_res=$row_sel->fetchall())
	{
		foreach ($table_res as $row) {
			$last_update = new DateTime($row['last_update']);
			$last_update_out = date("Y-m-d", $last_update->getTimestamp());
			$wardsTableData[]=array(
				'id'		=> $row['id'],
				'ward_text'	=> $row['ward_text'],
				'last_update'		=> $last_update_out,
				'complete_update'	=> $row['complete_update'],
			);
		}
	}
	$out_res=array('answer'=>'1', 'wardsTableData' => $wardsTableData);
}
elseif (isset($_REQUEST['doShowWardsList']))
{
	$sql="SELECT id, ward, ward_text FROM wards ORDER BY id";
	$row_sel=$pdo->prepare($sql);
	$row_sel->execute();
	if ($table_res=$row_sel->fetchall())
	{
		foreach ($table_res as $row) {
			$wardsTableData[]=array(
				'ward'		=> $row['ward'],
				'ward_text' => $row['ward_text'],
			);
		}
	}
	$out_res=array('answer'=>'1', 'wardsTableData' => $wardsTableData);
}
	header('Content-type: application/json');
	echo json_encode($out_res);
}
else
{
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
	echo "401 Unauthorized";
}

?>