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
function get($url)
{
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	
	return $data;
			
}	

//----------------------------------------------------------------------------------------

//$sql = 'SELECT * FROM names WHERE id=17814';

$sql = 'SELECT * FROM names WHERE issn="0007-2745"';


$sql .= ' AND volume IS NOT NULL';
$sql .= ' AND pages IS NOT NULL ';
$sql .= ' AND doi is NULL';
//$sql .= ' AND jstor is NULL';

$include_authors = true; // more accuracy
$include_authors = false;

$include_issue= true;
$include_issue = false;


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
	}
	
	$doc->volume = $data->volume;
	
	if (isset($data->number))
	{
		$doc->issue = $data->number;
	}	
	
	$doc->page = $data->pages;
	
	if (isset($data->year))
	{
		$doc->issued = new stdclass;
		$doc->issued->{'date-parts'} = array();
		$doc->issued->{'date-parts'}[0][0] = (Int)$data->year;
	}
	
	// add author
	if (isset($data->authorship) && $include_authors)
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
		
		$literal = preg_replace('/\.$/', '', $literal);
		
		//echo $literal . "\n";
		
		$literal = trim($literal);
		
		$author = new stdclass;
		$author->literal = $literal;
		$doc->author = array($author);
			
	}
	
	
	//print_r($doc);
	
	$parameters = array();
	
	$keys = array('id', 'container-title', 'ISSN', 'collection-title', 'volume', 'issue', 'page', 'author');
	foreach ($keys as $k)
	{
		if (isset($doc->{$k}))
		{
			switch ($k)
			{
				case 'container-title':
					break;
			
				case 'ISSN':
					$parameters['issn'] = $doc->ISSN[0];
					break;

				case 'collection-title':
					$parameters['series'] = $doc->{$k};
					break;

				case 'volume':
					$parameters['volume'] = $doc->{$k};
					break;
					
				case 'issue':
					if ($include_issue)
					{
						$parameters['issue'] = $doc->{$k};
					}
					break;											

				case 'page':
					$parameters['page'] = $doc->{$k};
					break;
					
				case 'author':
					if (isset($doc->{$k}[0]->literal))
					{
						$parameters['authors'] = $doc->{$k}[0]->literal;
					}
					break;				
						
				default:
					break;
			}
		}
	}
	
	// print_r($parameters);

	$url = 'http://localhost/old/microcitation/www/index.php?' . http_build_query($parameters);
	
	$json = get($url);
	
	//echo $json . "\n";
		
	$obj = json_decode($json);
	
	$obj = json_decode($json);

	//print_r($obj);

	if (isset($obj->found) && $obj->found)
	{
		if (count($obj->results) == 1)
		{	
			if (isset($obj->results[0]->doi))
			{
				$sql = 'UPDATE names SET doi="' . $obj->results[0]->doi . '" WHERE id=' . $doc->id . ';';
			
				echo $sql . "\n";			
			}
			
			if (isset($obj->results[0]->jstor))
			{
				$sql = 'UPDATE names SET jstor=' . $obj->results[0]->jstor . ' WHERE id=' . $doc->id . ';';
			
				echo $sql . "\n";			
			}

			if (isset($obj->results[0]->url))
			{
				$sql = 'UPDATE names SET url="' . $obj->results[0]->url . '" WHERE id=' . $doc->id . ';';
			
				echo $sql . "\n";			
			}
			
			
			if (isset($obj->results[0]->wikidata))
			{
				$sql = 'UPDATE names SET wikidata="' . $obj->results[0]->wikidata . '" WHERE id=' . $doc->id . ';';
			
				echo $sql . "\n";			
			}
			

		}
	}
	
}

?>
