<?php
namespace Gt\Input\InputData;

use Gt\Input\Input;
use Gt\Input\InputData\Datum\InputDatum;

trait KeyValueArrayAccess {
	public function offsetExists($offset):bool {
		return isset($this->parameters[$offset]);
	}

	public function offsetGet($offset):mixed {
		if($this instanceof FileUploadInputData) {
			return $this->getFile($offset);
		}
		/** @phpstan-ignore-next-line */
		elseif(is_a($this, Input::class) || is_a($this, InputData::class)) {
			if($this->contains($offset)) {
				return $this->get($offset);
			}
		}

		return null;
	}

	/**
	 * @param string $offset
	 * @param string|InputDatum $value
	 */
	public function offsetSet($offset, $value):void {
		if($this->parameters instanceof InputData) {
			if(is_string($value)) {
				$this->parameters->addKeyValue($offset, $value);

			}
			else {
				$this->parameters->add($offset, $value);
			}
		}
		else {
			if(is_string($value)) {
				$value = new InputDatum($value);
			}

			$this->parameters[$offset] = $value;
		}
	}

	public function offsetUnset($offset):void {
		if($this->parameters instanceof InputData) {
			$this->parameters->remove($offset);
		}
		else {
			unset($this->parameters[$offset]);
		}
	}
}
