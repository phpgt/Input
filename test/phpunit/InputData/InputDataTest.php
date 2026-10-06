<?php

namespace GT\Input\Test\InputData;

use GT\Input\InputData\InputData;
use PHPUnit\Framework\TestCase;

class InputDataTest extends TestCase {
	public function testSingleSource():void {
		$data = new InputData([
			"name" => "Alice",
			"gender" => "f",
		]);
		self::assertSame("Alice", (string)$data["name"]);
		self::assertSame("f", (string)$data["gender"]);
	}

	public function testMultipleSources():void {
		$data = new InputData([
			"name" => "Alice",
			"gender" => "f",
		], [
			"do" => "save",
			"userAccess" => "admin",
		]);

		self::assertSame("Alice", (string)$data["name"]);
		self::assertSame("f", (string)$data["gender"]);
		self::assertSame("save", (string)$data["do"]);
		self::assertSame("admin", (string)$data["userAccess"]);
	}

	public function testAddFromEmpty():void {
		$data = new InputData();
		$data->addKeyValue("name", "Bob");
		self::assertSame("Bob", (string)$data["name"]);
	}

	public function testAddMultipleFromEmpty():void {
		$data = new InputData();
		$data->addKeyValue("name", "Bob");
		$data->addKeyValue("gender", "m");
		self::assertSame("Bob", (string)$data["name"]);
		self::assertSame("m", (string)$data["gender"]);
	}

	public function testAddFromExisting():void {
		$data = new InputData([
			"name" => "Charles",
			"gender" => "m",
		], [
			"do" => "save",
			"userAccess" => "admin",
		]);

		$data->addKeyValue("view", "tab1");
		$data->addKeyValue("screenSize", "large");

		self::assertSame("Charles", (string)$data["name"]);
		self::assertSame("m", (string)$data["gender"]);
		self::assertSame("save", (string)$data["do"]);
		self::assertSame("admin", (string)$data["userAccess"]);
		self::assertSame("tab1", (string)$data["view"]);
		self::assertSame("large", (string)$data["screenSize"]);
	}

	public function testRemove():void {
		$data = new InputData([
			"name" => "Debbie",
			"gender" => "f",
		]);

		self::assertSame("f", (string)$data["gender"]);
		$data->remove("gender");
		self::assertNull($data["gender"]);
	}

	public function testRemoveMany():void {
		$data = new InputData([
			"name" => "Eddie",
			"gender" => "m",
			"userAccess" => "admin",
		]);
		$data->remove("gender", "name");
		self::assertNull($data["name"]);
		self::assertNull($data["gender"]);
		self::assertNotNull($data["userAccess"]);
	}

	public function testRemoveExceptSingle():void {
		$data = new InputData([
			"name" => "Freddie",
			"gender" => "f",
			"userAccess" => "sales",
		]);
		$data->removeExcept("userAccess");
		self::assertNull($data["name"]);
		self::assertNull($data["gender"]);
		self::assertSame("sales", (string)$data["userAccess"]);
	}

	public function testRemoveExceptMulti():void {
		$data = new InputData([
			"name" => "Emma",
			"gender" => "f",
			"userAccess" => "sales",
		]);
		$data->removeExcept("name", "userAccess");
		self::assertSame("Emma", (string)$data["name"]);
		self::assertSame("sales", (string)$data["userAccess"]);
		self::assertNull($data["gender"]);
	}

	public function testIsset():void {
		$data = new InputData([
			"name" => "Gordon",
			"gender" => "m",
			"userAccess" => "support",
		]);
		self::assertTrue(isset($data["name"]));
		self::assertTrue(isset($data["gender"]));
		self::assertFalse(isset($data["creditCard"]));
	}

	public function testSetAsArray():void {
		$data = new InputData([
			"name" => "Hannah",
			"gender" => "f",
		]);
		$data["userAccess"] = "test";
		self::assertSame("Hannah", (string)$data["name"]);
		self::assertSame("f", (string)$data["gender"]);
		self::assertSame("test", (string)$data["userAccess"]);
	}

	public function testUnsetAsArray():void {
		$data = new InputData([
			"name" => "Ian",
			"gender" => "m",
		]);
		self::assertTrue(isset($data["gender"]));
		unset($data["gender"]);
		self::assertFalse(isset($data["gender"]));
		self::assertSame("Ian", (string)$data["name"]);
	}

	public function testHas():void {
		$data = new InputData([
			"name" => "James",
			"gender" => "m",
		]);
		self::assertTrue($data->contains("name"));
		self::assertTrue($data->contains("gender"));
		self::assertFalse($data->contains("telephone"));
	}

	public function testHasValue():void {
		$data = new InputData([
			"name" => "Kelly",
			"gender" => "f",
			"telephone" => "",
		]);
		self::assertTrue($data->hasValue("name"));
		self::assertTrue($data->hasValue("gender"));
		self::assertFalse($data->hasValue("telephone"));
		self::assertFalse($data->hasValue("email"));
	}

	public function testToArray():void {
		$sourceData = [
			"name" => "Lisa",
			"gender" => "f",
			"telephone" => "07812457890",
		];

		$data = new InputData($sourceData);
		$array = $data->asArray();

		foreach($array as $key => $value) {
			self::assertEquals($data[$key], $value);
		}
	}

	public function testToArrayWithMultipleInputDatum():void {
		$sourceData = [
			"name" => "Mark",
			"gender" => "m",
			"pastOrderIds" => [
				123,
				456,
				789,
			]
		];

		$data = new InputData($sourceData);
		$array = $data->asArray();

		foreach($array as $key => $value) {
			self::assertEquals($sourceData[$key], $value);
			if(is_array($sourceData[$key])) {
				self::assertIsArray($value);
			}
			else {
				self::assertIsString($value);
			}
		}
	}
}
