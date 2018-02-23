<?php
namespace Gt\Input\Test;

use Gt\Input\Test\Helper\Reflection;
use Gt\Input\FileUploadInputData;
use PHPUnit\Framework\TestCase;

class UploadDataTest extends TestCase {
	/**
	 * @dataProvider dataFilesSuperglobal
	 */
	public function testNormaliseArray(array $files) {
		$method = Reflection::getMethod(
			FileUploadInputData::class,
			"normalizeArray"
		);

		$numberOfStrings = 0;
		$numberOfArrays = 0;

		foreach($files as $uploadName => $uploadData) {
			foreach($uploadData as $key => $value) {
				if(is_string($value)) {
					$numberOfStrings++;
				}
				else if(is_array($value)) {
					$numberOfArrays++;
				}
			}
		}

		self::assertGreaterThan(0, $numberOfStrings);
		self::assertGreaterThan(0, $numberOfArrays);

		$uploadData = new FileUploadInputData($files);
		$normalized = $method->invoke($uploadData, $files);

		$numberOfStrings = 0;
		$numberOfArrays = 0;

		foreach($normalized as $uploadName => $uploadData) {
			foreach($uploadData as $key => $value) {
				if(is_string($value)) {
					$numberOfStrings++;
				}
				else if(is_array($value)) {
					$numberOfArrays++;
				}
			}
		}

		self::assertEquals(0, $numberOfStrings);
		self::assertGreaterThan(0, $numberOfArrays);
	}

	public function dataFilesSuperglobal():array {
		$data = [];
		$num = 10;
		$fileKeys = ["name", "type", "tmp_name", "error", "size"];

		for($i = 0; $i < $num; $i++) {
			$files = [];

			$hasAtLeastOneFieldBeenSingle = false;
			$hasAtLeastOneFieldBeenMulti = false;

			$numDifferentFileFields = rand(1, 50);
			for($iFileUpload = 0; $iFileUpload < $numDifferentFileFields; $iFileUpload++) {
				$name = "upload_" . uniqid();
				$files[$name] = [];

				if(!$hasAtLeastOneFieldBeenSingle) {
					$numFilesWithinSameField = 1;
					$hasAtLeastOneFieldBeenSingle = true;
				}
				else if(!$hasAtLeastOneFieldBeenMulti) {
					$numFilesWithinSameField = 5;
					$hasAtLeastOneFieldBeenMulti = true;
				}
				else {
					$numFilesWithinSameField = rand(1, 5);
				}

				if($numFilesWithinSameField === 1) {
					foreach($fileKeys as $key) {
						$files[$name][$key] = uniqid($key . "_");
					}
				}
				else {
					for($iFileInField = 0; $iFileInField < $numFilesWithinSameField; $iFileInField++) {
						foreach($fileKeys as $key) {
							if(!isset($files[$name][$key])) {
								$files[$name][$key] = [];
							}
							$files[$name][$key] []= uniqid($key . "_");
						}
					}
				}
			}

			$data []= [$files];
		}

		return $data;
	}
}