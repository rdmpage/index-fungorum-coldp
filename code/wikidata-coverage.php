<?php

// Get Wikidata coverage for publications in a database, use this to proritise adding
// publications to Wikidata

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

?>
<html>
  <head>
  	<title>Wikidata bibliographic coverage</title>
    <style type="text/css">
	
	body {
        padding: 1em;
        margin: 1em;
        font-family:sans-serif;
    }
    
    td {
    	font-family:sans-serif;
    	padding:4px;
    }
    
    .doi {
    	background-color:#9FC;
    }
    .jstor {
    	background-color:#F93;
    }
    .handle {
    	background-color:#CFF;
    }
    .url {
    	background-color:#9FF;
    } 
    .pdf {
    	background-color:#F60;
    }            
    .biostor {
    	background-color:#FCC;
    } 
    .cinii {
    	background-color:#9C3;
    } 
    .isbn {
    	background-color:#FF9;
    } 

    
    </style>
  </head>
  <body>
  <h1>Wikidata coverage</h1>
  <p>The different identifier types for publications and the number of records
  which have an identifier but no corresponding Wikidata entry.</p>
  
<?php


$identifiers = array('doi', 'jstor', 'handle', 'url', 'pdf', 'biostor', 'cinii', 'isbn');

echo '<h2>Index Fungorum</h2>';

echo '<ul>';
foreach ($identifiers as $identifier)
{
	echo '<li><a href="#' . $identifier . '">' . $identifier . '</li>';		
}	

echo '</ul>';
	
echo '<div>';

foreach ($identifiers as $identifier)
{
	$sql = 'SELECT COUNT(id) AS count, title AS container FROM names WHERE <IDENTIFIER> IS NOT NULL AND title IS NOT NULL AND wikidata IS NULL GROUP BY title ORDER BY count desc;';
	
	$sql = str_replace('<IDENTIFIER>', $identifier, $sql);
	
	$data = do_query($sql);


	echo '<h2>' . '<a name="' . $identifier . '">' . strtoupper($identifier) . '</h2>';


	echo '<table style="width:70%" cellspacing="0" cellpadding="0">';
	echo '<tbody class="' . $identifier . '">';
	echo "\n";
	foreach ($data as $d)
	{
		echo '<tr>';

		echo '<td>' . $d->count . '</td>';
		echo '<td>' . $d->container . '</td>';

		echo '</tr>';

		echo "\n";
	}
	echo '</tbody>';
	echo '</table>';
	echo "\n";
}

echo '</div>';


?>


</body>
</html>
