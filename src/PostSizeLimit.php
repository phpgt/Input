<?php
namespace GT\Input;

readonly class PostSizeLimit {
	/** @param array<string, string> $server */
	public function __construct(
		private array $server,
		private string $requestMethod = "GET",
	) {}

	public function validate():void {
		$method = $this->server["REQUEST_METHOD"] ?? $this->requestMethod;
		if(strtoupper($method) !== "POST" || !ini_get("enable_post_data_reading")) {
			return;
		}

		$contentType = explode(";", $this->server["CONTENT_TYPE"] ?? "")[0];
		if(!in_array(strtolower(trim($contentType)), [
			"multipart/form-data", "application/x-www-form-urlencoded",
		], true)) {
			return;
		}

		$contentLength = $this->server["CONTENT_LENGTH"] ?? "";
		if(!preg_match('/^[0-9]+$/D', $contentLength)) {
			return;
		}

		$limit = ini_parse_quantity(ini_get("post_max_size") ?: "0");
		if($limit > 0 && (int)$contentLength > $limit) {
			throw new PostSizeExceededException($limit, (int)$contentLength);
		}
	}
}
