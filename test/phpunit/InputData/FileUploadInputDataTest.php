<?php
namespace GT\Input\Test\InputData;

use PHPUnit\Framework\TestCase;
use GT\Input\InputData\FileUploadInputData;
use GT\Input\Test\Helper\Reflection;

class FileUploadInputDataTest extends TestCase {
	/**
	 * @dataProvider dataFilesSuperglobal
	 */
	public function testNormaliseArray(array $files) {
// The $_FILES superglobal is an odd shape. Depending on the use of [] in the
// parameter name, the contained uploadData array(s) could contain either strings
// or arrays. The dataFilesSuperGlobal dataProvider creates $_FILES that definitely
// contain both strings and arrays.
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

	public static function dataFilesSuperglobal():array {
		$num = 10;

		$data = [];
		$fileKeys = ["name", "type", "tmp_name", "error", "size"];

		for($i = 0; $i < $num; $i++) {
			$files = [];

			$hasAtLeastOneFieldBeenSingle = false;
			$hasAtLeastOneFieldBeenMulti = false;

			$numDifferentFileFields = rand(2, 50);
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
