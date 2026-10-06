<?php
namespace GT\Input\InputData\Datum;

class InputDatum {
	protected mixed $value;

	public function __construct(mixed $value) {
		$this->value = $value;
	}

	public function __toString():string {
		return $this->value;
	}

	public function getValue():mixed {
		return $this->value;
	}
}
