<?php
require_once __DIR__ . '\php-graph-sdk\src\Facebook\autoload.php';
require_once __DIR__ . '\config.php';

function pictureAttachment($fb,$id)
{

	$request = $fb->request('GET', '/'.$id.'/attachments');
	try {
	  $response = $fb->getClient()->sendRequest($request);;
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
	  // When Graph returns an error
	  echo 'Graph returned an error: ' . $e->getMessage();
	  exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
	  // When validation fails or other local issues
	  echo 'Facebook SDK returned an error: ' . $e->getMessage();
	  exit;
	}

	$graphObject = $response->getGraphEdge()->asArray();

	foreach ($graphObject as $value)
	{
		if (($value['type'])=='photo') {
		$coba = $value['media'];
		$coba2 = $coba['image'];
		$coba3 = $coba2['src'];
		return($coba3);
		}
	}
}

function delete_all_between($beginning, $end, $string) 
{
	  $beginningPos = strpos($string, $beginning);
	  $endPos = strpos($string, $end);
	  if ($beginningPos === false || $endPos === false) {
		return $string;
	  }

	  $textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);

	  return str_replace($textToDelete, '', $string);
}

function getFacebookData($limit)
{
	session_start();

	$fb = new Facebook\Facebook([
	  'app_id' => '1103484983038755',
	  'app_secret' => '68e5f47945b1337b66f4012bf8be0a18',
	  'default_graph_version' => 'v2.8',
	]);

	// Sets the default fallback access token so we don't have to pass it to each request
	$fb->setDefaultAccessToken('1103484983038755|V32vVNT20-IqTeAgdBS7Vpq273c');

	$request = $fb->request('GET', '/208476365953052/feed?limit='.$limit);
	try {
	  $response = $fb->getClient()->sendRequest($request);;
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
	  // When Graph returns an error
	  echo 'Graph returned an error: ' . $e->getMessage();
	  exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
	  // When validation fails or other local issues
	  echo 'Facebook SDK returned an error: ' . $e->getMessage();
	  exit;
	}
	
	$graphEdge = $response->getGraphEdge()->asArray();
	usort($graphEdge, function($a, $b) {
	  $ad = ($a['updated_time']);
	  $bd = ($b['updated_time']);

	  if ($ad == $bd) {
		return 0;
	  }

	  return $ad < $bd ? -1 : 1;
	});

	$lostfound = array();
	foreach ($graphEdge as $value)
	{
		if ($value['message'] !== null)
		{
			$message = $value['message'];
			$link='https://www.facebook.com/'.$value['id'];
			$attach=pictureAttachment($fb,$value['id']);
			$info = delete_all_between('[', ']', $message);
			$date = $value['updated_time'];
			$time = $date->format('Y-m-d H:i:s');
			if ((strpos($message, 'LOST') !== false) or (strpos($message, 'menemukan') !== false)){
				$status = 'LOST';
				$lostfound[]= array(
							'Status' => $status,
							'Date' => $time,
							'Information' => $info,
							'Link' => $link,
							'Attachment' => $attach);
			} else
			if ((strpos($message, 'FOUND') !== false) or (strpos($message, 'Found') !== false) or (strpos($message, 'found') !== false) or (strpos($message, 'Ditemukan') !== false) or (strpos($message, 'DITEMUKAN') !== false)) {
				$status = 'FOUND';
				$lostfound[]= array(
							'Status' => $status,
							'Date' => $time,
							'Information' => $info,
							'Link' => $link,
							'Attachment' => $attach);
			} else { $status = 'null';}
		}
	}
	
	
	return $lostfound;
}

function searchData($info,$array)
{
	$result = array();
	foreach ($array as $row) {
		$information = $row['Information'];
		$date = $row['Date'];
		if ((strpos($information, $info) !== false) or (strpos($date, $info) !== false)) {
			$result[] = $row;
		}
	}
	return $result;
}

