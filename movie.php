<?php
require 'head.php';
require_once 'html_dom.php';
if (!isset($_GET['url'])) die();
$url = gethostbyname($server.($_GET['url']));
$objects=[];
$main=[];
//function file_get_html(
	// $url,
	// $use_include_path = false,
	// $context = null,
	// $offset = 0,
	// $maxLen = -1,
	// $lowercase = true,
	// $forceTagsClosed = true,
	// $target_charset = DEFAULT_TARGET_CHARSET,
	// $stripRN = true,
	// $defaultBRText = DEFAULT_BR_TEXT,
	// $defaultSpanText = DEFAULT_SPAN_TEXT)

//a40 + Hearing Impaired a41
if(!isset($_GET['lang'])) $html = file_get_html($url);
else{
	try{
		$LanguageFilter_Cookies=[13,46,2,4,10,11,17,18,22,44,26,30,33,38,39,45,51,34];
		$opts = array('http'=>array('header'=> 'Cookie: '."LanguageFilter=".$LanguageFilter_Cookies[$_GET['lang']]."\r\n"));
		$context = stream_context_create($opts);
		$html = file_get_html($url,false,$context);
		// $html = file_get_html($url,false,"LanguageFilter=".$LanguageFilter_Cookies[$_GET['lang']]);
		$ch = curl_init();
		$headers = array('X-Auth-Email: user@emailaddress.com','X-Auth-Key: d820fa8fc881921323e08a2c19b8347896ac26','Content-Type: application/json');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 60);
		// curl_setopt($ch, CURLOPT_TIMEOUT , 60);
		
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
		curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 2 );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$str = curl_exec($ch);
		// Check for errors and display the error message
		if($errno = curl_errno($ch)) {
			$error_message = curl_strerror($errno);
			echo "cURL error ({$errno}):\n {$error_message}";
		}
		curl_close($ch);
		$html= new simple_html_dom();
		$html->load($str);
	}catch(Exception $ex){
		$html = '';
	}
}
if(strlen($html)<1 || (strpos($html,'404 - Not Found') !== false)) {
	http_response_code(404);
	die();
}
try {
	$v=$html->find('img[alt=Poster]',0);
	if(@$v) $main['ImgSrc']=$v->src;
} catch (Exception $e) {
	$main['ImgSrc']="";
}
try{
	$main['Year']=trim(str_replace("\t", " ", $html->find('div.header>ul>li',0)->plaintext));
	$main['Name']=trim(str_replace("\t", " ", $html->find('div.header>h2',0)->plaintext));
	$imdbPosition=strrpos($main['Name'], 'Imdb');
	if($imdbPosition===false) $imdbPosition=strrpos($main['Name'], 'Flag');
	$main['Name']=substr($main['Name'],0,$imdbPosition);
}catch(Exception $e){die();}
foreach ($html->find('tr') as $k=>$tr) {
	if($k==0 )continue;
	if((strpos($tr->innertext, 'subtitle') == false))continue;
	try{
		$obj['Lang']=trim(str_replace("\t", '',($tr->find('td.a1 span',0)->plaintext)));
		if(isset($lang) && (strripos($lang,$obj['Lang'])=== false)) continue;
	}catch(Exception $e){
		continue;
	}
	$obj["Name"]=trim(str_replace("\t", '',$tr->find('td.a1 span text',1)->plaintext));
	$obj["Url"]=$tr->find('td.a1 a[href]',0)->href;
	$obj['Owner']=trim(str_replace("\t", '',$tr->find('td.a5',0)->plaintext));
	if((strpos($tr->innertext, 'neutral-icon') !== false)) $obj['Rate']="Gray";
	$objects[]=$obj;
} 
echo json_encode([$main,$objects],JSON_UNESCAPED_UNICODE);