<?php

namespace Supra\FileStorage;

use Supra\FileStorage\Entity\Abstraction\File;
use Supra\Event\EventArgs;

class FileEventArgs extends EventArgs
{
	const FILE_EVENT_FILE_SELECTED = 'fileSelected';
	const FILE_EVENT_PRE_DELETE = 'preFileDelete';
	const FILE_EVENT_POST_DELETE = 'postFileDelete';
	const FILE_EVENT_PRE_RENAME = 'preFileRename';
	const FILE_EVENT_POST_RENAME = 'postFileRename';
	
	/**
	 * @var File
	 */
	protected $file;

	/**
	 * @param File $file 
	 */
	public function setFile(File $file)
	{
		$this->file = $file;
	}

	/**
	 * @return File
	 */
	public function getFile()
	{
		return $this->file;
	}

}
