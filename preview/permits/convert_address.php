<?php
//2570 BIRCHMOUNT RD - CHRIS JERK DINE IN TAKE OUT
require_once "./connect_db.php";

$sql="SELECT id, folderName FROM applications WHERE inDate>='2019-01-01' ORDER BY id";
$row_sel=$pdo->prepare($sql);

$sql_addr="UPDATE applications SET folderAddress=:folderAddress WHERE id=:id";
$row_addr=$pdo->prepare($sql_addr);

$total_rows=0;
$current_row=0;
try
{
	$pdo->beginTransaction();
	$row_sel->execute();
	if ($table_res=$row_sel->fetchall())
	{
		foreach ($table_res as $row) {
			$total_rows++;
			$folderName=$row['folderName'];
			$folderAddress='';
			if (preg_match('/^(.{10,})\s*-.*$/U', $folderName,$matches))
			{	
				$folderAddress=$matches[1];
			}
			else
			{
				$folderAddress=$folderName;
			}
			$row_addr->execute(['folderAddress' => $folderAddress, 'id' => $row['id']]);
		}
	}
	$pdo->commit();
}
catch (PDOException $e)
{
    echo "row:{$total_rows}: ".$e->getMessage().'\n';
    $$pdo->rollBack();
}
?>