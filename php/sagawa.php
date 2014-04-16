<?php
/*
 * コンソール上からパパっと配達状況を確認したいがために書いたスクリプト
 * 結果はvar_dumpで吐き出すだけです。
 * 
 * ■使い方
 * php sagawa.php お問い合せ番号(例: 401198325253)
 */
 
define("SAGAWA_REQUEST_URI", "http://k2k.sagawa-exp.co.jp/p/sagawa/web/okurijoinput.jsp");

if(!isset($argv[1])) die("no code");

$requestCode = $argv[1];

//--[1]--//

// POSTに必要なパラメーターだが、動的に変わりそうなので直前に取得

$html = httpGet(SAGAWA_REQUEST_URI);

preg_match('@id="jsf_tree_64" value="([a-zA-Z0-9.=+/-]+)"@', $html, $matches);

if(!isset($matches[1])) die("jsf_tree_64 not found");

$jsf_tree_64 = $matches[1];

preg_match('@id="jsf_state_64" value="([a-zA-Z0-9.=+/-]+)"@', $html, $matches);

if(!isset($matches[1])) die("jsf_state_64 not found");

$jsf_state_64 = $matches[1];

preg_match('@id="jsf_viewid" value="([a-zA-Z0-9.=+/-]+)"@', $html, $matches);

if(!isset($matches[1])) die("jsf_viewid not found");

$jsf_viewid = $matches[1];

//--[1]--//

//--[2]--//

// POST

$data = array(
	"jsf_state_64" => $jsf_state_64,
	"jsf_tree_64" => $jsf_tree_64,
	"jsf_viewid" => $jsf_viewid,
	"main:_link_hidden_" => "",
	"main:correlation" => 1,
	"main:no1" => $requestCode,
	"main:no2" => "",
	"main:no3" => "",
	"main:no4" => "",
	"main:no5" => "",
	"main:no6" => "",
	"main:no7" => "",
	"main:no8" => "",
	"main:no9" => "",
	"main:no10" => "",
	"main:toiStart" => "",
	"main_SUBMIT" => 1
);

$result = httpPost(SAGAWA_REQUEST_URI, $data);

if(!$result) die("request failed");

//--[2]--//

//--[3]--//

// HTMLをパースし、出力

$result = mb_convert_encoding($result, 'cp932', 'HTML-ENTITIES');
$result = mb_convert_encoding($result, 'UTF-8', 'cp932');

$tidy = tidy_parse_string($result, array(), "raw");

// 最新状況
$status = $tidy->html()->child[1]
			->child[0]
			->child[1]
			->child[0]
			->child[1]
			->child[0]
			->child[0]
			->child[4]
			->child[3]
			->child[0]
			->child[0]
			->child[0]
			->child[2]
			->child[0]
			->value;

// 詳細表示 
$details = $tidy->html()->child[1]
			->child[0]
			->child[1]
			->child[0]
			->child[1]
			->child[0]
			->child[0]
			->child[4]
			->child[5]
			->child[0]
			->child[0]
			->child[0]
			->child[0]
			->child[0]
			->child[0]
			->child[1]
			->child[7]
			->child[1]
			->child;


$detail = "";
foreach($details as $value){
	$detail .= $value->value;
}


$result = array(
	"code" => $requestCode,
	"status" => $status,
	"detail" => $detail
);

var_dump($result);

//--[3]--//


// データGET
/**
 * @param string $target_url
 * @param int $timeout_sec
 * @return string
 */
function httpGet($target_url, $timeout_sec = 180) 
{
	$response = '';
	do{
		
		$ch = curl_init();
		$options = array(
			      CURLOPT_USERAGENT => "Mozilla/5.0 (X11; U; FreeBSD amd64; ja-JP; rv:11.0a1) Gecko/20111116 Firefox/11.0a1",
			      CURLOPT_URL => $target_url,
			      CURLOPT_SSL_VERIFYPEER => FALSE,
			      CURLOPT_RETURNTRANSFER => TRUE,
			      CURLOPT_TIMEOUT => $timeout_sec,
			      );

		curl_setopt_array($ch, $options);

		$response = curl_exec($ch);
		curl_close ($ch);
	}while(false);
    return $response;
}

// データPOST
/**
 * @param string $target_url
 * @param array $post_data
 * @param int $timeout_sec
 * @return string
 */
function httpPost($target_url, $post_data, $timeout_sec = 180) 
{
	$response = '';
	do{
		if(!is_array($post_data)) break;
		
		$catData = NULL;
		foreach($post_data as $key => $value){
			$catData .= '&' . urlencode($key) . '=' . urlencode($value);
		}
		list($empty, $postData) = explode('&', $catData, 2);
		
		$ch = curl_init();
		$options = array(
			CURLOPT_USERAGENT => "Mozilla/5.0 (X11; FreeBSD amd64; rv:28.0) Gecko/20100101 Firefox/28.0",
			CURLOPT_URL => $target_url,
			CURLOPT_POST => TRUE,
			CURLOPT_POSTFIELDS => $postData,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_TIMEOUT => $timeout_sec,
			CURLOPT_REFERER => SAGAWA_REQUEST_URI,
		);

		curl_setopt_array($ch, $options);
		$response = curl_exec($ch);
		curl_close ($ch);
	}while(false);
    return $response;
}
