<?php

namespace Supra\Controller\Layout\Processor;

use Supra\Response\ResponseInterface,
		Supra\Controller\Pages\Entity\Layout;

/**
 * Simple layout processor
 */
class Html implements ProcessorInterface
{
	/**
	 * Place holder function name
	 */
	const PLACE_HOLDER = 'placeHolder';

	/**
	 * Maximum layout file size
	 */
	const FILE_SIZE_LIMIT = 1000000;

	/**
	 * Allowed macro functions
	 * @var array
	 */
	static protected $macroFunctions = array(
		self::PLACE_HOLDER,
	);

	/**
	 * Layout root dir
	 * @var string
	 */
	protected $layoutDir;

	/**
	 * @var string
	 */
	protected $startDelimiter = '<!--';

	/**
	 * @var string
	 */
	protected $endDelimiter = '-->';

	/**
	 * Process the layout
	 * @param ResponseInterface $response
	 * @param array $placeResponses
	 * @param string $layout
	 */
	public function process(ResponseInterface $response, array $placeResponses, $layoutSrc)
	{
		
		// Output CDATA
		$cdataCallback = function($cdata) use ($response) {
			$response->output($cdata);
		};

		// Flush place holder responses into master response
		$macroCallback = function($func, array $args) use (&$response, &$placeResponses) {
			if ($func == Html::PLACE_HOLDER) {
				if ( ! \array_key_exists(0, $args) || $args[0] == '') {
					throw new Exception("No placeholder name defined in the placeHolder macro in template ");
				}

				$place = $args[0];

				if (isset($placeResponses[$place])) {
					/* @var $placeResponse ResponseInterface */
					$placeResponse = $placeResponses[$place];
					$placeResponse->flushToResponse($response);
				}
			}
		};

		$this->walk($layoutSrc, $cdataCallback, $macroCallback);

		//throw new \Exception("Not implemented yet");
	}

	/**
	 * Return list of place names inside the layout
	 * @param string $layout
	 * @return array
	 */
	public function getPlaces($layoutSrc)
	{
		$places = array();

		// Ignore CDATA
		$cdataCallback = function($cdata) {};

		// Collect place holders
		$macroCallback = function($func, array $args) use (&$places) {
			if ($func == Html::PLACE_HOLDER) {
				if ( ! \array_key_exists(0, $args) || $args[0] == '') {
					throw new Exception("No placeholder name defined in the placeHolder macro in template ");
				}
				$places[] = $args[0];
			}
		};

		$this->walk($layoutSrc, $cdataCallback, $macroCallback);

		return $places;
	}

	/**
	 * @param string $layoutSrc
	 * @return string
	 * @throws Exception when file or security issue is raised
	 */
	protected function getContent($layoutSrc)
	{
		$filename = $this->getLayoutDir() . \DIRECTORY_SEPARATOR . $layoutSrc;
		if ( ! \is_file($filename)) {
			throw new Exception("File '$layoutSrc' was not found");
		}
		if ( ! \is_readable($filename)) {
			throw new Exception("File '$layoutSrc' is not readable");
		}
		
		// security stuff
		$this->securityCheck($filename);

		return \file_get_contents($filename);
	}

	/**
	 * @param string $filename
	 * @throws Exception if security issue is found
	 */
	protected function securityCheck($filename)
	{
		if (preg_match('!(^|/|\\\\)\.\.($|/|\\\\)!', $filename)) {
			throw new Exception("Security error for '$filename': Layout filename cannot contain '..' part");
		}
		if (\filesize($filename) > self::FILE_SIZE_LIMIT) {
			throw new Exception("Security error for '$filename': Layout file size cannot exceed " . self::FILE_SIZE_LIMIT . ' bytes');
		}
	}

	protected function macroExists($name)
	{
		$exists = \in_array($name, static::$macroFunctions);
		return $exists;
	}

	protected function walk($layoutSrc, \Closure $cdataCallback, \Closure $macroCallback)
	{
		$layoutContent = $this->getContent($layoutSrc);

		$startDelimiter = $this->getStartDelimiter();
		$startLength = strlen($startDelimiter);
		$endDelimiter = $this->getEndDelimiter();
		$endLength = strlen($endDelimiter);

		do {
			$pos = strpos($layoutContent, $startDelimiter);
			if ($pos !== false) {
				$cdataCallback(substr($layoutContent, 0, $pos));
				$layoutContent = substr($layoutContent, $pos);
				$pos = strpos($layoutContent, $endDelimiter);
				if ($pos === false) {
					break;
				}

				$macroString = substr($layoutContent, $startLength, $pos - $startLength);
				$macro = trim($macroString);
				if ( ! \preg_match('!^(.*)\((.*)\)$!', $macro, $macroInfo)) {
					$cdataCallback(substr($layoutContent, 0, $startLength));
					$layoutContent = substr($layoutContent, $startLength);
					continue;
				}

				$macroFunction = trim($macroInfo[1]);
				$macroArguments = explode(',', $macroInfo[2]);
				$macroArguments = \array_map('trim', $macroArguments);

				if ( ! $this->macroExists($macroFunction)) {
					$cdataCallback(substr($layoutContent, 0, $startLength));
					$layoutContent = substr($layoutContent, $startLength);
					continue;
				}

				$macroCallback($macroFunction, $macroArguments);
				
				// remove the used data
				$layoutContent = substr($layoutContent, $pos + $endLength);
			}
		} while ($pos !== false);

		$cdataCallback($layoutContent);
	}

	/**
	 * Set layout root dir
	 * @param string $layoutDir
	 */
	public function setLayoutDir($layoutDir)
	{
		$this->layoutDir = $layoutDir;
	}

	/**
	 * Get layout root dir
	 * @return string
	 */
	public function getLayoutDir()
	{
		return $this->layoutDir;
	}

	/**
	 * @return string
	 */
	public function getStartDelimiter()
	{
		return $this->startDelimiter;
	}

	/**
	 * @param string $startDelimiter
	 */
	public function setStartDelimiter($startDelimiter)
	{
		$this->startDelimiter = $startDelimiter;
	}

	/**
	 * @return string
	 */
	public function getEndDelimiter()
	{
		return $this->endDelimiter;
	}

	/**
	 * @param string $endDelimiter
	 */
	public function setEndDelimiter($endDelimiter)
	{
		$this->endDelimiter = $endDelimiter;
	}

}