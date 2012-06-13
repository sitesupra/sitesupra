<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntityInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Entity\Abstraction\OwnedEntityInterface;
use Doctrine\ORM\PersistentCollection;
use Supra\Controller\Pages\Event\AuditEvents;
use Supra\Database\Entity;
use Supra\Controller\Pages\Entity\PageRevisionData;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Event\PageEventArgs;
use Supra\Controller\Pages\Entity\ReferencedElement\ReferencedElementAbstract;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity\Abstraction\Block;
use Supra\Controller\Pages\Entity\BlockPropertyMetadata;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;

class EntityRevisionSetterListener implements EventSubscriber
{
	
	const pagePreDeleteEvent = 'preDeleteEvent';
	const pagePostDeleteEvent = 'postDeleteEvent';
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;
	
	/**
	 * @var \Doctrine\ORM\UnitOfWork
	 */
	private $uow;
	
	/**
	 * @var array
	 */
	private $visitedEntities = array();
	
	/**
	 * @var boolean
	 */
	private $_pageRestoreState = false;
	
	/**
	 *
	 * @var boolean
	 */
	private $_pageDeleteState = false;
	
	/**
	 * @var Supra\User\Entity\User
	 */
	private $user;
	
	/**
	 * @var string
	 */
	private $referenceId;
	private $revision;
	
	/**
	 * @var string
	 */
	private $revisionInfo;
	

	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
			AuditEvents::pagePreRestoreEvent,
			AuditEvents::pagePostRestoreEvent,
			AuditEvents::pagePreEditEvent,
			AuditEvents::pageContentEditEvent,
			AuditEvents::localizationPreRestoreEvent,
			AuditEvents::localizationPostRestoreEvent,
			
