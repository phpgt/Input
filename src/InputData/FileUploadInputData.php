<?php
namespace GT\Input\InputData;

use GT\Input\InputData\Datum\FailedFileUpload;
use GT\Input\InputData\Datum\FileUpload;

class FileUploadInputData extends InputData {
	/**
	 * @param array<string, string|array<string, string>> $files
	 */
	public function __construct(array $files) {
		$files = $this->normalizeArray($files);
		$data = $this->createData($files);
		parent::__construct($data);
	}

	/**
	 * The files array is an associative array where the key is the name of
	 * the request parameter, and the value is another associative array
	 * with keys:
	 * + name
	 * + type
	 * + tmp_name
	 * + error
	 * + size
	 * Each key's value is string, unless the request parameter name ends
	 * with [], in which case each value is another array. This function
	 * normalises the array to the latter.
	 *
	 * @param array<string, string|array<string, string|array<string>>> $files
	 * @return array<string, array<string, array<string>>>
	 */
	protected function normalizeArray(array $files):array {
		foreach($files as $parameterName => $fileDetailArray) {
			foreach($fileDetailArray as $key => $value) {
				if(!is_array($value)) {
					$files[$parameterName][$key] = [$value];
				}
			}
		}

		return $files;
	}

	/**
	 * @param array<string, array<string, array<string>>> $files
	 * @return array<string, array<FileUpload>>
	 */
	protected function createData(array $files):array {
		$datumList = [];

		foreach($files as $inputName => $details) {
			$datumList[$inputName] = [];

			foreach(array_keys($details["tmp_name"]) as $i) {
				$params = [
					$details["name"][$i],
					$details["type"][$i],
					(int)$details["size"][$i],
					$details["tmp_name"][$i],
				];

				if($details["error"][$i] == UPLOAD_ERR_OK) {
					array_push(
						$datumList[$inputName],
						new FileUpload(...$params)
					);
				}
				else {
					$params []= (int)$details["error"][$i];
					array_push(
						$datumList[$inputName],
						new FailedFileUpload(...$params)
					);
				}
			}
		}

		return $datumList;
	}
}
