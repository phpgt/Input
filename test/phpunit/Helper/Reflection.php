<?php
namespace GT\Input\Test\Helper;

use ReflectionClass;
use ReflectionMethod;

class Reflection {
	public static function getMethod(string $className, string $methodName):ReflectionMethod {
		$class = new ReflectionClass($className);
		$method = $class->getMethod($methodName);
		$method->setAccessible(true);
		return $method;
	}
}
