<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Controller\Pages\BlockControllerCollection;
use Supra\Loader\Loader;
use Supra\Configuration\ConfigurationInterface;

/**
 * Block configuration class
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class BlockControllerConfiguration implements ConfigurationInterface
{

	/**
	 * Autogenerated from block classname if isn't set manually
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * Group id 
	 * @var string
	 */
	public $groupId = null;
	
	/**
	 * @var string
	 */
	public $description;

	/**
	 * Local icon path
	 * @var string
	 */
	public $icon;

	/**
	 * Full icon web path, autogenerated if empty
	 * @var string
	 */
	public $iconWebPath = '/cms/lib/supra/img/blocks/icons-items/default.png';

	/**
	 * CMS classname for the block
	 * @var string
	 */
	public $cmsClassname = 'Editable';

	/**
	 * Block controller class name
	 * @var string
	 */
	public $controllerClass;

	/**
	 * Should be block hidden from block menu or not
	 * @var boolean
	 */
	public $hidden = false;
	
	
	/**
	 * Block HTML description
	 * @var string
	 */
	public $html;
	
	/**
	 * Cache implementation
	 * @var BlockControllerCacheConfiguration
	 */
	public $cache;	
	
	/**
	 * Adds block configuration to block controller collection
	 */
	public function configure()
	{
		if (empty($this->id)) {
			$id = str_replace('\\', '_', $this->controllerClass);
			$this->id = $id;
		}

		if ( ! empty($this->icon)) {
			$this->iconWebPath = $this->getIconWebPath();
		}

		BlockControllerCollection::getInstance()
				->addBlockConfiguration($this);
	}

	/**
	 * Return icon webpath
	 * @return string
	 */
	private function getIconWebPath()
	{
		$file = Loader::getInstance()->findClassPath($this->controllerClass);
		$dir = dirname($file);
		$iconPath = $dir . '/' . $this->icon;

//		// Disabled for performance
//		if ( ! file_exists($iconPath)) {
//			$iconPath = null;
//		} else
		
		if (strpos($iconPath, SUPRA_WEBROOT_PATH) !== 0) {
			$iconPath = null;
		} else {
			$iconPath = substr($iconPath, strlen(SUPRA_WEBROOT_PATH) - 1);
		}

		return $iconPath;
	}

}
