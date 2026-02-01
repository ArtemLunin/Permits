<?php
set_time_limit(0);

$date_start = '"' . $_REQUEST['dateStart'] . '"' ?? '';
$date_end = '"' . $_REQUEST['dateEnd'] . '"' ?? '';

if ($date_start == '') {
    $date_end = '';
}
session_start();
$userAgent = $_SESSION['User-Agent'] . PHP_EOL ?? 'undefined';
session_write_close();

// $res = exec('upload_data.bat ' . $date_start . ' '. $date_end . ' ' . '"' . $userAgent . '"');
pclose(popen('start "" /MIN c:\xampp\php\php.exe upload_data.php ' . $date_start . ' '. $date_end . ' ' . '"' . $userAgent . '"', 'r'));
$out_res = [
    'answer'    => '1',
    'dates'     => [
        $date_start,
        $date_end
    ],
    'res'       => true,
    'user-agent'    => $userAgent,
];
header('Content-type: application/json');
echo json_encode($out_res);
?>