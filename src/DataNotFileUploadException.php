<?php
namespace GT\Input;

use Throwable;

class DataNotFileUploadException extends InputException {
	public function __construct(
		string $message = "",
		int $code = 0,
		?Throwable $previous = null,
	) {
		parent::__construct(
			"Key \"$message\" is not a FileUpload - "
			."does your form have the enctype=\"multipart/form-data\" attribute?",
			$code,
			$previous
		);
	}
}
