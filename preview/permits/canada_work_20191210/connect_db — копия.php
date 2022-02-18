<?php
	$host = 'localhost'; 
	$database = 'permits'; 
	$user = 'viewer'; 
	$password = 'Viewer1@'; 
	try
	{
		$pdo=new PDO('mysql:host='.$host.';dbname='.$database.";charset=UTF8",$user,$password,[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
	}
	catch (PDOException $e){
		echo "errro connect to database:".$e;
		exit;
	}
?>
