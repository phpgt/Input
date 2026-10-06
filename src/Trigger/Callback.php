<?php
namespace GT\Input\Trigger;

use GT\Input\InputData\InputData;

class Callback {
	/** @var callable */
	private $callback;
	/** @var array<string> */
	private array $args;

	public function __construct(callable $callback, string...$args) {
		$this->callback = $callback;
		$this->args = $args;
	}

	public function call(InputData $data):void {
// TODO: Issue #8 Rather than passing all fields into the first parameter,
// pass them individually.
		// @see https://github.com/PhpGt/Input/issues/8
		$parameters = [$data];
		foreach($this->args as $arg) {
			$parameters []= $arg;
		}

		call_user_func_array($this->callback, $parameters);
	}
}
