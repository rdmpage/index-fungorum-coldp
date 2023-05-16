<?php

// Add citation titles for data output



require_once(dirname(__FILE__) . '/sqlite.php');
require_once(dirname(__FILE__) . '/nameparse.php');

require_once (dirname(dirname(__FILE__)) . '/vendor/autoload.php');

use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;

$pdo 		= new PDO('sqlite:../if.db');    // name of SQLite database (a file on disk)


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
function get_title($csl)
{
	$title = '';
	
	if (isset($csl->title))
	{
		if (is_array($csl->title))
		{
			if (count($csl->title) > 0)
			{
				$title = $csl->title[0];
			}
		}
		else
		{	
			$title = $csl->title;
		}
		
		if ($title != '')
		{
			$title = strip_tags($title);
			$title = preg_replace('/\R/u', ' ', $title);
			$title = preg_replace('/\s\s+/', ' ', $title);
		}
	}
	
	return $title;	
}

//----------------------------------------------------------------------------------------
function format_csl($csl)
{
	// fix
	if (!isset($csl->type))
	{
		$csl->type = 'article-journal';
	}
	else
	{
		if ($csl->type == 'journal-article')
		{
			$csl->type = 'article-journal';
		}
	}
	
	// citeproc doesn't handle authors if they are only literals :(
	if (isset($csl->author))
	{
		foreach ($csl->author as &$author)
		{
			if (!isset($author->family))
			{
				if (isset($author->literal))
				{
					$parts = parse_name($author->literal);
				
					if (isset($parts['last']))
					{
						$author->family = $parts['last'];
					}
				
					if (isset($parts['suffix']))
					{
						$author->suffix = $parts['suffix'];
					}
				
					if (isset($parts['first']))
					{
						$author->given = $parts['first'];
					
						if (array_key_exists('middle', $parts))
						{
							$author->given .= ' ' . $parts['middle'];
						}
					}
					
					if (!isset($author->given) || !isset($author->family))
					{
						if (isset($author->given))
						{
							unset($author->given);
						}
						if (isset($author->family))
						{
							unset($author->family);
						}
						
					}			
				}
			}
		}
	}

	
	$style_sheet = StyleSheet::loadStyleSheet('apa');
	$citeProc = new CiteProc($style_sheet);
	$citation = $citeProc->render(array($csl), "bibliography");
	$citation = strip_tags($citation);
	$citation = trim(html_entity_decode($citation, ENT_QUOTES | ENT_HTML5, 'UTF-8'));	
	
	return $citation;
}

//----------------------------------------------------------------------------------------

$mode = 3;

if ($mode == 0)
{
	// CSL from MySQL
	$sql = 'SELECT DISTINCT doi AS guid FROM `names` WHERE doi IS NOT NULL AND title="IMA Fungus" AND citation_title IS NULL LIMIT 10'; 
	
	$sql = 'SELECT DISTINCT doi AS guid FROM `names` 
	WHERE doi IS NOT NULL AND citation_title IS NULL'; 
	
}

if ($mode == 1)
{
	// CSL from SQLite
	$sql = 'SELECT DISTINCT doi AS guid FROM `names` 
	WHERE doi IS NOT NULL AND citation_title IS NULL'; 
}

if ($mode == 2)
{
	// DOI content negotiation
	$sql = 'SELECT DISTINCT doi FROM `names` 
	WHERE doi IS NOT NULL AND citation_title IS NULL'; 
	
	//$sql = 'SELECT DISTINCT doi FROM names WHERE doi="10.1023/A:1002453628455"';
}

if ($mode == 3)
{
	$sql = 'SELECT DISTINCT wikidata FROM `names` WHERE wikidata IS NOT NULL AND citation_title IS NULL'; 
}


$data = do_query($pdo, $sql);

//print_r($data);

