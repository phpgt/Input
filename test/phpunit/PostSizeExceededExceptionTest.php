<?php
namespace GT\Input\Test;

use GT\Input\PostSizeExceededException;
use GT\Http\ResponseStatusException\ResponseStatusException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PostSizeExceededExceptionTest extends TestCase {
	#[DataProvider("requestLimits")]
	public function testConstruct_providesAReadableLimitAndHttpStatus(int $bytes, string $label):void {
		$sut = new PostSizeExceededException($bytes, $bytes + 1);

		self::assertInstanceOf(ResponseStatusException::class, $sut);
		self::assertSame(413, $sut->getHttpCode());
		self::assertSame($bytes, $sut->limitBytes);
		self::assertSame($bytes + 1, $sut->contentLength);
		self::assertStringContainsString("$label request limit", $sut->getMessage());
		self::assertStringContainsString("Please select smaller files", $sut->getMessage());
	}

	public static function requestLimits():iterable {
		yield [512, "512 bytes"];
		yield [1024, "1 KB"];
		yield [1048576, "1 MB"];
		yield [1572864, "1.5 MB"];
		yield [1073741824, "1 GB"];
	}
}
