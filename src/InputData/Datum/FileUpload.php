<?php
namespace GT\Input\InputData\Datum;

use Gt\Http\Stream;
use GT\Input\UploadedFileMoveException;
use GT\Input\UploadedFileSecurityException;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use SplFileInfo;
use TypeError;

class FileUpload extends InputDatum implements UploadedFileInterface {
	protected string $originalFileName;
	protected string $mimeType;
	protected int $fileSize;
	protected string $tempFilePath;
	protected bool $moved;

	public function __construct(
		string $originalFilename,
		string $mimeType,
		int $fileSize,
		string $tempFilePath
	) {
		$this->originalFileName = $originalFilename;
		$this->mimeType = $mimeType;
		$this->fileSize = $fileSize;
		$this->tempFilePath = $tempFilePath;
		$this->moved = false;

		parent::__construct($originalFilename);
	}

	public function getFileInfo():SplFileInfo {
		return new SplFileInfo($this->tempFilePath);
	}

	public function getRealPath():string {
		return $this->tempFilePath;
	}

	public function getOriginalName():string {
		return $this->originalFileName;
	}

	public function getOriginalExtension():string {
		return pathinfo($this->originalFileName, PATHINFO_EXTENSION);
	}

	public function getSize():int {
		return $this->fileSize;
	}

	public function getMimeType():string {
		return $this->mimeType;
	}

	/** @inheritDoc */
	public function getStream():StreamInterface {
		if($this->moved) {
			throw new StreamNotAvailableException("File has been moved");
		}
		return new Stream($this->getRealPath());
	}

	/** @inheritDoc */
	public function moveTo(string $targetPath):void {
		if(!is_uploaded_file($this->tempFilePath)) {
			throw new UploadedFileSecurityException($this->tempFilePath);
		}

		$targetPath = str_replace(
			["/", "\\"],
			DIRECTORY_SEPARATOR,
			$targetPath
		);

		if(!is_dir(dirname($targetPath))) {
			mkdir(dirname($targetPath), 0775, true);
		}

		$success = move_uploaded_file(
			$this->tempFilePath,
			$targetPath
		);

		if(!$success) {
			throw new UploadedFileMoveException($this->tempFilePath);
		}

		$this->moved = true;
	}

	/** @inheritDoc */
	public function getError():int {
// Note that this class ALWAYS returns UPLOAD_ERR_OK, due to failed uploads
// being represented by another class, FailedFileUpload.
		return UPLOAD_ERR_OK;
	}

	/** @inheritDoc */
	public function getClientFilename():?string {
		return $this->originalFileName;
	}

	/** @inheritDoc */
	public function getClientMediaType():?string {
		return $this->getMimeType();
	}
}
