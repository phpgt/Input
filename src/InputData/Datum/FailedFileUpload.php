<?php
namespace GT\Input\InputData\Datum;

class FailedFileUpload extends FileUpload {
	protected int $errorCode;

	public function __construct(
		string $originalFilename,
		string $mimeType,
		int $fileSize,
		string $tempFilePath,
		int $errorCode
	) {
		$this->errorCode = $errorCode;

		parent::__construct(
			$originalFilename,
			$mimeType,
			$fileSize,
			$tempFilePath
		);
	}

	/** @inheritDoc */
	public function getError():int {
		return $this->errorCode;
	}

	public function getErrorMessage():string {
		$msg = "";

		switch($this->errorCode) {
		case UPLOAD_ERR_INI_SIZE:
			$msg = "The uploaded file exceeds the "
			. "upload_max_filesize directive in php.ini.";
			break;
		case UPLOAD_ERR_FORM_SIZE:
			$msg = "The uploaded file exceeds the MAX_FILE_SIZE "
			. "directive that was specified in the HTML form.";
			break;
		case UPLOAD_ERR_PARTIAL:
			$msg = "The uploaded file was only partially uploaded.";
			break;
		case UPLOAD_ERR_NO_FILE:
			$msg = "No file was uploaded.";
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$msg = "Missing a temporary folder.";
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$msg = "Failed to write file to disk.";
			break;
		case UPLOAD_ERR_EXTENSION:
			$msg = "A PHP extension stopped the file upload.";
			break;
		}

		return $msg;
	}
}
