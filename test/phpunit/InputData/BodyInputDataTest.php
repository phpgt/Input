<?php

namespace GT\Input\Test\InputData;

use GT\Input\InputData\BodyInputData;
use GT\Input\InputData\Datum\InputDatum;
use GT\Input\InputData\Datum\MultipleInputDatum;
use GT\Input\Test\Helper\Helper;
use PHPUnit\Framework\TestCase;

class BodyInputDataTest extends TestCase {
	public function testGet_returnsInputDatumOrMultipleInputDatum():void {
		$sut = new BodyInputData(Helper::getPostPizza());
		self::assertInstanceOf(InputDatum::class, $sut->get("name"));
		self::assertInstanceOf(MultipleInputDatum::class, $sut->get("toppings"));
		self::assertNull($sut->get("nothing"));
	}
}
