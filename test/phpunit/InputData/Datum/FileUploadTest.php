<?php
namespace GT\Input\Test\InputData\Datum;

use GT\Input\InputData\Datum\FileUpload;
use GT\Input\InputData\Datum\FileUploadUnserializeException;
use GT\Input\UploadedFileSecurityException;
use PHPUnit\Framework\TestCase;

class FileUploadTest extends TestCase {
	public function testMoveTo_rejectsAPathThatIsNotAnHttpUpload():void {
		$sut = new FileUpload("photo.jpg", "image/jpeg", 12, "/not-an-upload/photo.jpg");

		$this->expectException(UploadedFileSecurityException::class);
		$sut->moveTo("unused/photo.jpg");
	}

	public function testWakeup_rejectsUnserialisation():void {
		$sut = new FileUpload("example.txt", "text/plain", 12, "/tmp/upload");
		$serialized = serialize($sut);

		self::expectException(FileUploadUnserializeException::class);
		self::expectExceptionMessage(
			"FileUpload cannot be unserialized because uploaded files are only available during the original request."
		);
		unserialize($serialized);
	}
}