foreach ($data as $obj)
{
	switch ($mode)
	{
		case 0:
			// CSL from MySQL
			if (isset($obj->guid))
			{
				echo "-- " . $obj->guid . "\n";				
				$url = 'http://localhost/old/microcitation/www/citeproc-api.php?guid=' . urlencode($obj->guid);
				
				$json = get($url);
				
				$doc = json_decode($json);
				
				if ($doc)
				{					
					$title = '';
					
					if (is_array($doc))
					{
						$title = get_title($doc[0]);		
					}					
					else
					{
						$title = get_title($doc);		
					}
					if ($title != '')
					{
						echo 'UPDATE names SET citation_title = "' . str_replace('"', '""', trim($title)) . '" WHERE doi="' . $obj->guid . '";' . "\n";				
					}	
					
					$citation = '';
					
					if (is_array($doc))
					{
						$citation = format_csl($doc[0]);		
					}					
					else
					{
						$citation = format_csl($doc);		
					}
					if ($citation != '')
					{
						// echo $citation . "\n";						
						echo 'UPDATE names SET citation = "' . str_replace('"', '""', trim($citation)) . '" WHERE doi="' . $obj->guid . '";' . "\n";				
					}
								
				}
			}
			break;
		
		case 1:
			// CSL from SQLite, clumsy but fast, need to change what guid we use, and which table we query
			if (isset($obj->guid))
			{
				echo "-- " . $obj->guid . "\n";
				$url = 'http://localhost/microcitation-lite/api/api.php?id=' . urlencode($obj->guid);
				
				$json = get($url);
				
				// echo $json. "\n";
				
				$doc = json_decode($json);
				
				if ($doc)
				{					
					$title = '';
					
					if (is_array($doc))
					{
						$title = get_title($doc[0]);		
					}					
					else
					{
						$title = get_title($doc);		
					}
					if ($title != '')
					{
						echo 'UPDATE names SET citation_title = "' . str_replace('"', '""', trim($title)) . '" WHERE doi="' . $obj->guid . '";' . "\n";				
					}	
					
					$citation = '';
					
					if (is_array($doc))
					{
						$citation = format_csl($doc[0]);		
					}					
					else
					{
						$citation = format_csl($doc);		
					}
					if ($citation != '')
					{
						// echo $citation . "\n";						
						echo 'UPDATE names SET citation = "' . str_replace('"', '""', trim($citation)) . '" WHERE doi="' . $obj->guid . '";' . "\n";				
					}
								
				}
			}
			break;
		
		// CSL from DOI resolution
		case 2:		
			if (isset($obj->doi))
			{
				echo "-- " . $obj->doi . "\n";

				$doc = new stdclass;
				$doc->IDENTIFIER = $obj->doi;
		
				$url = 'http://localhost/citation-matching/api/csl.php';
	
				$json = post($url, json_encode($doc));
	
				//echo $json . "\n";
	
				$doc = json_decode($json);
	
				if ($doc)
				{
					$title = get_title($doc);					
					if ($title != '')
					{
						echo 'UPDATE names SET citation_title = "' . str_replace('"', '""', trim($title)) . '" WHERE doi="' . $obj->doi . '";' . "\n";				
					}	
					
					$citation = format_csl($doc);		
					if ($citation != '')
					{
						// echo $citation . "\n";						
						echo 'UPDATE names SET citation = "' . str_replace('"', '""', trim($citation)) . '" WHERE doi="' . $obj->doi . '";' . "\n";				
					}
								
				}
			}
			break;
			
		// CSL from WIKIDATA
		case 3:		
			if (isset($obj->wikidata))
			{
				echo "-- " . $obj->wikidata . "\n";
						
				$doc = new stdclass;
				$doc->IDENTIFIER = $obj->wikidata;
		
				$url = 'http://localhost/citation-matching/api/csl.php';
	
				$json = post($url, json_encode($doc));
	
				//echo $json . "\n";
	
				$doc = json_decode($json);
				
				if ($doc)
				{
					$title = get_title($doc);					
					if ($title != '')
					{
						echo 'UPDATE names SET citation_title = "' . str_replace('"', '""', trim($title)) . '" WHERE wikidata="' . $obj->wikidata . '";' . "\n";				
					}				
					$citation = format_csl($doc);		
					if ($citation != '')
					{
						// echo $citation . "\n";						
						echo 'UPDATE names SET citation = "' . str_replace('"', '""', trim($citation)) . '" WHERE wikidata="' . $obj->wikidata . '";' . "\n";				
					}
				}
			}
			break;	
	
		default:
			break;
	}


	// print_r($obj);
	
	
}

?>

