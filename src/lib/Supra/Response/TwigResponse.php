<?php

namespace Supra\Response;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Template\Parser\Twig\Twig;
use Supra\Template\Parser\Twig\Loader\FilesystemLoaderByContext;

/**
 * Response based on Twig template parser
 */
class TwigResponse extends HttpResponse
{

	/**
	 * @var array
	 */
	protected $templateVariables = array();

	/**
	 * @var mixed
	 */
	protected $loaderContext;

	/**
	 * @var Twig
	 */
	protected $twigEnvironment;

	/**
	 * @var mixed
	 */
	protected $loader;

	/**
	 * Can set context classname or object to search for the templates there
	 * @param mixed $loaderContext
	 */
	public function __construct($loaderContext = null)
	{
		parent::__construct();

		$this->loaderContext = $loaderContext;
		$this->twigEnvironment = ObjectRepository::getTemplateParser($this);

		if ( ! $this->twigEnvironment instanceof Twig) {
			throw new Exception\IncompatibleObject("Twig response object must have Twig template parser assigned");
		}
	}

	public function __sleep()
	{
		return parent::__sleep();
	}

	/**
	 * Override loader context
	 * @param mixed $loaderContext
	 */
	public function setLoaderContext($loaderContext = null)
	{
		$this->loaderContext = $loaderContext;
	}

	public function setLoader($loader)
	{
		$this->loader = $loader;
	}

	/**
	 * @return Twig
	 */
	public function getTwigEnvironment()
	{
		return $this->twigEnvironment;
	}

	/**
	 * Output the template
	 * @param string $templateName
	 */
	public function outputTemplate($templateName)
	{
		$loader = null;

		if ( ! is_null($this->loader)) {
			$loader = $this->loader;
		} else {
			
			if ( ! is_null($this->loaderContext)) {
				$loader = new FilesystemLoaderByContext($this->loaderContext, $this->twigEnvironment->getLoader());
			}
		}
		$content = $this->twigEnvironment->parseTemplate($templateName, $this->templateVariables, $loader);

		$this->output($content);
		
		$file = $this->twigEnvironment->getTemplateFilename($templateName, $loader);
		$this->addResourceFile($file);
	}

	/**
	 * Assign parameter for the Twig template
	 * @param string $name
	 * @param mixed $value
	 * @return TwigResponse
	 */
	public function assign($name, $value = null)
	{
		if (is_array($name)) {
			$this->templateVariables = array_merge($this->templateVariables, $name);
		} else {
			$this->templateVariables[$name] = $value;
		}

		return $this;
	}

	/**
	 * Get assigned template data
	 * @return array
	 */
	public function getTemplateVariables()
	{
		return $this->templateVariables;
	}
	
}
