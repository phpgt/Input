<?php
namespace GT\Input\InputData\Datum;

use ArrayAccess;
use GT\Input\ImmutableObjectModificationException;
use Iterator;

/**
 * @implements ArrayAccess<string|int, mixed>
 * @implements Iterator<string|int, mixed>
 * @SuppressWarnings("TooManyPublicMethods")
 */
class MultipleInputDatum extends InputDatum implements ArrayAccess, Iterator {
	protected int $iteratorKey;

	public function __construct(mixed $value) {
		parent::__construct($value);

		$this->iteratorKey = 0;
	}

	public function __toString():string {
		return implode(", ", $this->value);
	}

	/** @return array<string, string> */
	public function toArray():array {
		$array = [];

		foreach($this as $key => $value) {
			$array[$key] = (string)$value;
		}

		return $array;
	}

	/**
	 * @link http://php.net/manual/en/iterator.current.php
	 */
	public function current():mixed {
		return $this->offsetGet($this->iteratorKey);
	}

	/**
	 * @link http://php.net/manual/en/iterator.next.php
	 */
	public function next():void {
		$this->iteratorKey++;
	}

	/**
	 * @link http://php.net/manual/en/iterator.key.php
	 */
	public function key():int {
		return $this->iteratorKey;
	}

	/**
	 * @link http://php.net/manual/en/iterator.valid.php
	 */
	public function valid():bool {
		return $this->offsetExists($this->iteratorKey);
	}

	/**
	 * @link http://php.net/manual/en/iterator.rewind.php
	 */
	public function rewind():void {
		$this->iteratorKey = 0;
	}

	/** @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param string|int $offset
	 */
	public function offsetExists($offset):bool {
		return isset($this->value[$offset]);
	}

	/** @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param string|int $offset
	 */
	public function offsetGet($offset):mixed {
		return $this->value[$offset];
	}

	/**
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param string|int $offset
	 * @param string $value
	 * @SuppressWarnings("UnusedFormalParameter")
	 */
	public function offsetSet($offset, $value):void {
		throw new ImmutableObjectModificationException(
			"Trying to set $offset with $value"
		);
	}

	/**
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param string|int $offset
	 * @SuppressWarnings("UnusedFormalParameter")
	 */
	public function offsetUnset($offset):void {
		throw new ImmutableObjectModificationException("Trying to unset $offset");
	}
}
