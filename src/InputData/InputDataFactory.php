<?php
namespace GT\Input\InputData;

use GT\Input\Input;
use GT\Input\WithWithoutClashingException;

class InputDataFactory {
	/**
	 * @param array<string>|array<string, string> $with
	 * @param array<string>|array<string, string> $without
	 */
	public static function create(
		Input $input,
		array $with = [],
		array $without = [],
	):InputData {
		$data = $input->getAll();

		if(empty($with)
		&& empty($without)) {
			return $data;
		}

// It's fine to call $input->with()->without, but not if the same value exists
// in with and without.
		if(!empty($with)
		&& !empty($without)) {
			$clash = array_intersect($with, $without);
			if(!empty($clash)) {
				throw new WithWithoutClashingException(
					implode(", ", $clash)
				);
			}
		}

		if(!empty($without)) {
			$data->remove(...$without);
		}

		if(!empty($with)) {
			$data->removeExcept(...$with);
		}

		return $data;
	}
}
