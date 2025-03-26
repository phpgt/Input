<?php
namespace Gt\Input\Trigger;

class NeverTrigger extends Trigger {
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	public function call(callable $callback, string ...$args):Trigger {
		return $this;
	}

	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	public function orCall(callable $callback, string ...$args):Trigger {
		return $this;
	}

	public function fire():bool {
		return false;
	}
}
