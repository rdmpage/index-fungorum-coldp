<?php

// add ISSNs to records that lack them

error_reporting(E_ALL);

$pdo = new PDO('sqlite:../if.db');


//----------------------------------------------------------------------------------------
function do_query($sql)
{
	global $pdo;
	
	$stmt = $pdo->query($sql);

	$data = array();

	while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

		$item = new stdclass;
		
		$keys = array_keys($row);
	
		foreach ($keys as $k)
		{
			if ($row[$k] != '')
			{
				$item->{$k} = $row[$k];
			}
		}
	
		$data[] = $item;
	
	
	}
	
	return $data;	
}


//----------------------------------------------------------------------------------------


$sql = 'SELECT DISTINCT title, issn FROM names WHERE title IS NOT NULL AND issn IS NOT NULL';
  
$data = do_query($sql);
  	
//print_r($data);
  	
foreach ($data as $obj)
{
	//print_r($obj);
	echo 'UPDATE names SET issn="' . $obj->issn . '" WHERE title = "' . str_replace('"', '""', $obj->title) . '" AND issn IS NULL;' . "\n";
}

?>
