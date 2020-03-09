<?php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', -1);
error_reporting(0);

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Response JSON
 */
function _json($success, $message, $data = [])
{
	$result = [
		'success' => $success,
		'data' => $data,
		'message' => $message
	];

	echo json_encode($result);
	exit();
}

/**
 * Get Page function
 *
 * @param [type] $url
 * @param [type] $accessToken
 * @return void
 */
function getPage(
	$url,
	$accessToken = 'EAAAAZAw4FxQIBAAzZCwVMKAsQtGfGYqJhkz2UvpjYq80SoY6B4QZBFjYfQJmzmseEzkl1xVKhyHW1AVSZCsFHtZC8Vc9Sb2583ZCwIibOED4gbF4P3IzmldZAdkIRZAovOwbpjCciJZAhoaZBZAZAKKTQDSjEoSqZCU8fyaQ9JeFPKgokygZDZD'
) {
	$rs = \Unirest\Request::get(
		$url,
		$headers = array(),
		$parameters = array('access_token' => $accessToken)
	);
	return $rs->body;
}

$json = file_get_contents('php://input');
$data = json_decode($json);
$group_id = isset($data->group_id) && !empty($data->group_id) ? $data->group_id : false;
$access_token =
	isset($data->access_token) && !empty($data->access_token) ? $data->access_token : false;
if ($group_id && $access_token) {
	$url = "https://graph.facebook.com/v6.0/{$group_id}/feed?fields=message,updated_time,shares,likes.limit(0).summary(true),comments.limit(0).summary(true)";
	$rs = getPage($url, $access_token);
	if ($rs->error) {
		_json(false, 'You\'re get error.', $rs->error);
	} else {
		_json(true, 'DONE', $rs);
	}
} else {
	_json(false, 'Missing param [group_id] && [access_token].');
}

?>
