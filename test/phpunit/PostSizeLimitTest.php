<?php
namespace GT\Input\Test;

use GT\Input\PostSizeLimit;
use PHPUnit\Framework\TestCase;

class PostSizeLimitTest extends TestCase {
	public function testValidate_rejectsOversizedFormsFromRequestMetadata():void {
		$limit = ini_parse_quantity(ini_get("post_max_size"));
		$sut = new PostSizeLimit(server: [
			"REQUEST_METHOD" => "POST",
			"CONTENT_TYPE" => "multipart/form-data; boundary=test",
			"CONTENT_LENGTH" => (string)($limit + 1),
		]);

		$this->expectException(\GT\Input\PostSizeExceededException::class);
		$sut->validate();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider("acceptedPostMetadata")]
	public function testValidate_doesNotMistakeEmptyDataForRejectedUploads(array $server):void {
		$sut = new PostSizeLimit(server: $server);

		$sut->validate();

		$this->expectNotToPerformAssertions();
	}

	public static function acceptedPostMetadata():iterable {
		$limit = ini_parse_quantity(ini_get("post_max_size"));
		$base = ["REQUEST_METHOD" => "POST", "CONTENT_TYPE" => "multipart/form-data"];
		yield "empty request" => [[]];
		yield "at the limit" => [$base + ["CONTENT_LENGTH" => (string)$limit]];
		yield "unknown length" => [$base];
		yield "invalid length" => [$base + ["CONTENT_LENGTH" => "invalid"]];
		yield "JSON body" => [["REQUEST_METHOD" => "POST", "CONTENT_TYPE" => "application/json", "CONTENT_LENGTH" => (string)($limit + 1)]];
		yield "PUT upload" => [["REQUEST_METHOD" => "PUT", "CONTENT_TYPE" => "multipart/form-data", "CONTENT_LENGTH" => (string)($limit + 1)]];
	}

}
