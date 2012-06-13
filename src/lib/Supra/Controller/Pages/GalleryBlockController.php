<?php

namespace Supra\Controller\Pages;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Editable\EditableInterface;
use Supra\Editable;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Dummy controller to fetch subproperties of specified metadata
 */
class GalleryBlockController extends BlockController
{
	
	/**
	 * @var BlockPropertyMetadata
	 */
	protected $metadata;
	
	/**
	 * @var BlockPropertyConfiguration
	 */
	protected $propertyConfiguration;
		

	public function getProperty($name)
	{
		$parentProperty = $this->metadata->getBlockProperty();
		$parentName = $parentProperty->getName();

		$parentDefinition = $this->configuration->getProperty($parentName);
		foreach($parentDefinition->properties as $property) {
			if ($property->name === $name) {
				$propertyDefinition = $property;
				break;
			}
		}

		if ( ! isset($propertyDefinition)) {
			throw new Exception\RuntimeException("Content '{$name}' is not defined for block ");
		}

		$editable = $propertyDefinition->editableInstance;
		if ( ! $editable instanceof EditableInterface) {
			throw new Exception\RuntimeException("Definition of property must be an instance of editable");
		}

		// Find property by name
		$property = null;
		$expectedType = get_class($editable);
		
		$existentPropertyCollection = $this->metadata->getMetadataProperties();

		foreach ($existentPropertyCollection as $propertyCheck) {
			/* @var $propertyCheck BlockProperty */
			/* @var $property BlockProperty */
			if ($propertyCheck->getName() === $name) {

				if ($propertyCheck->getType() === $expectedType) {
					$property = $propertyCheck;
					break;
				}
			}
		}

		/*
		 * Must create new property here
		 */
		if (empty($property)) {
			$property = new Entity\BlockProperty($name);
			$property->setEditable($editable);
			$property->setValue($editable->getDefaultValue());
			$property->setBlock($parentProperty->getBlock());
			$property->setLocalization($parentProperty->getLocalization());
			
			$property->setMasterMetadata($this->metadata);
			
			$existentPropertyCollection->add($property);
		}

		$editable = $property->getEditable();

		//TODO: do this some way better..
		$this->configureContentFilters($property, $editable);
		
		return $property;
	
	}
	
	public function setParentMetadata($metadata)
	{
		$this->metadata = $metadata;
		
		// self preparing
		$this->page = $this->metadata->getBlockProperty()
				->getLocalization()
				->getMaster();
		
		$block = $this->metadata->getBlockProperty()
				->getBlock();
		
		$controllerCollection = BlockControllerCollection::getInstance();
		
		// original gallery controller
		$controller = $controllerCollection->getBlockController($block->getComponentClass());
		$this->configuration = $controller->getConfiguration();
		
		$parentProperty = $this->metadata->getBlockProperty();
		$this->propertyConfiguration = $this->configuration->getProperty($parentProperty->getName());
		
		$this->request = $controller->getRequest();
		
	}
	
	/**
	 * @param type $request
	 */
	public function setRequest($request)
	{
		$this->request = $request;
	}
	
	/**
	 * @return BlockPropertyConfiguration
	 */
	public function getConfiguration()
	{
		return $this->propertyConfiguration;
	}
}