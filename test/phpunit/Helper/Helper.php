<?php
namespace GT\Input\Test\Helper;

use GT\Input\Input;
use GT\Input\InputData\InputData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Helper {
	public static function getPostPizza():array {
		return [
			"toppings" => ["Mozzarella", "Basil", "Mushroom", "Peppers", "Onion", "Spinach"],
			"name" => "Greg's pizza",
		];
	}

	public static function getRandomKvp(int $num, string $prefix = ""):array {
		$kvp = [];

		for($i = 0; $i < $num; $i++) {
			$key = "key-$i-$prefix" . uniqid();
			$value = "value-$i-$prefix" . str_repeat(uniqid(), rand(1, 10));
			$kvp[$key] = $value;
		}

		return $kvp;
	}

	public static function getRandomWhenCriteria(Input $input, bool $inInput = true):array {
		if($inInput) {
			$inputData = $input->getAll();
			$inputDataArray = self::convertInputDataToArray($inputData);
			$numberToCreate = rand(1, count($inputDataArray));
			$keys = array_rand($inputDataArray, $numberToCreate);
		}
		else {
			$inputDataArray = [];
			$numberToCreate = rand(1, 100);
			for($i = 0; $i < $numberToCreate; $i++) {
				$inputDataArray[
					"key-$i-madeup" . uniqid()
				] = "value-$i-madeup" . uniqid();
			}
			$keys = array_rand($inputDataArray, $numberToCreate);
		}

		if(!is_array($keys)) {
			$keys = [$keys];
		}

		$criteria = [];
		foreach($keys as $k) {
			$criteria[$k] = (string)$inputDataArray[$k];
		}

		return $criteria;
	}

	public static function convertInputDataToArray(InputData $inputData):array {
		$array = [];

		foreach($inputData as $key => $value) {
			$array[$key] = $value;
		}

		return $array;
	}

	public static function getKeysFromInput(Input $input, int $num):array {
		$keys = [];

		foreach($input as $key => $value) {
			$keys []= $key;

			if(count($keys) >= $num) {
				break;
			}
		}

		return $keys;
	}

	public static function cleanUp() {
		$testDirectory = Helper::getTestDirectory();

		if(!is_dir($testDirectory)) {
			return;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$testDirectory,
				RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $fileInfo) {
			$action = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
			$action($fileInfo->getRealPath());
		}

		rmdir($testDirectory);
	}

	public static function getTestDirectory() {
		return implode(DIRECTORY_SEPARATOR, [
			sys_get_temp_dir(),
			"phpgt",
			"input",
			"test",
		]);
	}
}