function combineFbDB($graphEdge)
{
	$con = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("Cannot connect to host");
	mysqli_select_db($con,DB_DB) or die("Cannot connect to db");
	$result = mysqli_query($con,"SELECT * FROM data");
	
	while($row=mysqli_fetch_array($result)){
		$graphEdge[] = $row;
	}
	usort($graphEdge, function($a, $b) {
	  $ad = ($a['Date']);
	  $bd = ($b['Date']);

	  if ($ad == $bd) {
		return 0;
	  }

	  return $ad > $bd ? -1 : 1;
	});
	mysqli_close($con);
	return $graphEdge;
}

function addtosql($status,$info)
{
	$sql = "INSERT INTO data ".
		   "(Status,Information) ".
		   "VALUES ".
		   "('$status','$info')";
	$con = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("Cannot connect to host");
	mysqli_select_db($con,DB_DB) or die("Cannot connect to db");
	$retval = mysqli_query($con,$sql);
	if(! $retval )
	{
	  die('Could not enter data: ');
	}
	$value= 'Data inserted successfully!';
	mysqli_close($con);
	return $value;
}

function deletesql($id)
{
	$sql = "DELETE FROM data ".
		   "WHERE ".
		   "Id='$id'";
	$con = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("Cannot connect to host");
	mysqli_select_db($con,DB_DB) or die("Cannot connect to db");
	$retval = mysqli_query($con,$sql);
	if(! $retval )
	{
	  die('Could not enter data: ');
	}
	$value= 'Data deleted successfully!';
	mysqli_close($con);
	return $value;
}


$verb= $_SERVER['REQUEST_METHOD'];
if ($verb== 'GET'){
	error_reporting (E_ALL ^ E_NOTICE); 
	if (isset($_GET["act"]))
		{
			switch ($_GET["act"])
				{
					case "getAttachment":
						if (isset($_GET["id"])){
							$id=$_GET["id"];
							$fb = new Facebook\Facebook([
							  'app_id' => '1103484983038755',
							  'app_secret' => '68e5f47945b1337b66f4012bf8be0a18',
							  'default_graph_version' => 'v2.8',
							]);

							// Sets the default fallback access token so we don't have to pass it to each request
							$fb->setDefaultAccessToken('1103484983038755|V32vVNT20-IqTeAgdBS7Vpq273c');
							$value = pictureAttachment($fb,$id);
							
						} else {
							$value =  "Please insert id";
						}
					break;
					case "getFbData":
						if (isset($_GET["limit"])){
							$limit = $_GET["limit"];
							$value = getFacebookData($limit);
							
						} else {
							$value =  "Please insert limit";
						}
					break;
					case "searchData":
						if (isset($_GET["search"])){
							if (isset($_GET["limit"])){
								$limit = $_GET["limit"];
								$dataaa = getFacebookData($limit);
								$dataa = combineFbDB($dataaa);
								$value = searchData($_GET["search"],$dataa);
								
							} else {
								$value =  "Please insert limit";
							}
						} else {
							$value =  "Please insert search data";
						}
					break;
					case "combineDB":
						if (isset($_GET["limit"])){
							$limit = $_GET["limit"];
							$dataa = getFacebookData($limit);
							$value = combineFbDB($dataa);
							
						} else {
							$value =  "Please insert limit";
						}
					break;
					case "add":
						if (isset($_GET["status"])){
							if (isset($_GET["info"])){
								$statuss = $_GET["status"];
								$infoo = $_GET["info"];
								$value = addtosql($statuss,$infoo);
							} else {
								$value =  "Please insert info";
							}
						} else {
							$value =  "Please insert status and information";
						}
					break;
					case "delete":
						if (isset($_GET["id"])){
							$idd = $_GET["id"];
							$value = deletesql($idd);
						} else {
							$value =  "Please insert id";
						}
					break;
				}
				exit(json_encode($value));
	}
}

?>
