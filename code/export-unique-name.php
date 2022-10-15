<?php

// export names for ColDP using DOI as proxy for refernece identifier

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

$name_key_mapping = array
(
'id' 					=> 'ID',
'basionymid'			=> 'basionymID',
'namecomplete' 			=> 'scientificName',

'authorship' 			=> 'authorship',

'uninomial'				=> 'uninomial',
'genuspart'				=> 'genus',
'specificepithet' 		=> 'specificEpithet',
'infraspecificEpithet' 	=> 'infraspecificEpithet',

'rankstring'			=> 'rank',

'nomenclaturalcode'		=> 'code',

'year'					=> 'publishedInYear',
'pages'					=> 'publishedInPage',

'referenceID'			=> 'referenceID',

);

$name_headings = array_values($name_key_mapping);
$name_headings[] = 'link';

echo join("\t", $name_headings) . "\n";

while (!$done)
{
	$sql = 'SELECT * FROM names WHERE rowid IN (
	  SELECT rowid FROM names WHERE 
	  genuspart IN ("Elaphocordyceps", "Tolypocladium")
	  AND doi IS NOT NULL
	   LIMIT ' . $page . ' OFFSET ' . $offset . ');';
  
    //$sql = 'SELECT * FROM names WHERE id="808857"';
  
  	$data = do_query($sql);
  	
  	// print_r($data);
  	

	foreach ($data as $obj)
	{
		//print_r($obj);
				
		$output = new stdclass;
		
		foreach ($obj as $k => $v)
		{
			if (isset($name_key_mapping[$k]))
			{
				switch ($k)
				{
					case 'id':
						$output->{$name_key_mapping[$k]} = $v;

						// mimic Species Fungorum for CoL+
						$output->link = 'http://www.indexfungorum.org/Names/NamesRecord.asp?RecordID=' . $v;
						break;
						
					case 'referenceID':
						$output->{$name_key_mapping[$k]} = md5($v);
						break;

					default:
						$output->{$name_key_mapping[$k]} = $v;
						break;
				}
			}
		}
		
		// print_r($output);
		
		// translate to ColDP
		$row = array();

		foreach ($name_headings as $k)
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
