<?php
set_time_limit(0);

$res=exec('upload_data.bat');
$out_res=array('answer'=>'1', 'res'=>$res);
header('Content-type: application/json');
echo json_encode($out_res);
?>