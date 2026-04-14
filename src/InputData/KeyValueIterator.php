<?php
namespace GT\Input\InputData;

use GT\Input\InputData\Datum\InputDatum;

trait KeyValueIterator {
	/** @var int */
	protected $iteratorKey;

	public function current():?InputDatum {
		return $this->parameters[$this->getIteratorDataKey()];
	}

	public function next():void {
		$this->iteratorKey++;
	}

	public function key():string {
		return $this->getIteratorDataKey();
	}

	public function valid():bool {
		return !empty($this->getIteratorDataKey());
	}

	public function rewind():void {
		$this->iteratorKey = 0;
	}

	protected function getIteratorDataKey():?string {
		$keys = [];

		if(is_array($this->parameters)) {
			$keys = array_keys($this->parameters);
		}
		else if($this->parameters instanceof InputData) {
			$keys = $this->parameters->getKeys();
		}

		return $keys[$this->iteratorKey] ?? null;
	}
}
