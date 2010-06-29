<?php

namespace Supra\Proxy;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class SupraControllerPagesPagePlaceHolderProxy extends \Supra\Controller\Pages\PagePlaceHolder implements \Doctrine\ORM\Proxy\Proxy
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

    
    public function setPage(\Supra\Controller\Pages\Page $page)
    {
        $this->_load();
        return parent::setPage($page);
    }

    public function getPage()
    {
        $this->_load();
        return parent::getPage();
    }


    public function __sleep()
    {
        if (!$this->__isInitialized__) {
            throw new \RuntimeException("Not fully loaded proxy can not be serialized.");
        }
        return array('page', 'id', 'layoutPlaceHolderName', 'locked');
    }
}