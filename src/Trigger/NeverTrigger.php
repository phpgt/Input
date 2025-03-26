<?php
namespace Gt\Input\Trigger;

class NeverTrigger extends Trigger {
	public function call(callable $callback, string ...$args):Trigger {
		return $this;
	}

	public function orCall(callable $callback, string ...$args):Trigger {
		return $this;
	}

	public function fire():bool {
		return false;
	}
}
