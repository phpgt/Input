<?php
require dirname(__DIR__, 3) . "/vendor/autoload.php";

header("Content-Type: application/json");
$input = new GT\Input\Input($_GET, $_POST, $_FILES, server: $_SERVER);
try {
	$input->validatePostSize();
	$file = $input->getFile("photo");
	echo json_encode(["post" => $_POST, "files" => $_FILES, "fileSize" => $file?->getSize()]);
}
catch(GT\Input\PostSizeExceededException $exception) {
	http_response_code($exception->getHttpCode());
	echo json_encode([
		"exception" => $exception::class,
		"message" => $exception->getMessage(),
		"limitBytes" => $exception->limitBytes,
		"contentLength" => $exception->contentLength,
		"post" => $_POST,
		"files" => $_FILES,
	]);
}
