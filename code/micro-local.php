<?php

// Match citatuons to DOIs

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
function post($url, $data = '', $content_type = '')
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	if ($content_type != '')
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, 
			array(
				"Content-type: " . $content_type
				)
			);
	}	
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
		
	curl_close($ch);
	
	return $response;
}

//----------------------------------------------------------------------------------------


$sql = 'SELECT * FROM names WHERE issn="0093-4666" and year > 2008';

$sql = 'SELECT * FROM names WHERE id=817479';

//$sql = 'SELECT * FROM names WHERE issn="2346-9641" and volume=51';

$sql = 'SELECT * FROM names WHERE issn="0236-6495"';
$sql = 'SELECT * FROM names WHERE issn="0170-110X"';
$sql = 'SELECT * FROM names WHERE issn="2077-7019"';
$sql = 'SELECT * FROM names WHERE issn="0093-4666"';
$sql = 'SELECT * FROM names WHERE issn="1314-4049"';
$sql = 'SELECT * FROM names WHERE issn="2544-7459"';
//$sql = 'SELECT * FROM names WHERE issn="2657-5000"';

$sql = 'SELECT * FROM names WHERE issn="0511-9618"';
$sql = 'SELECT * FROM names WHERE issn="1672-6472"';

//$sql = "SELECT * FROM names WHERE issn IN ('0181-1584','1314-4049','2309-608X','0003-6072','1179-3155','0166-0616') AND year='2021'";

$sql = 'SELECT * FROM names WHERE issn="1436-2317"';
$sql = 'SELECT * FROM names WHERE issn="1617-416X"';
$sql = 'SELECT * FROM names WHERE issn="2210-6340"';

$sql = 'SELECT * FROM names WHERE issn="0453-3402"';

$sql = 'SELECT * FROM names WHERE issn="1560-3695"';

$sql = 'SELECT * FROM names WHERE issn="0031-5850"';

$sql = 'SELECT * FROM names WHERE issn="0093-4666"';
$sql = 'SELECT * FROM names WHERE issn="2077-7019"';

$sql = 'SELECT * FROM names WHERE issn="1424-2818"';

$sql = 'SELECT * FROM names WHERE issn="1312-3300"';

$sql = 'SELECT * FROM names WHERE issn="0007-1536"';

$sql = 'SELECT * FROM names WHERE issn="1179-3155"'; // Phytotaxa
$sql = 'SELECT * FROM names WHERE issn="0024-2829"'; // Lichenologist

$sql = 'SELECT * FROM names WHERE issn="1878-6146"'; // Fungal Biology

$sql = 'SELECT * FROM names WHERE issn="0031-5850"'; // Persoonia
$sql = 'SELECT * FROM names WHERE issn="0166-0616"'; // Stud. Mycol.

$sql = 'SELECT * FROM names WHERE issn="0815-3191"'; // Australas. Pl. Path.


//$sql = 'SELECT * FROM names WHERE id=510859';

//$sql .= ' AND year=2007';

$sql .= ' AND volume IS NOT NULL';
$sql .= ' AND pages IS NOT NULL ';
$sql .= ' AND doi is NULL';
//$sql .= ' AND wikidata is NULL';
//$sql .= ' AND jstor is NULL';
//$sql .= ' AND url is NULL';

$debug = true;
$debug = false;

$include_authors = true; // more accuracy
//$include_authors = false;

$query_result = do_query($sql);

$rows = array();

foreach ($query_result as $data)
{
	$doc = new stdclass;
	
	$doc->id = $data->id;
	
	$doc->{'container-title'} = $data->title;
		
	if (isset($data->issn))
	{
		$doc->ISSN[] = $data->issn;
		
		// Phytotaxa
		if ($data->issn == '1179-3155')
		{
			$doc->ISSN[0] = '1179-3163';
		}

		if ($data->issn == '1436-2317')
		{
			$doc->ISSN[0] = '2195-9889';
		}

		if ($data->issn == '2210-6340')
		{
			$doc->ISSN[0] = '2210-6359';
		}
		
		
		if ($data->issn == '1560-3695')
		{
			$doc->ISSN[0] = '1013-2732';
		}
		
	}
	
	$doc->volume = $data->volume;
	$doc->page = $data->pages;
		
	if (isset($data->year))
	{
		$doc->issued = new stdclass;
		$doc->issued->{'date-parts'} = array();
		$doc->issued->{'date-parts'}[0][0] = (Int)$data->year;
	}
	
	// add author
	if ($include_authors)
	{
		if (isset($data->authorship))
		{
			$literal = $data->authorship;
	
			$literal = preg_replace('/.*\)\s+/', '', $literal);
		
			//echo $literal . "\n";
		
			// multiple authors, split on "&"
			if (preg_match('/^([^\&]+)\&/', $literal, $m))
			{
				$literal = trim($m[1]);
			}
		
			//echo $literal . "\n";
		
			// split on ","
			if (preg_match('/^([^,]+),/', $literal, $m))
			{
				$literal = trim($m[1]);
			}	
		
			//echo $literal . "\n";
		
			/*		
			if (preg_match('/de Cooman/', $literal, $m))
			{
				$literal = 'Cooman';
			}	
			*/	
		
			if (preg_match('/^([A-Z][a-z]*\.)+\s*(.*)/', $literal, $m))
			{
				$literal = trim($m[2]);
			}	
				
			$literal = preg_replace('/\.$/', '', $literal);
		
			//echo $literal . "\n";
		
			$literal = trim($literal);
		
			$author = new stdclass;
			$author->literal = $literal;
			$doc->author = array($author);
			
		}
	}
	
	$url = 'http://localhost/microcitation-lite/api/micro.php';
	
	$json = post($url, json_encode($doc));
	
	//echo $json;
	
	if ($debug)
	{
		echo $json . "\n";
	}
	
	$doc = json_decode($json);
	
	if ($doc && isset($doc->DOI) && count($doc->DOI) == 1)
	{
		echo 'UPDATE names SET doi = "' . $doc->DOI[0] . '" WHERE id="' . $data->id . '";' . "\n";
	}
	
	if ($doc && isset($doc->WIKIDATA) && count($doc->WIKIDATA) == 1)
	{
		echo 'UPDATE names SET wikidata = "' . $doc->WIKIDATA[0] . '" WHERE id="' . $data->id . '";' . "\n";
	}

	
	if ($doc && isset($doc->URL) && count($doc->URL) == 1)
	{
//		echo 'UPDATE names SET url = "' . $doc->URL[0] . '" WHERE id="' . $data->id . '";' . "\n";
	}
	

	
}

?>
