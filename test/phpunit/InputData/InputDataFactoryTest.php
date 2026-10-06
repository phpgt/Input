<?php
namespace GT\Input\Test\InputData;

use GT\Input\Input;
use GT\Input\InputData\InputDataFactory;
use GT\Input\WithWithoutClashingException;
use PHPUnit\Framework\TestCase;

class InputDataFactoryTest extends TestCase {
	public function testCreateNoCriteria(): void {
		$input = $this->createInput();
		$inputDataFactory = new InputDataFactory();
		$data = $inputDataFactory->create($input);
		self::assertSame("Edward", (string)$data["name"]);
		self::assertSame("51", (string)$data["age"]);
		self::assertSame("01234 567890", (string)$data["telephone"]);
		self::assertSame("AB12 3CD", (string)$data["postcode"]);
	}

	public function testCreateCriteriaWith(): void {
		$input = $this->createInput();
		$inputDataFactory = new InputDataFactory();
		$data = $inputDataFactory->create($input, ["name", "postcode"]);
		self::assertSame("Edward", (string)$data["name"]);
		self::assertNull($data["age"]);
		self::assertNull($data["telephone"]);
		self::assertSame("AB12 3CD", (string)$data["postcode"]);
	}

	public function testCreateCriteriaWithout(): void {
		$input = $this->createInput();
		$inputDataFactory = new InputDataFactory();
		$data = $inputDataFactory->create($input, [], ["age", "telephone"]);
		self::assertSame("Edward", (string)$data["name"]);
		self::assertNull($data["age"]);
		self::assertNull($data["telephone"]);
		self::assertSame("AB12 3CD", (string)$data["postcode"]);
	}

	public function testCreateCriteriaWithWithoutNoClash(): void {
		$input = $this->createInput();
		$inputDataFactory = new InputDataFactory();
		$data = $inputDataFactory->create($input, ["name"], ["postcode"]);
		self::assertSame("Edward", (string)$data["name"]);
		self::assertNull($data["age"]);
		self::assertNull($data["telephone"]);
		self::assertNull($data["postcode"]);
	}

	public function testCreateCriteriaWithWithoutClash(): void {
		$input = $this->createInput();
		self::expectException(WithWithoutClashingException::class);
		$inputDataFactory = new InputDataFactory();
		$inputDataFactory->create($input, ["name", "age"], ["age"]);
	}

	protected function createInput(): Input {
		return new Input([
		"name" => "Edward",
		"age" => 51,
		"telephone" => "01234 567890",
		"postcode" => "AB12 3CD",
		]);
	}
}
