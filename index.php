<?php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', -1);
error_reporting(0);

require_once __DIR__ . '/vendor/autoload.php';

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

$GROUPS_URL = 'https://www.facebook.com/groups';
$group_id = isset($_POST['group_id']) && !empty($_POST['group_id']) ? $_POST['group_id'] : false;
$access_token =
	isset($_POST['access_token']) && !empty($_POST['access_token']) ? $_POST['access_token'] : false;
$errorMessage = false;
if ($group_id && $access_token) {
	try {
		$current = "https://graph.facebook.com/v6.0/{$group_id}/feed?fields=message,updated_time,shares,likes.limit(0).summary(true),comments.limit(0).summary(true)";
		$fileName = $group_id . '_' . (new DateTime())->format('Y-m-d-H-i-s') . '.csv';
		$fp = fopen(__DIR__ . '/csv/' . $fileName, 'w');
		$csvHeader = ['Date', 'URL', 'Likes', 'Comments', 'Shares', 'Content'];
		fputcsv($fp, $csvHeader);
		echo 'Downloading ';

		while ($current) {
			$page = getPage($current, $access_token);
			if ($page) {
				$data = $page->data;

				foreach ($data as $post) {
					$fields = array();
					$fields[] = $post->updated_time;
					$fields[] = $GROUPS_URL . '/' . implode('/', explode('_', $post->id));
					$fields[] = $post->likes ? $post->likes->summary->total_count : 0;
					$fields[] = $post->comments ? $post->comments->summary->total_count : 0;
					$fields[] = $post->shares ? $post->shares->count : 0;
					$fields[] = $post->message ? $post->message : '';
					fputcsv($fp, $fields);
				}

				$current = $page->paging->next;
				echo '...';
				ob_flush();
				flush();
				sleep(2);
			} else {
				$current = null;
			}
		}

		fclose($fp);
		echo '<script>window.location = "/index.php"</script>';
		die();
		if ($rs->error) {
			$errorMessage = $rs->error->message;
		} else {
			var_dump($rs);
		}
	} catch (\Exception $ex) {
		$errorMessage = 'Something went wrong.';
	}
}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title>FB Tools</title>

		<link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet" />
		
	</head>
	<body class="font-sans antialiased bg-gray-200">
		<div class="py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
			<div class="max-w-4xl mx-auto">
				<div class="overflow-hidden bg-white shadow sm:rounded-lg">
					<div class="px-4 py-5 border-b border-gray-200 sm:px-6">
						<h3 class="text-lg font-medium leading-6 text-gray-900">
							Group Facebook
						</h3>
						<p class="max-w-2xl mt-1 text-sm leading-5 text-gray-500">
							Scrape posts.
						</p>
					</div>
					<form method="POST">
						<dl>
							<div class="px-4 pt-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
								<dt class="text-sm font-medium leading-5 text-gray-900">
									Group ID
								</dt>
								<input
									class="w-full col-span-2 px-3 py-2 mb-2 leading-5 text-gray-700 border rounded-lg focus:outline-none focus:shadow-outline bg-gray-300 <?= !$group_id
         	? 'border-red-500'
         	: '' ?>"
									type="text"
									name="group_id"
									value="<?= $group_id ? $group_id : '' ?>"
								/>
							</div>

							<div class="px-4 pt-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
								<dt class="text-sm font-medium leading-5 text-gray-900">
									Access Token
								</dt>
								<textarea
									rows="4"
									class="w-full col-span-2 px-3 py-2 mb-2 leading-5 text-gray-700 border rounded-lg focus:outline-none focus:shadow-outline bg-gray-300 <?= !$access_token
         	? 'border-red-500'
         	: '' ?>"
									name="access_token"
								><?= $access_token ? $access_token : '' ?></textarea
								>
							</div>
							<div class="px-4 py-5 sm:px-6">
								<button
									class="w-full px-4 py-2 leading-5 text-white uppercase bg-indigo-700 rounded-lg focus:outline-none focus:shadow-outline hover:bg-indigo-600"
									type="submit"
								>
									Download
								</button>
								<?= $errorMessage ? '<p class="pt-4 text-center text-red-500" ></p>' : '' ?>
							</div>
						</dl>
					</form>
				</div>

				<div class="inline-block min-w-full mt-8 overflow-hidden align-middle border-b border-gray-200 shadow sm:rounded-lg">
						<table class="min-w-full">
							<thead>
								<tr>
									<th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-900 uppercase border-b border-gray-200 bg-gray-50">
										History Files
									</th>
								</tr>
							</thead>
							<tbody class="bg-white">
								<?php
        $files = glob(__DIR__ . '/csv/*.csv');
        if (count($files) > 0) {
        	foreach ($files as $filepath) { ?>
				<tr>
									<td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
										<a class="text-blue-600 underline" href="/csv/<?= basename($filepath) ?>"><?= basename(
	$filepath
) ?></a>
									</td>
								</tr>
									<?php }
        } else {
        	 ?>
					<tr>
									<td class="px-6 py-4 text-center text-gray-600 whitespace-no-wrap border-b border-gray-200">
										Not Yet
									</td>
								</tr>
					<?php
        }
        ?>
							</tbody>
						</table>
					</div>

			</div>
		</div>
	</body>
</html>
