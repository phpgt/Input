<?php
namespace GT\Input\InputData;

use ArrayAccess;
use Countable;
use GT\Input\InputData\Datum\MultipleInputDatum;
use GT\Input\InputValueGetter;
use Iterator;
use GT\Input\InputData\Datum\InputDatum;

/**
 * @implements ArrayAccess<string, string|InputDatum>
 * @implements Iterator<string, string|InputDatum>
 */
abstract class AbstractInputData implements ArrayAccess, Countable, Iterator {
	use InputValueGetter;
	use KeyValueArrayAccess;
	use KeyValueCountable;
	use KeyValueIterator;

	protected QueryStringInputData $queryStringParameters;
	protected BodyInputData $bodyParameters;

	public function get(string $key):mixed {
		return $this->parameters[$key] ?? null;
	}

	public function contains(string $key):bool {
		return isset($this->parameters[$key]);
	}

	public function hasValue(string $key):bool {
		$value = $this->parameters[$key] ?? "";
		return (strlen((string)$value) > 0);
	}

	protected function set(string $key, InputDatum $value):void {
		$this->parameters[$key] = (string)$value;
	}

	public function withKeyValue(string $key, InputDatum $value):static {
		$clone = clone($this);
		$clone->parameters[$key] = (string)$value;
		return $clone;
	}
}
