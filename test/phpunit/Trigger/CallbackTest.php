<?php
namespace GT\Input\Test\Trigger;

use GT\Input\InputData\InputData;
use GT\Input\Trigger\Callback;
use PHPUnit\Framework\TestCase;

class CallbackTest extends TestCase {
	public function testCallNoArgs() {
		$callbackArgs = null;

		$callback = new Callback(function(...$args) use(&$callbackArgs) {
			$callbackArgs = $args;
		});
		$inputData = self::createMock(InputData::class);
		$callback->call($inputData);

		self::assertCount(1, $callbackArgs);
		self::assertInstanceOf(InputData::class, $callbackArgs[0]);
	}

	public function testCallWithArgs() {
		$callbackArgs = null;
		$firstParam = "one";
		$secondParam = "two";
		$thirdParam = "three";

		$callback = new Callback(function(...$args) use (&$callbackArgs) {
			$callbackArgs = $args;
		}, $firstParam, $secondParam, $thirdParam);
		$inputData = self::createMock(InputData::class);
		$callback->call($inputData);

		self::assertCount(4, $callbackArgs);
		self::assertEquals($firstParam, $callbackArgs[1]);
		self::assertEquals($secondParam, $callbackArgs[2]);
		self::assertEquals($thirdParam, $callbackArgs[3]);
	}
}
