<?php

namespace Supra\Proxy\History;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class SupraUserEntityAbstractionUserProxy extends \Supra\User\Entity\Abstraction\User implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }
    
    
    public function getName()
    {
        $this->__load();
        return parent::getName();
    }

    public function setName($name)
    {
        $this->__load();
        return parent::setName($name);
    }

    public function getCreationTime()
    {
        $this->__load();
        return parent::getCreatedTime();
    }

    public function setCreationTime()
    {
        $this->__load();
        return parent::setCreatedTime();
    }

    public function getModificationTime()
    {
        $this->__load();
        return parent::getModifiedTime();
    }

    public function setModificationTime()
    {
        $this->__load();
        return parent::setModifiedTime();
    }

    public function getId()
    {
        $this->__load();
        return parent::getId();
    }

    public function equals(\Supra\Database\Entity $entity)
    {
        $this->__load();
        return parent::equals($entity);
    }

    public function __toString()
    {
        $this->__load();
        return parent::__toString();
    }

    public function getProperty($name)
    {
        $this->__load();
        return parent::getProperty($name);
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'name', 'createdTime', 'modifiedTime', 'id');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields AS $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        
    }
}