<?php
namespace Gt\Input;

use ArrayAccess;
use Countable;
use Gt\Json\JsonDecodeException;
use Gt\Json\JsonObject;
use Gt\Json\JsonObjectBuilder;
use Iterator;
use Psr\Http\Message\StreamInterface;
use Gt\Input\Trigger\Trigger;
use Gt\Input\InputData\InputData;
use Gt\Input\InputData\Datum\InputDatum;
use Gt\Input\InputData\KeyValueArrayAccess;
use Gt\Input\InputData\KeyValueCountable;
use Gt\Input\InputData\KeyValueIterator;
use Gt\Input\InputData\BodyInputData;
use Gt\Input\InputData\CombinedInputData;
use Gt\Input\InputData\FileUploadInputData;
use Gt\Input\InputData\QueryStringInputData;

/**
 * @implements ArrayAccess<string, ?string>
 * @implements Iterator<string, ?string>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Input implements ArrayAccess, Countable, Iterator {
	use InputValueGetter;
	use KeyValueArrayAccess;
	use KeyValueCountable;
	use KeyValueIterator;

	const DATA_QUERYSTRING = "get";
	const DATA_BODY = "post";
	const DATA_FILES = "files";
	const DATA_COMBINED = "combined";

	protected BodyStream $bodyStream;
	protected QueryStringInputData $queryStringParameters;
	protected BodyInputData $bodyParameters;

	/**
	 * @param array<string, string> $get
	 * @param array<string, string> $post
	 * @param array<string, array<int|string, string|array<int|string>>> $files
	 * @param string $bodyPath
	 */
	public function __construct(
		array $get = [],
		array $post = [],
		array $files = [],
		string $bodyPath = "php://input",
	) {
		$this->bodyStream = new BodyStream($bodyPath);

		$this->queryStringParameters = new QueryStringInputData($get);
		$this->bodyParameters = new BodyInputData($post);
		$this->fileUploadParameters = new FileUploadInputData($files);

		$this->parameters = new CombinedInputData(
			$this->queryStringParameters,
			$this->bodyParameters,
			$this->fileUploadParameters
		);
	}

	/**
	 * Returns the input payload as a streamable HTTP request body.
	 */
	public function getStream():StreamInterface {
		return $this->bodyStream;
	}

	public function add(string $key, InputDatum $datum, string $method):void {
		switch($method) {
		case self::DATA_QUERYSTRING:
			$this->queryStringParameters =
				$this->queryStringParameters->withKeyValue(
					$key,
					$datum
				);
			break;

		case self::DATA_BODY:
			$this->bodyParameters =
				$this->bodyParameters->withKeyValue(
					$key,
					$datum
				);
			break;

		case self::DATA_FILES:
			$this->fileUploadParameters =
				$this->fileUploadParameters->withKeyValue(
					$key,
					$datum
				);
			break;

		default:
			throw new InvalidInputMethodException($method);
		}

		$this->parameters = new CombinedInputData(
			$this->queryStringParameters,
			$this->bodyParameters,
			$this->fileUploadParameters
		);
	}

	/**
	 * Get a particular input value by its key. To specify either GET or
	 * POST variables, pass Input::METHOD_GET or Input::METHOD_POST as the
	 * second parameter (defaults to Input::METHOD_BOTH).
	 */
	public function get(string $key, ?string $method = null):mixed {
		if(is_null($method)) {
			$method = self::DATA_COMBINED;
		}

		$data = match($method) {
			self::DATA_QUERYSTRING => $this->queryStringParameters->get($key),
			self::DATA_BODY => $this->bodyParameters->get($key),
			self::DATA_FILES => $this->fileUploadParameters->getFile($key),
			self::DATA_COMBINED => $this->parameters->get($key),
			default => throw new InvalidInputMethodException($method),
		};

		return $data?->getValue();
	}

	/**
	 * Does the input contain the specified key?
	 */
	public function contains(string $key, ?string $method = null):bool {
		if(is_null($method)) {
			$method = self::DATA_COMBINED;
		}

		switch($method) {
		case self::DATA_QUERYSTRING:
			$isset = $this->containsQueryStringParameter($key);
			break;

		case self::DATA_BODY:
			$isset =$this->containsBodyParameter($key);
			break;

		case self::DATA_FILES:
			$isset =$this->containsFile($key);
			break;

		case self::DATA_COMBINED:
			$isset = isset($this->parameters[$key]);
			break;

		default:
			throw new InvalidInputMethodException($method);
		}

		return $isset;
	}

	public function containsQueryStringParameter(string $key):bool {
		return isset($this->queryStringParameters[$key]);
	}

	public function containsBodyParameter(string $key):bool {
		return isset($this->bodyParameters[$key]);
	}

	public function containsFile(string $key):bool {
		return isset($this->fileUploadParameters[$key]);
	}

	/**
	 * Get an InputData object containing all request variables. To specify
	 * only GET or POST variables, pass Input::METHOD_GET
	 * or Input::METHOD_POST.
	 */
	public function getAll(?string $method = null):InputData {
		if(is_null($method)) {
			$method = self::DATA_COMBINED;
		}

		switch($method) {
		case self::DATA_QUERYSTRING:
			return $this->queryStringParameters;
		case self::DATA_BODY:
			return $this->bodyParameters;
		case self::DATA_FILES:
			return $this->fileUploadParameters;
		case self::DATA_COMBINED:
			return $this->parameters;
		default:
			throw new InvalidInputMethodException($method);
		}
	}

	public function getBodyJson():?JsonObject {
		$jsonBuilder = new JsonObjectBuilder();

		try {
			return $jsonBuilder->fromJsonString($this->bodyStream->getContents());
		}
		catch(JsonDecodeException) {
			return null;
		}
	}

	/**
	 * Return a "do" Trigger, matching when a request variable is present with the
	 * provided $match value.
	 */
	public function do(string $match):Trigger {
		return $this->when(["do" => $match]);
	}

	/**
	 * Return a Trigger, firing when one or more request variables are
	 * present with the provided key value pair(s) are present.
	 *
	 * $matches is an associative array, where the key is a request
	 * variable's name and the value is the request variable's value
	 * to match.
	 *
	 * @param array<string, string>|string $matches
	 */
	public function when(array|string...$matches):Trigger {
		$trigger = new Trigger($this);
		$trigger->when($matches);
		return $trigger;
	}

	/**
	 * Return a Trigger that will only pass the provided keys to its callback.
	 */
	public function select(string...$keys):Trigger {
		foreach($keys as $key) {
			if(!$this->parameters->contains($key)) {
				throw new MissingInputParameterException($key);
			}
		}

		return $this->newTrigger("with", ...$keys);
	}

	/** @deprecated Use select() instead to avoid ambiguity with immutable `with` functions */
	public function with(string...$keys):Trigger {
		return $this->select(...$keys);
	}

	/**
	 * Return a Trigger that will pass all keys apart from the provided
	 * keys to its callback.
	 */
	public function selectAllExcept(string...$keys):Trigger {
		return $this->newTrigger("without", ...$keys);
	}
	/** @deprecated Use selectAllExcept() instead to avoid ambiguity with immutable `with` functions */
	public function without(string...$keys):Trigger {
		return $this->selectAllExcept(...$keys);
	}

	/**
	 * Return a Trigger that will pass all keys to its callback.
	 */
	public function selectAll():Trigger {
		return $this->newTrigger("withAll");
	}
	/** @deprecated Use selectAll() instead to avoid ambiguity with immutable `with` functions */
	public function withAll():Trigger {
		return $this->selectAll();
	}

	protected function newTrigger(string $functionName, string...$args):Trigger {
		$trigger = new Trigger($this);
		return $trigger->$functionName(...$args);
	}
}
