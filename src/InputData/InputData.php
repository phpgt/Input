<?php
namespace GT\Input\InputData;

use GT\Input\InputData\Datum\InputDatum;
use GT\Input\InputData\Datum\MultipleInputDatum;
use GT\Input\InputValueGetter;

class InputData extends AbstractInputData {
	/** @param iterable<string,string>|iterable<string, array<string>>|iterable<InputData>...$sources */
	public function __construct(iterable...$sources) {
		$this->parameters = [];

		foreach($sources as $source) {
			foreach($source as $key => $value) {
				if(is_array($value)
				&& isset($value[0])) {
					$value = new MultipleInputDatum($value);
				}
				else {
					$value = new InputDatum($value);
				}
				$this->add($key, $value);
			}
		}
	}

	public function add(string $key, InputDatum $datum):self {
		$this->parameters[$key] = $datum;
		return $this;
	}

	public function addKeyValue(string $key, string $value):self {
		$datum = new InputDatum($value);
		return $this->add($key, $datum);
	}

	public function remove(string...$keys):self {
		foreach($keys as $key) {
			if(isset($this->parameters[$key])) {
				unset($this->parameters[$key]);
			}
		}

		return $this;
	}

	public function removeExcept(string...$keys):self {
		foreach(array_keys($this->parameters) as $key) {
			if(!in_array($key, $keys)) {
				unset($this->parameters[$key]);
			}
		}

		return $this;
	}

	/** @return array<string> */
	public function getKeys():array {
		return array_keys($this->parameters);
	}

	/** @return array<string, string|array<string>> */
	public function asArray():array {
		$array = [];

		foreach($this->parameters as $key => $value) {
			if($value instanceof MultipleInputDatum) {
				$array[$key] = $value->toArray();
			}
			else {
				$array[$key] = (string)$value;
			}
		}

		return $array;
	}
}
