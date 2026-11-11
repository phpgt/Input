<?php
namespace GT\Input;

use GT\Http\ResponseStatusException\ClientError\HttpPayloadTooLarge;

class PostSizeExceededException extends HttpPayloadTooLarge {
	public function __construct(
		public readonly int $limitBytes,
		public readonly int $contentLength,
	) {
		parent::__construct(
			"The submitted form exceeds the server's "
			. $this->formatLimit($limitBytes) . " request limit. "
			. "Please select smaller files and try again."
		);
	}

	private function formatLimit(int $bytes):string {
		$units = ["GB" => 1073741824, "MB" => 1048576, "KB" => 1024];
		foreach($units as $unit => $size) {
			if($bytes >= $size) {
				return round($bytes / $size, 2) . " $unit";
			}
		}

		return "$bytes bytes";
	}
}
