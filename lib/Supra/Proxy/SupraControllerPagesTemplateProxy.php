<?php

namespace Supra\Proxy;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class SupraControllerPagesTemplateProxy extends \Supra\Controller\Pages\Template implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    private function _load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister);
            unset($this->_identifier);
        }
    }

    
    public function setParent(\Supra\Controller\Pages\PageAbstraction $parent = NULL)
    {
        $this->_load();
        return parent::setParent($parent);
    }

    public function addTemplateLayout(\Supra\Controller\Pages\TemplateLayout $templateLayout)
    {
        $this->_load();
        return parent::addTemplateLayout($templateLayout);
    }

    public function getTemplateLayouts()
    {
        $this->_load();
        return parent::getTemplateLayouts();
    }

    public function addLayout($media, \Supra\Controller\Pages\Layout $layout)
    {
        $this->_load();
        return parent::addLayout($media, $layout);
    }

    public function removeLayout($media)
    {
        $this->_load();
        return parent::removeLayout($media);
    }

    public function getId()
    {
        $this->_load();
        return parent::getId();
    }

    public function getParent()
    {
        $this->_load();
        return parent::getParent();
    }

    public function getChildren()
    {
        $this->_load();
        return parent::getChildren();
    }

    public function getPlaceHolders()
    {
        $this->_load();
        return parent::getPlaceHolders();
    }

    public function getData()
    {
        $this->_load();
        return parent::getData();
    }

    public function setData($locale, \Supra\Controller\Pages\PageDataAbstraction $data)
    {
        $this->_load();
        return parent::setData($locale, $data);
    }

    public function removeData($locale)
    {
        $this->_load();
        return parent::removeData($locale);
    }


    public function __sleep()
    {
        if (!$this->__isInitialized__) {
            throw new \RuntimeException("Not fully loaded proxy can not be serialized.");
        }
        return array('data', 'templateLayouts', 'children', 'parent', 'placeHolders', 'id');
    }
}