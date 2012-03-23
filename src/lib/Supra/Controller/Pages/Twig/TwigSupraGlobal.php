<?php

namespace Supra\Controller\Pages\Twig;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;
use Supra\Request\RequestInterface;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Uri\Path;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Response\ResponseContext;
use Supra\Html\HtmlTag;

/**
 * Helper object for twig processor
 */
class TwigSupraGlobal
{

	/**
	 * @var RequestInterface
	 */
	protected $request;

	/**
	 * @var ResponseContext
	 */
	protected $responseContext;

	/**
	 * @return RequestInterface
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @return ResponseContext
	 */
	public function getResponseContext()
	{
		return $this->responseContext;
	}

	/**
	 * @param RequestInterface $request
	 */
	public function setRequest(RequestInterface $request = null)
	{
		$this->request = $request;
	}

	/**
	 * @param ResponseContext $responseContext 
	 */
	public function setResponseContext(ResponseContext $responseContext = null)
	{
		$this->responseContext = $responseContext;
	}

	/**
	 * Returns if in CMS mode
	 * @return boolean
	 */
	public function isCmsRequest()
	{
		return ($this->request instanceof PageRequestEdit);
	}

	/**
	 * Whether the passed link is actual - is some descendant opened currently
	 * @param string $path
	 * @param boolean $strict
	 * @return boolean
	 */
	public function isActive($path, $strict = false)
	{
		// Check if path is relative
		$pathData = parse_url($path);
		if ( ! empty($pathData['scheme'])
				|| ! empty($pathData['host'])
				|| ! empty($pathData['port'])
				|| ! empty($pathData['user'])
				|| ! empty($pathData['pass'])
		) {
			return false;
		}

		$path = $pathData['path'];

		$localization = $this->getLocalization();

		if ( ! $localization instanceof PageLocalization) {
			return false;
		}

		// Remove locale prefix
		$localeId = $localization->getLocale();
		$localeIdQuoted = preg_quote($localeId);
		$path = preg_replace('#^(/?)' . $localeIdQuoted . '(/|$)#', '$1', $path);

		$checkPath = new Path($path);
		$currentPath = $localization->getPath();

		if (is_null($currentPath)) {
			return false;
		}

		if ($strict) {
			if ($checkPath->equals($currentPath)) {
				return true;
			}
		} elseif ($currentPath->startsWith($checkPath)) {
			return true;
		}

		return false;
	}
	
	/**
	 * @return Localization
	 */
	public function getLocalization()
	{
		$request = $this->request;

		if ( ! $request instanceof PageRequest) {
			return;
		}

		$localization = $request->getPageLocalization();

		if ( ! $localization instanceof Localization) {
			return;
		}
		
		return $localization;
	}

	/**
	 * @return Locale
	 */
	public function getLocale()
	{
		$locale = ObjectRepository::getLocaleManager($this)
				->getCurrent();

		return $locale;
	}
	
	/**
	 * @return Info
	 */
	public function getInfo()
	{
		$info = ObjectRepository::getSystemInfo($this);
		
		return $info;
	}
	
	/**
	 * Try getting 1) from request 2) from system settings
	 * @return string
	 */
	public function getHost()
	{
		// From request
		if ($this->request instanceof \Supra\Request\HttpRequest) {
			$fromRequest = $this->request->getBaseUrl();
			
			if ( ! empty($fromRequest)) {
				return $fromRequest;
			}
		}
		
		// From info package
		return $this->getInfo()
				->getHostName(\Supra\Info::WITH_SCHEME);
	}
	
	/**
	 * Generates page title tag with class name CMS would recognize
	 * @param string $tagName
	 * @return HtmlTag
	 */
	public function pageTitleHtmlTag($tagName = 'span')
	{
		$localization = $this->getLocalization();
		
		if (is_null($localization)) {
			return;
		}
		
		$title = $localization->getTitle();
		$htmlTag = new HtmlTag($tagName, $title);
		
		if ($this->isCmsRequest()) {
			$htmlTag->addClass('su-settings-title');
		}
		
		return $htmlTag;
	}

}