			self::pagePreDeleteEvent,
			self::pagePostDeleteEvent,
		);
	}
	
	/**
	 * Listen all entity insertions and updates performed by Draft entity manager,
	 * update their revision Id
	 * 
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		// to skip revision changes when restoring data from audit
		if ($this->_pageRestoreState) {
			return;
		}
		
		$this->em = $eventArgs->getEntityManager();
		$this->uow = $this->em->getUnitOfWork();
		
		// TODO: temporary, should make another solution
		$userProvider = ObjectRepository::getUserProvider($this, false);
		if ($userProvider instanceof \Supra\User\UserProviderAbstract) {
			$this->user = $userProvider->getSignedInUser();
		}
		
		$this->visitedEntities = array();
		// is it enough with single revision id for inserts and updates?
		//$revisionId = $this->_getRevisionId();
		
		foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
			
			if ( ! ($entity instanceof AuditedEntityInterface)) {
				continue;
			}

			$changeSet = $this->uow->getEntityChangeSet($entity);
			foreach($changeSet as $fieldName => $fieldValue) {

				// skip some cases:
				//   - when changeset contains only associated collection
				//   - when Localization lock/unlock action is performed (lock column value update)
				if ($fieldValue instanceof PersistentCollection
						|| ($entity instanceof Localization && $fieldName == 'lock')
						|| $fieldName == 'revision'
						|| ($fieldValue[0] instanceof \DateTime && $fieldValue[0] == $fieldValue[1])
						|| ($fieldValue[0] instanceof \Supra\Editable\EditableAbstraction && $fieldValue[0] == $fieldValue[1])
						// FIXME: workaround for PageLocalization::resetPath() called on page delete action
						|| $this->_pageDeleteState && $fieldName == 'path') {
					unset($changeSet[$fieldName]);
				}
			}

			if ( ! empty($changeSet)) {
				
				$revision = $this->createRevisionData($entity);
				$revisionId = $revision->getId();
				
				$this->_setRevisionId($entity, $revisionId);
			}
		}
		
		foreach ($this->uow->getScheduledEntityInsertions() as $entity) {

			if ( ! ($entity instanceof AuditedEntityInterface)) {
				continue;
			}
			
			$revision = $this->createRevisionData($entity, PageRevisionData::TYPE_INSERT);
			$revisionId = $revision->getId();
						
			$this->_setRevisionId($entity, $revisionId);
				
		}
	}
	
	/**
	 * Recursively goes througs $entity 'owners' (see getOwner() implementation
	 * for each entity that implements OwnedEntity interface) and sets for all of
	 * them single $newRevisionId
	 * 
	 * @param Entity $entity
	 * @param string $newRevisionId 
	 */
	private function _setRevisionId($entity, $newRevisionId, $nestedCall = false) 
	{
		// helps to avoid useless revision overwriting
		if (in_array(spl_object_hash($entity), $this->visitedEntities)) {
			return;
		}
		
		$oldRevisionId = $entity->getRevisionId();
		$entity->setRevisionId($newRevisionId);
		
		$this->visitedEntities[] = spl_object_hash($entity);
		
		// let know to UoW, that we manually changed entity revision property
		$this->uow->propertyChanged($entity, 'revision', $oldRevisionId, $newRevisionId);
		
		// this will update originalEntityData
		$class = $this->em->getClassMetadata($entity::CN());
		$this->uow->recomputeSingleEntityChangeSet($class, $entity);
				
		if ($nestedCall) {
			// schedule parent entities to update, otherwise they will not be audited by audit listener
			$this->uow->scheduleForUpdate($entity);
		}
		
		// recursively going up to set up revision for entity owners
		if ($entity instanceof OwnedEntityInterface) {
			$parentEntity = $entity->getOwner();
			if ( ! is_null($parentEntity)) {
				$this->_setRevisionId($parentEntity, $newRevisionId, true);
			}
		}
	}
	
	public function pagePreRestoreEvent()
	{
		$this->_pageRestoreState = true;
	}
	
	public function pagePostRestoreEvent()
	{
		 $this->_pageRestoreState = false;
	}
	
	public function preDeleteEvent()
	{
		$this->_pageDeleteState = true;
	}
	
	public function postDeleteEvent()
	{
		$this->_pageDeleteState = false;
	}
	
	public function localizationPreRestoreEvent()
	{
		$this->_pageRestoreState = true;
	}
	
	public function localizationPostRestoreEvent()
	{
		$this->_pageRestoreState = false;
	}
	
	public function pagePreEditEvent(PageEventArgs $eventArgs)
	{
		$this->referenceId = $eventArgs->getProperty('referenceId');
	}
	
	private function createRevisionData($entity, $type = PageRevisionData::TYPE_CHANGE)
	{
		$blockName = null;
		
		// Need to search block title even if revision is created because
		// referenced element might be in the changeset before metadata.
		if (is_null($this->revision) || $this->revision->getElementTitle() === null) {
			$blockName = $this->findBlockName($entity);
		}
		
		if (is_null($this->revision)) {
			
			$em = ObjectRepository::getEntityManager('#public');

			$revision = new PageRevisionData();

			$revision->setElementName($entity::CN());
			$revision->setElementId($entity->getId());

			$revision->setType($type);
			$revision->setReferenceId($this->referenceId);
			$revision->setAdditionalInfo($this->revisionInfo);

			$userId = null;
			if ($this->user instanceof \Supra\User\Entity\User) {
				$userId = $this->user->getId();
			}
			$revision->setUser($userId);

			$em->persist($revision);
			$em->flush();

			$this->revision = $revision;
		}
		
		if ( ! is_null($blockName)) {
			$this->revision->setElementTitle($blockName);
			
			$em = ObjectRepository::getEntityManager('#public');
			$em->flush();
		}

		return $this->revision;
	}
	
	/**
	 * Finds block title by element changed.
	 * Doesn't work for metadata referenced elements, but currently they are
	 * saved togather with metadata elements.
	 * @param mixed $entity
	 * @return string
	 */
	private function findBlockName($entity)
	{
		$block = null;
		
		switch (true) {
			case ($entity instanceof BlockPropertyMetadata):
				$block = $entity->getBlockProperty()
						->getBlock();
				break;
			
			case ($entity instanceof BlockProperty):
				$block = $entity->getBlock();
				break;
			
			case ($entity instanceof Block):
				$block = $entity;
				break;
		}
		
		if (is_null($block)) {
			return;
		}
		
		$blockName = null;
		$entity = null;
		
		if ( ! is_null($block)) {
			$componentClass = $block->getComponentClass();
			$componentConfiguration = ObjectRepository::getComponentConfiguration($componentClass);

			if ($componentConfiguration instanceof BlockControllerConfiguration) {
				$blockName = $componentConfiguration->title;
			}
		}
		
		return $blockName;
	}
	
	/**
	 * @param PageEventArgs $eventArgs
	 */
	public function pageContentEditEvent(PageEventArgs $eventArgs)
	{
		$this->revisionInfo = $eventArgs->getRevisionInfo();
	}

}