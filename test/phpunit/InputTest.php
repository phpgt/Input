<?php
namespace Gt\Input\Test;

use DateTime;
use Gt\Input\DataNotCompatibleFormatException;
use Gt\Input\Input;
use Gt\Input\InputData\Datum\FileUpload;
use Gt\Input\InputData\Datum\InputDatum;
use Gt\Input\InputData\InputData;
use Gt\Input\InvalidInputMethodException;
use Gt\Input\MissingInputParameterException;
use Gt\Input\Test\Helper\Helper;
use Gt\Input\Trigger\Trigger;
use Gt\Json\JsonObject;
use Gt\Json\JsonPrimitive\JsonArrayPrimitive;
use Gt\Json\JsonPrimitive\JsonStringPrimitive;
use PHPUnit\Framework\TestCase;

class InputTest extends TestCase {
	const FAKE_DATA = [
		"a-number" => "1234",
		"one-plus-one" => "2",
		"half-of-five" => "2.5",
		"many-dots" => "1.2.3.4",
		"this-is-positive" => "oh yes",
		"a-date-without-timezone" => "2005-08-15T15:52:00",
		"a-date-with-timezone" => "2005-08-15T16:52:00+01:00",
		"a-date-without-timezone-or-seconds" => "2005-08-15T15:52",
		"a-date-in-rss-format" => "Mon, 15 Aug 2005 15:52:00 +0000",
		"empty-value" => "",
	];
	const FAKE_FILE = [
		"exampleFile" => [
			"name" => "Clouds.jpg",
			"type" => "image/jpeg",
			"tmp_name" => "/tmp/phpgt/input/example.tmp",
			"error" => 0,
			"size" => 1234,
		]
	];

	public function tearDown():void {
		Helper::cleanUp();
	}

	public function testBodyStreamContents():void {
		$testMessage = "This is a test message";
		$tmpPath = implode(DIRECTORY_SEPARATOR, [
			sys_get_temp_dir(),
			"phpgt",
			"input",
			"test",
			uniqid(),
		]);
		mkdir(dirname($tmpPath), 0775, true);
		touch($tmpPath);
		$fh = fopen($tmpPath, "r+");
		fwrite($fh, $testMessage);

		$input = new Input([],[],[],$tmpPath);
		$body = $input->getStream();
		self::assertEquals($testMessage, (string)$body);
	}

