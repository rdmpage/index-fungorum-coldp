<?php

// Convert page records to DOIs
// IF often has DOI stored as page numbers(!)

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



$sql = 'SELECT * FROM names WHERE title="Fungal Diversity"';

$sql .= ' AND pages LIKE "10.1007%"';
$sql .= ' AND doi is NULL';

$debug = true;
$debug = false;


$query_result = do_query($sql);

foreach ($query_result as $data)
{
	//print_r($data);
	
	echo "-- " . $data->pages . "\n";
	
	$doi = '';
	
	if (preg_match('/(10.1007\/[^,]+),\s+\[/', $data->pages, $m))
	{
		$doi = $m[1];		
	}
	
	if ($doi != '')
	{
		
		echo 'UPDATE names SET doi = "' . $doi . '" WHERE id="' . $data->id . '";' . "\n";
	}

}

?>
