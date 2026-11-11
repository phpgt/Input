<?php
namespace GT\Input\Test\Integration;

use GT\Input\PostSizeExceededException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InputTest extends TestCase {
	#[DataProvider("postSizes")]
	public function testValidatePostSize_handlesPhpDiscardingTheForm(int $fileSize, string $limit, string $readPostData, int $expectedStatus):void {
		$socket = stream_socket_server("tcp://127.0.0.1:0");
		$address = stream_socket_get_name($socket, false);
		fclose($socket);
		$command = [PHP_BINARY, "-d", "post_max_size=$limit", "-d", "upload_max_filesize=8K", "-d", "enable_post_data_reading=$readPostData", "-d", "display_errors=0", "-d", "log_errors=0", "-S", $address, __DIR__ . "/Helper/post-size.php"];
		$sut = proc_open($command, [1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes);

		try {
			$this->awaitServer($address, $sut);
			$boundary = "InputPostSizeBoundary";
			$body = "--$boundary\r\nContent-Disposition: form-data; name=\"submit\"\r\n\r\nUpload\r\n"
				. "--$boundary\r\nContent-Disposition: form-data; name=\"photo\"; filename=\"photo.txt\"\r\nContent-Type: text/plain\r\n\r\n"
				. str_repeat("x", $fileSize) . "\r\n--$boundary--\r\n";
			$context = stream_context_create(["http" => [
				"method" => "POST",
				"header" => "Content-Type: multipart/form-data; boundary=$boundary\r\n",
				"content" => $body,
				"ignore_errors" => true,
				"timeout" => 5,
			]]);
			$result = file_get_contents("http://$address/", context: $context);
			$headers = http_get_last_response_headers();
			self::assertStringContainsString(" $expectedStatus ", $headers[0]);
			$data = json_decode($result, true, flags: JSON_THROW_ON_ERROR);

			if($expectedStatus === 413) {
				self::assertSame(PostSizeExceededException::class, $data["exception"]);
				self::assertSame(1024, $data["limitBytes"]);
				self::assertSame(strlen($body), $data["contentLength"]);
				self::assertSame([], $data["post"]);
				self::assertSame([], $data["files"]);
				self::assertStringContainsString("1 KB request limit", $data["message"]);
			}
			elseif($readPostData === "1") {
				self::assertSame("Upload", $data["post"]["submit"]);
				self::assertSame($fileSize, $data["fileSize"]);
			}
			else {
				self::assertSame([], $data["post"]);
				self::assertSame([], $data["files"]);
			}
		}
		finally {
			proc_terminate($sut);
			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($sut);
		}
	}

	public static function postSizes():iterable {
		yield "accepted upload" => [10, "1K", "1", 200];
		yield "rejected request" => [4096, "1K", "1", 413];
		yield "unlimited POST size" => [4096, "0", "1", 200];
		yield "manual body handling" => [4096, "1K", "0", 200];
	}

	private function awaitServer(string $address, mixed $process):void {
		$deadline = microtime(true) + 5;
		do {
			$connection = @stream_socket_client("tcp://$address", timeout: 0.05);
			if($connection !== false) {
				fclose($connection);
				return;
			}
			self::assertTrue(proc_get_status($process)["running"], "PHP server stopped before accepting connections");
			usleep(1000);
		}
		while(microtime(true) < $deadline);
		self::fail("PHP server did not start within five seconds");
	}
}