	/** @dataProvider dataRandomGetPost */
	public function testGetQueryString(array $get, array $post):void {
		$input = new Input($get, $post);

		for($i = 0; $i < 100; $i++) {
			$key = array_rand($get);

			$value = $input->get(
				$key,
				Input::DATA_QUERYSTRING
			);

			self::assertEquals($get[$key], $value);
			self::assertFalse(isset($post[$key]));
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testGetPostField(array $get, array $post):void {
		$input = new Input($get, $post);

		for($i = 0; $i < 100; $i++) {
			$key = array_rand($post);

			$value = $input->get(
				$key,
				Input::DATA_BODY
			);

			self::assertEquals($post[$key], $value);
			self::assertFalse(isset($get[$key]));
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testGetFileFieldSingle(array $get, array $post):void {
		$files = self::FAKE_FILE;
		$input = new Input($get, $post, $files);
		$file = $input->getFile("exampleFile");

		self::assertInstanceOf(
			FileUpload::class,
			$file
		);
		self::assertEquals(
			$files["exampleFile"]["size"],
			$file->getSize()
		);
		self::assertEquals(
			$files["exampleFile"]["type"],
			$file->getMimeType()
		);
		self::assertEquals(
			$files["exampleFile"]["name"],
			$file->getOriginalName()
		);
		self::assertEquals(
			$files["exampleFile"]["tmp_name"],
			$file->getRealPath()
		);
	}

	public function testGetFile_null():void {
		$input = new Input([], [], []);
		$file = $input->getFile("exampleFile");
		self::assertNull($file);
	}

	/** @dataProvider dataRandomGetPost */
	public function testGetInvalidDataType(array $get, array $post):void {
		self::expectException(InvalidInputMethodException::class);
		$input = new Input($get, $post);
		$input->get("test", "WRONG_TYPE");
	}

	/** @dataProvider dataRandomGetPost */
	public function testGetAllInvalidDataType(array $get, array $post):void {
		self::expectException(InvalidInputMethodException::class);
		$input = new Input($get, $post);
		$input->getAll("WRONG_TYPE");
	}

	/** @dataProvider dataRandomGetPost */
	public function testGetAllQueryString(array $get, array $post):void {
		$input = new Input($get, $post);
		$queryString = $input->getAll(Input::DATA_QUERYSTRING);

		foreach($post as $key => $value) {
			self::assertFalse(isset($queryString[$key]));
		}

		foreach($get as $key => $value) {
			self::assertTrue(isset($queryString[$key]));
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testGetAllPostFields(array $get, array $post):void {
		$input = new Input($get, $post);
		$postFields = $input->getAll(Input::DATA_BODY);

		foreach($get as $key => $value) {
			self::assertFalse(isset($postFields[$key]));
		}

		foreach($post as $key => $value) {
			self::assertTrue(isset($postFields[$key]));
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testGetAllFileFields(array $get, array $post):void {
		$files = self::FAKE_FILE;
		$input = new Input($get, $post, $files);
		$postFields = $input->getAll(Input::DATA_FILES);

		foreach($get as $key => $value) {
			self::assertFalse(isset($postFields[$key]));
		}

		foreach($post as $key => $value) {
			self::assertFalse(isset($postFields[$key]));
		}

		foreach($files as $key => $value) {
			self::assertTrue(isset($postFields[$key]));
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testGetAll(array $get, array $post):void {
		$input = new Input($get, $post);
		$combined = $input->getAll();

		foreach($get as $key => $value) {
			self::assertTrue(isset($combined[$key]));
		}
		foreach($post as $key => $value) {
			self::assertTrue(isset($combined[$key]));
		}

		self::assertFalse(isset($combined->thisDoesNotExist));
	}

	/** @dataProvider dataRandomGetPost */
	public function testGetAllMethods(array $get, array $post):void {
		$input = new Input($get, $post);
		$getVariables = $input->getAll(Input::DATA_QUERYSTRING);
		$postVariables = $input->getAll(Input::DATA_BODY);

		foreach($get as $key => $value) {
			self::assertTrue(isset($getVariables[$key]));
			self::assertFalse(isset($postVariables[$key]));
		}
	}

	/** @dataProvider dataRandomString */
	public function testDo(string $doName):void {
		$input = new Input(["do" => $doName]);
		$trigger = $input->do($doName);
		$this->assertInstanceOf(Trigger::class, $trigger);
		self::assertTrue($trigger->fire(), "Triggers should fire");
	}

	/** @dataProvider dataRandomString */
	public function testNotDo(string $doName):void {
		$input = new Input(["do" => "submit"]);
		$trigger = $input->do($doName);
		self::assertFalse($trigger->fire());
	}

	/** @dataProvider dataRandomString */
	public function testWhen(string $whenName):void {
		$whenValue = uniqid("whenValue");

		$input = new Input([
			uniqid("key") => uniqid("value"),
			$whenName => $whenValue,
		]);
		$trigger = $input->when([
			$whenName => $whenValue,
		]);

		self::assertTrue($trigger->fire());
	}

	/** @dataProvider dataRandomString */
	public function testNotWhen(string $whenName):void {
		$whenValue = uniqid("whenValue");

		$input = new Input([
			uniqid("key") => uniqid("value"),
		]);
		$trigger = $input->when([
			$whenName => $whenValue,
		]);

		self::assertFalse($trigger->fire());
	}

	/** @dataProvider dataRandomString */
	public function testWhenKey(string $whenName):void {
		$whenValue = uniqid("whenValue");

		$input = new Input([
			$whenName => $whenValue,
		]);
		$trigger = $input->when($whenName);

		self::assertTrue($trigger->fire());
	}

	/** @dataProvider dataRandomString */
	public function testWhenNotKey(string $whenName):void {
		$whenValue = uniqid("whenValue");

		$input = new Input([
			"$whenName-oh-no" => $whenValue,
		]);
		$trigger = $input->when($whenName);

		self::assertFalse($trigger->fire());
	}

	/** @dataProvider dataRandomString */
	public function testWhenKeySurrounded(string $whenName):void {
		$whenValue = uniqid("whenValue");

		$input = new Input([
			uniqid("whenKey1") => uniqid("whenValue1"),
			$whenName => $whenValue,
			uniqid("whenKey2") => uniqid("whenValue2"),
		]);
		$trigger = $input->when($whenName);

		self::assertTrue($trigger->fire());
	}

	/** @dataProvider dataRandomString */
	public function testWhenKeyMultiple(string $whenName):void {
		$whenName2 = "$whenName-2";
		$whenValue = uniqid("whenValue");
		$whenValue2 = "$whenValue-2";

		$input = new Input([
			$whenName => $whenValue,
			$whenName2 => $whenValue2,
			uniqid("whenName3") => uniqid("whenValue3"),
		]);
		$trigger = $input->when($whenName, $whenName2);

		self::assertTrue($trigger->fire());
	}

	/** @dataProvider dataRandomString */
	public function testWhenKeyMultipleMissing(string $whenName):void {
		$whenName2 = "$whenName-2";
		$whenValue = uniqid("whenValue");
		$whenValue2 = "$whenValue-2";

		$input = new Input([
			$whenName => $whenValue,
			"$whenName2-oh-no" => $whenValue2,
			uniqid("whenName3") => uniqid("whenValue3"),
		]);
		$trigger = $input->when($whenName, $whenName2);

		self::assertFalse($trigger->fire());
	}

	/** @dataProvider dataRandomGetPost */
	public function testWithExist(array $get, array $post):void {
		$withKeys = [];

		for($i = 0; $i < 10; $i++) {
			if(rand(0, 1)) {
				$withKeys []= array_rand($get);
			}
			else {
				$withKeys []= array_rand($post);
			}
		}

		$input = new Input($get, $post);
		$trigger = $input->with(...$withKeys);
		$keysCalled = [];

		$trigger->call(function(InputData $data) use(&$keysCalled) {
			foreach($data as $key => $value) {
				$keysCalled []= $key;
			}
		});

		foreach($withKeys as $key) {
			self::assertContains($key, $keysCalled);
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testWithNotExist(array $get, array $post):void {
		$withKeys = [];
		$combined = array_merge($get, $post);

		for($i = 0; $i < 3; $i++) {
			$withKeys []= array_rand($combined);
		}

		$withKeys []= "does_not_exist";
		$input = new Input($get, $post);
		self::expectException(MissingInputParameterException::class);
		$trigger = $input->with(...$withKeys);
	}

	/** @dataProvider dataRandomGetPost */
	public function testWithAll(array $get, array $post):void {
		$input = new Input($get, $post);
		$trigger = $input->withAll();
		$keysCalled = [];

		$trigger->call(function(InputData $data) use(&$keysCalled) {
			foreach($data as $key => $value) {
				$keysCalled []= $key;
			}
		});

		foreach(array_merge($get, $post) as $key => $value) {
			self::assertContains($key, $keysCalled);
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testNoWith(array $get, array $post):void {
		$post["example-trigger"] = "testtesttest";

		$input = new Input($get, $post);
		$trigger = $input->when(["example-trigger" => "testtesttest"]);
		$keysCalled = [];

		$trigger->call(function(InputData $data) use(&$keysCalled) {
			foreach($data as $key => $value) {
				$keysCalled []= $key;
			}
		});

		foreach(array_merge($get, $post) as $key => $value) {
			self::assertContains($key, $keysCalled);
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testWithout(array $get, array $post):void {
		$withoutKeys = [];

		for($i = 0; $i < 10; $i++) {
			if(rand(0, 1)) {
				$withoutKeys []= array_rand($get);
			}
			else {
				$withoutKeys []= array_rand($post);
			}
		}

		$input = new Input($get, $post);
		$trigger = $input->without(...$withoutKeys);
		$keysCalled = [];

		$trigger->call(function(InputData $data) use(&$keysCalled) {
			foreach($data as $key => $value) {
				$keysCalled []= $key;
			}
		});

		foreach(array_merge($get, $post) as $key => $value) {
			if(in_array($key, $withoutKeys)) {
				continue;
			}

			self::assertContains($key, $keysCalled);
		}
		foreach($withoutKeys as $key) {
			self::assertNotContains($key, $keysCalled);
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testSettingOwnData(array $get, array $post):void {
		$input = new Input($get, $post);
		$originalInputCount = count($input);

		$input["added-from-test"] = uniqid();

		self::assertEquals($originalInputCount + 1, count($input));
	}

	/** @dataProvider dataRandomGetPost */
	public function testUnsettingOwnData(array $get, array $post):void {
		$input = new Input($get, $post);
		$originalInputCount = count($input);

		$getToUnset = array_rand($get);
		$postToUnset = array_rand($post);

		unset($input[$getToUnset]);
		self::assertEquals($originalInputCount - 1, count($input));

		unset($input[$postToUnset]);
		self::assertEquals($originalInputCount - 2, count($input));
	}

	public function testGetString():void {
		$input = new Input(self::FAKE_DATA);
		foreach(self::FAKE_DATA as $key => $value) {
			self::assertIsString(
				$input->getString($key)
			);
		}
	}

	public function testGetInt():void {
		$input = new Input(self::FAKE_DATA);
		self::assertNotSame("1234",$input->getInt("a-number"));
		self::assertSame(1234, $input->getInt("a-number"));
		self::assertSame(2, $input->getInt("one-plus-one"));
		self::assertSame(1, $input->getInt("many-dots"));
		self::assertSame(0, $input->getInt("this-is-positive"));
		self::assertNull($input->getInt("empty-value"));
		self::assertNull($input->getInt("not-set"));
	}

	public function testGetFloat():void {
		$input = new Input(self::FAKE_DATA);
		self::assertSame(1234.0, $input->getFloat("a-number"));
		self::assertSame(2.0, $input->getFloat("one-plus-one"));
		self::assertSame(2.5, $input->getFloat("half-of-five"));
		self::assertSame(1.2, $input->getFloat("many-dots"));
		self::assertSame(0.0, $input->getFloat("this-is-positive"));
		self::assertNull($input->getFloat("empty-value"));
		self::assertNull($input->getFloat("not-set"));
	}

	public function testGetBool():void {
		$input = new Input(self::FAKE_DATA);
		self::assertSame(true, $input->getBool("a-number"));
		self::assertSame(true, $input->getBool("one-plus-one"));
		self::assertSame(true, $input->getBool("half-of-five"));
		self::assertSame(true, $input->getBool("many-dots"));
		self::assertSame(true, $input->getBool("this-is-positive"));
		self::assertNull($input->getBool("empty-value"));
		self::assertNull($input->getBool("not-set"));
	}

	public function testGetDateTime():void {
		$dateTime = new DateTime("2005-08-15T15:52");

		$input = new Input(self::FAKE_DATA);
		self::assertNull($input->getDateTime("empty-value"));
		self::assertEquals($dateTime, $input->getDateTime("a-date-with-timezone"));
		self::assertEquals($dateTime, $input->getDateTime("a-date-without-timezone"));
		self::assertEquals($dateTime, $input->getDateTime("a-date-without-timezone-or-seconds"));
	}

	public function testGetDateTimeInvalid():void {
		$input = new Input(self::FAKE_DATA);
		self::expectException(DataNotCompatibleFormatException::class);
		$input->getDateTime("one-plus-one");
	}

	public function testGetDateTimeFromFormat():void {
		$dateTime = new DateTime("2005-08-15T15:52");

		$input = new Input(self::FAKE_DATA);
		self::assertEquals($dateTime, $input->getDateTime("a-date-in-rss-format"));
	}

	/** @dataProvider dataRandomGetPost */
	public function testContains($get, $post):void {
		$files = self::FAKE_FILE;
		$input = new Input($get, $post, $files);

		foreach($get as $key => $value) {
			self::assertTrue($input->contains($key));
		}
		foreach($post as $key => $value) {
			self::assertTrue($input->contains($key));
		}
		foreach($files as $key => $value) {
			self::assertTrue($input->contains($key));
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testNotContains($get, $post):void {
		$files = self::FAKE_FILE;
		$input = new Input($get, $post, $files);

		$all = array_merge($get, $post, $files);
		foreach($all as $key => $value) {
			$missingKey = "missing-$key";
			self::assertFalse(
				$input->contains($missingKey)
			);
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testContainsIndividualParts($get, $post):void {
		$files = self::FAKE_FILE;
		$input = new Input($get, $post, $files);

		foreach($get as $key => $value) {
			self::assertTrue(
				$input->contains(
					$key,
					Input::DATA_QUERYSTRING
				)
			);
			self::assertFalse(
				$input->contains(
					$key,
					Input::DATA_BODY
				)
			);
			self::assertFalse(
				$input->contains(
					$key,
					Input::DATA_FILES
				)
			);
		}

		foreach($post as $key => $value) {
			self::assertFalse(
				$input->contains(
					$key,
					Input::DATA_QUERYSTRING
				)
			);
			self::assertTrue(
				$input->contains(
					$key,
					Input::DATA_BODY
				)
			);
			self::assertFalse(
				$input->contains(
					$key,
					Input::DATA_FILES
				)
			);
		}

		foreach($files as $key => $value) {
			self::assertFalse(
				$input->contains(
					$key,
					Input::DATA_QUERYSTRING
				)
			);
			self::assertFalse(
				$input->contains(
					$key,
					Input::DATA_BODY
				)
			);
			self::assertTrue(
				$input->contains(
					$key,
					Input::DATA_FILES
				)
			);
		}
	}

	/** @dataProvider dataRandomGetPost */
	public function testContainsThrowsExceptionOnIncorrectType($get, $post) {
		self::expectException(InvalidInputMethodException::class);
		$input = new Input($get, $post);
		$input->contains("anything", "invalid-method");
	}

	public function testGetMultipleFile():void {
		$get = [];
		$post = ["do" => "upload"];
		$files = [
			"uploads" => [
				"name" => ["one.txt", "two.txt"],
				"type" => ["plain/text", "plain/text"],
				"size" => [123, 321],
				"tmp_name" => ["/tmp/aaaaa", "/tmp/bbbbb"],
				"error" => [0, 0],
				"full_path" => ["one.txt", "two.txt"],
			]
		];
		$sut = new Input($get, $post, $files);
		$multipleFiles = $sut->getMultipleFile("uploads");
		self::assertCount(count($files["uploads"]["name"]), $multipleFiles);

		$i = 0;
		foreach($multipleFiles as $fileName => $file) {
			self::assertSame($files["uploads"]["name"][$i], $fileName);
			self::assertSame($files["uploads"]["tmp_name"][$i], $file->getRealPath());
			$i++;
		}
	}

	public function testGetBodyJson_notJson():void {
		$get = [];
		$post = ["this" => "is not JSON!"];
		$sut = new Input($get, $post);
		$json = $sut->getBodyJson();
		self::assertNull($json);
	}

	public function testGetBodyJson_string():void {
		$inputPath = tempnam(sys_get_temp_dir(), "phpgt-input-test-");
		file_put_contents($inputPath, "\"Hello, PHP.Gt!\"");
		$sut = new Input(bodyPath: $inputPath);
		$json = $sut->getBodyJson();
		self::assertInstanceOf(JsonStringPrimitive::class, $json);
		self::assertSame("Hello, PHP.Gt!", $json->getPrimitiveValue());
	}

	public function testGetBodyJson_arrayWithObject():void {
		$inputPath = tempnam(sys_get_temp_dir(), "phpgt-input-test-");
		file_put_contents($inputPath, "[1, 2, 3, {\"name\": \"Cody\"}]");
		$sut = new Input(bodyPath: $inputPath);
		$json = $sut->getBodyJson();
		self::assertInstanceOf(JsonArrayPrimitive::class, $json);
		$array = $json->getPrimitiveValue();
		self::assertSame(1, $array[0]);
		self::assertSame(2, $array[1]);
		self::assertSame(3, $array[2]);
		/** @var JsonObject $thirdArrayElement */
		$thirdArrayElement = $array[3];
		self::assertInstanceOf(JsonObject::class, $thirdArrayElement);
		self::assertSame("Cody", $thirdArrayElement->getString("name"));
	}

	public function testGetBodyJson_withQueryString():void {
		$inputPath = tempnam(sys_get_temp_dir(), "phpgt-input-test-");
		file_put_contents($inputPath, "\"Hello, PHP.Gt!\"");
		$sut = new Input(["id" => 123], bodyPath: $inputPath);
		$json = $sut->getBodyJson();
		self::assertInstanceOf(JsonStringPrimitive::class, $json);
		self::assertSame("Hello, PHP.Gt!", $json->getPrimitiveValue());
		self::assertSame(123, $sut->getInt("id"));
	}

	public function testSelectPrefix_noMatch():void {
		$sut = new Input(["id" => 123]);
		$trigger = $sut->selectPrefix("io_");

		$triggered = false;
		$trigger->call(function(...$args)use(&$triggered) {
			$triggered = true;
		});
		$trigger->fire();
		self::assertFalse($triggered);
	}

	public function testSelectPrefix_match():void {
		$sut = new Input(["id" => 123, "io_database" => "connect"]);
		$trigger = $sut->selectPrefix("io_");

		$triggered = false;
		$trigger->call(function(...$args)use(&$triggered) {
			$triggered = true;
		});
		$trigger->fire();
		self::assertTrue($triggered);
	}

	public static function dataRandomGetPost():array {
		$data = [];

		for($i = 0; $i < 10; $i++) {
			$params = [
				Helper::getRandomKvp(rand(10, 100), "get-"),
				Helper::getRandomKvp(rand(10, 100), "post-"),
			];
			$data []= $params;
		}

		return $data;
	}

	public static function dataRandomString():array {
		$data = [];

		for($i = 0; $i < 10; $i++) {
			$params = [
				uniqid(),
			];
			$data []= $params;
		}

		return $data;
	}
}
