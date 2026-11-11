<?php
namespace GT\Input\Test\InputData\Datum;

use GT\Input\InputData\Datum\FailedFileUpload;
use GT\Input\UploadedFileMoveException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FailedFileUploadTest extends TestCase {
	#[DataProvider("uploadErrors")]
	public function testMoveTo_reportsTheUploadFailure(int $error, string $message):void {
		$sut = new FailedFileUpload("photo.jpg", "", 0, "", $error);

		$this->expectException(UploadedFileMoveException::class);
		$this->expectExceptionMessage($message);
		$this->expectExceptionCode($error);
		$sut->moveTo("unused/photo.jpg");
	}

	#[DataProvider("uploadErrors")]
	public function testGetErrorMessage_describesTheOriginalFailure(int $error, string $message):void {
		$sut = new FailedFileUpload("photo.jpg", "", 0, "", $error);

		self::assertSame($error, $sut->getError());
		self::assertSame($message, $sut->getErrorMessage());
	}

	public static function uploadErrors():iterable {
		yield "PHP size limit" => [UPLOAD_ERR_INI_SIZE, "The uploaded file exceeds the upload_max_filesize directive in php.ini."];
		yield "form size limit" => [UPLOAD_ERR_FORM_SIZE, "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form."];
		yield "partial upload" => [UPLOAD_ERR_PARTIAL, "The uploaded file was only partially uploaded."];
		yield "no file" => [UPLOAD_ERR_NO_FILE, "No file was uploaded."];
		yield "no temporary directory" => [UPLOAD_ERR_NO_TMP_DIR, "Missing a temporary folder."];
		yield "cannot write" => [UPLOAD_ERR_CANT_WRITE, "Failed to write file to disk."];
		yield "extension stopped upload" => [UPLOAD_ERR_EXTENSION, "A PHP extension stopped the file upload."];
		yield "unknown error" => [99, "The file upload failed with error code 99."];
	}
}
