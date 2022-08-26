<?php

// export names for ColDP

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


$page 	= 100;
$offset = 0;
$done 	= false;

$key_mapping = array
(
'id' 					=> 'ID',

'publishedin'			=> 'citation',

'basionymauthorship'	=> 'author',
'combinationauthorship'	=> 'author',

'title' 				=> 'containerTitle',
'volume' 				=> 'volume',
'number' 				=> 'issue',
'pages' 				=> 'page',
'year' 					=> 'issued',

'issn' 					=> 'issn',

'doi'					=> 'doi',


);

$headings = array_unique(array_values($key_mapping));

echo join("\t", $headings) . "\n";

while (!$done)
{
	$sql = 'SELECT * FROM names WHERE rowid IN (
	  SELECT rowid FROM names WHERE 
	  genuspart="Tolypocladium"
	   LIMIT ' . $page . ' OFFSET ' . $offset . ');';
  
    //$sql = 'SELECT * FROM names WHERE id="808857"';
  
  	$data = do_query($sql);
  	
  	// print_r($data);
  	
	foreach ($data as $obj)
	{
		// print_r($obj);
				
		$output = new stdclass;
		
		foreach ($obj as $k => $v)
		{
			if (isset($key_mapping[$k]))
			{
				switch ($k)
				{				
					case 'basionymauthorship':
					case 'combinationauthorship':
						$output->author = $v;
						break;

					default:
						$output->{$key_mapping[$k]} = $v;
						break;
				}
			}
		}
		
		// print_r($output);
		
		// translate to ColDP
		$row = array();

		foreach ($headings as $k)
		{
			if (isset($output->{$k}))
			{
				$row[] = $output->{$k};
			}
			else
			{
				$row[] = '';
			}
		}
		
		echo join("\t", $row) . "\n";
		
	}

	if (count($data) < $page)
	{
		$done = true;
	}
	else
	{
		$offset += $page;
		//if ($offset > 5) { $done = true; }
	}
	

}

?>
