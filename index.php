<?php
define('fromIndex', 'on');
GLOBAL $config,$_CWD,$pretty_url_mode;
$_CWD=realpath(".");
require_once $_CWD."/config.php";
require_once $_CWD."/functions.php";
$pretty_url_mode=(isset($_GET['pretty']) && $_GET['pretty']==1) ? 1 : 0;
$debug_mode=(isset($config['debug']) && $config['debug']===true);

$protocol=getProtocol();
$_request=getRequest();
if($debug_mode){
	debugMessage("DEBUG MODE ENABLED. ERRORS WILL BE SHOWN. DISABLE THIS IN config.php.");
	debugMessage("Dumping config: ");
	var_dump($config);
	debugMessage("Dumping get variables: ");
	var_dump($_GET);
	debugMessage("Dumping request: ");
	var_dump($_request);
	debugMessage("Dumping SERVER: ");
	var_dump($_SERVER);
}

if($_request=="")$_request=$config['default'];


if($_request=="homepage" && file_exists($_CWD."/homepage.php")){
	include $_CWD."/homepage.php";
	die;
}
if($_request=="install" && file_exists($_CWD."/install.php")){
    $downloadTime = isset($config['download_time']) ? (int)$config['download_time'] : time();
    if(time() - (86400*3) > $downloadTime){
        die("Expired");
    }
	include $_CWD."/install.php";
	die;
}

//Attempt to read cache file
$cache_file_name=$config['api_key']."_"."$_request"."_".$protocol.".html";
$_cache_file=$_CWD."/cache/".$cache_file_name;

if(file_exists($_cache_file)){
	if($debug_mode){
		debugMessage("Found cache file $_cache_file - opening");
	}

	include $_cache_file;
	$_cache_file_creation_unix=filemtime($_cache_file);
	if($_cache_file_creation_unix && (time()-$_cache_file_creation_unix)>(int)$config['cache_time'] && $config['cache_time']!==-1){
		//File is more than a day old (or cache time setting)! Delete so it will update!
		unlink($_cache_file);
	}
	die;
}

//Attempt to get HTML from main server
$_server_url=$config['server_url']."?".http_build_query(array(
	                                                        "request"=>$_request,
	                                                        "api"=>$config['api_key'],
	                                                        "pretty_url"=>$pretty_url_mode
                                                        ));
$_html_contents=xGetContents($_server_url);

if(!empty($_html_contents) && strlen($_html_contents) > 100){
	echo $_html_contents;
	cacheContents($_html_contents,$_cache_file);
	die;
}
else{
	debugMessage("Error getting contents from server url. Redirecting to 404.php");
	include $_CWD."/404.php";
	die;
}