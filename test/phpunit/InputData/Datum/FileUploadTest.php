<?php
namespace GT\Input\Test\InputData\Datum;

use GT\Input\InputData\Datum\FileUpload;
use GT\Input\InputData\Datum\FileUploadUnserializeException;
use PHPUnit\Framework\TestCase;

class FileUploadTest extends TestCase {
	public function testUnserializeThrowsException():void {
		$fileUpload = new FileUpload("example.txt", "text/plain", 12, "/tmp/upload");
		$serialized = serialize($fileUpload);

		self::expectException(FileUploadUnserializeException::class);
		self::expectExceptionMessage(
			"FileUpload cannot be unserialized because uploaded files are only available during the original request."
		);
		unserialize($serialized);
	}
}
