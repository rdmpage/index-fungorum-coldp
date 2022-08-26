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

$key_mapping = array
(
'id' 					=> 'ID', // something unique to citations of same reference

//'publishedin'			=> 'citation', // this will vary amoung individual names

'basionymauthorship'	=> 'author',
'combinationauthorship'	=> 'author',

'title' 				=> 'containerTitle',
'volume' 				=> 'volume',
'number' 				=> 'issue',

//'pages' 				=> 'page', // this will vary amoung individual names

'year' 					=> 'issued',

'issn' 					=> 'issn',

'doi'					=> 'doi',


);

$headings = array_unique(array_values($key_mapping));

echo join("\t", $headings) . "\n";


// get distinct ids
$sql = 'SELECT DISTINCT doi FROM names WHERE doi IS NOT NULL 
AND genuspart IN ("Elaphocordyceps", "Tolypocladium")';

$ids = array();

$data = do_query($sql);
foreach ($data as $obj)
{
	$ids[] = $obj->doi;
}

// get each reference in turn
foreach ($ids as $doi)
{
	$sql = 'SELECT * FROM names WHERE doi="' . $doi . '" LIMIT 1';
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
					case 'doi':
						$output->{$key_mapping[$k]} = $v;

						// IPNI CoLDP hack
						$output->ID = md5($v);
						break;					
						
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

}

?>
