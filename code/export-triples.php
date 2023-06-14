<?php

// export simple triples

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

$page 	= 1000;
$offset = 0;
$done 	= false;

while (!$done)
{
	// Export triples that have a DOI
	$sql = 'SELECT * FROM names WHERE rowid IN (
	  SELECT rowid FROM names WHERE 
	  doi LIKE "10.%"
	   LIMIT ' . $page . ' OFFSET ' . $offset . ');';
/*	   
	$sql = 'SELECT * FROM names WHERE rowid IN (
  SELECT rowid FROM names WHERE doi IS NOT NULL 
  AND year=2022
  LIMIT ' . $page . ' OFFSET ' . $offset . ');';
*/	   
  
  	$data = do_query($sql);
  	
	foreach ($data as $obj)
	{
		// print_r($obj);
		
		$triples = array();
		
		// TaxonName		
		$triple = array();
		$triple[] = '<urn:lsid:indexfungorum.org:names:' . $obj->id . '>';
		$triple[] = '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type>';
		$triple[] = '<http://schema.org/TaxonName>';
		
		$triples[] = $triple;

		$triple = array();
		$triple[] = '<urn:lsid:indexfungorum.org:names:' . $obj->id . '>';
		$triple[] = '<http://schema.org/name>';
		$triple[] = '"' . str_replace('"', '\"', $obj->namecomplete) . '"';
		
		$triples[] = $triple;
		
		// basedOn		
		if (isset($obj->doi))
		{
			$doi = $obj->doi;
			$doi = str_replace('<', '%3C', $doi);
			$doi = str_replace('>', '%3E', $doi);

			$doi = str_replace('[', '%5B', $doi);
			$doi = str_replace(']', '%5D', $doi);
			
			$doi = strtolower($doi);
		
			$triple = array();
			$triple[] = '<urn:lsid:indexfungorum.org:names:' . $obj->id . '>';
			$triple[] = '<http://schema.org/isBasedOn>';
			$triple[] = '<https://doi.org/' . $doi . '>';
			
			$triples[] = $triple;
			
			if (isset($obj->citation_title))
			{
				$triple = array();
				$triple[] = '<https://doi.org/' . $doi . '>';
				$triple[] = '<http://schema.org/name>';
				$triple[] = '"' . str_replace('"', '\"', $obj->citation_title) . '"';
			
				$triples[] = $triple;			
			}
		}
		
		foreach ($triples as $triple)
		{
			echo join(" ", $triple) . " . \n";
		}

		
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
