<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Entity\Abstraction;

use Doctrine\Common\Collections;
use Supra\Core\NestedSet;
use Supra\Core\NestedSet\Exception\BadMethodCall;
use Supra\Core\NestedSet\Node\DoctrineNode;
use Supra\Core\NestedSet\Node\EntityNodeInterface;
use Supra\Package\Cms\Entity\FilePath;
use Supra\Package\Cms\Entity\FileProperty;

/**
 * File abstraction
 * @Entity(repositoryClass="Supra\Package\Cms\Repository\FileNestedSetRepository")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"file" = "Supra\Package\Cms\Entity\File", "folder" = "Supra\Package\Cms\Entity\Folder", "image" = "Supra\Package\Cms\Entity\Image"})
 * @Table(name="file_abstraction", indexes={
 * 		@index(name="file_abstraction_lft_idx", columns={"lft"}),
 * 		@index(name="file_abstraction_rgt_idx", columns={"rgt"}),
 * 		@index(name="file_abstraction_lvl_idx", columns={"lvl"})
 * })
 * @method int getNumberChildren()
 * @method NestedSet\Node\NodeAbstraction addChild(NestedSet\Node\NodeInterface $child)
 * @method void delete()
 * @method boolean hasNextSibling()
 * @method boolean hasPrevSibling()
 * @method int getNumberDescendants()
 * @method boolean hasParent()
 * @method NestedSet\Node\NodeAbstraction getParent()
 * @method array getAncestors(int $levelLimit, boolean $includeNode)
 * @method array getDescendants($levelLimit = 0, $includeNode = false)
 * @method NestedSet\Node\NodeAbstraction getFirstChild()
 * @method NestedSet\Node\NodeAbstraction getLastChild()
 * @method NestedSet\Node\NodeAbstraction getNextSibling()
 * @method NestedSet\Node\NodeAbstraction getPrevSibling()
 * @method array getChildren()
 * @method array getSiblings(boolean $includeNode)
 * @method boolean hasChildren()
 * @method NestedSet\Node\NodeAbstraction moveAsNextSiblingOf(NestedSet\Node\NodeInterface $afterNode)
 * @method NestedSet\Node\NodeAbstraction moveAsPrevSiblingOf(NestedSet\Node\NodeInterface $beforeNode)
 * @method NestedSet\Node\NodeAbstraction moveAsFirstChildOf(NestedSet\Node\NodeInterface $parentNode)
 * @method NestedSet\Node\NodeAbstraction moveAsLastChildOf(NestedSet\Node\NodeInterface $parentNode)
 * @method boolean isLeaf()
 * @method boolean isRoot()
 * @method boolean isAncestorOf(NestedSet\Node\NodeInterface $node)
 * @method boolean isDescendantOf(NestedSet\Node\NodeInterface $node)
 * @method boolean isEqualTo(NestedSet\Node\NodeInterface $node)
 */
abstract class File extends Entity implements EntityNodeInterface, TimestampableInterface
{
	const PERMISSION_UPLOAD_NAME = 'file_upload';
	const PERMISSION_UPLOAD_MASK = 256;
	const PERMISSION_DELETE_NAME = 'file_delete';
	const PERMISSION_DELETE_MASK = 512;

	/**
	 * Integer object type ID
	 */
	const TYPE_ID = 0;

	/**
	 * @var NestedSet\Node\DoctrineNode
	 */
	protected $nestedSetNode;

	/**
	 * @Column(type="integer", name="lft", nullable=true)
	 * @var integer
	 */
	protected $left;

	/**
	 * @Column(type="integer", name="rgt", nullable=true)
	 * @var integer
	 */
	protected $right;

	/**
	 * @Column(type="integer", name="lvl", nullable=true)
	 * @var integer
	 */
	protected $level;

	/**
	 * @Column(type="string", name="file_name", nullable=false)
	 * @var string
	 */
	protected $fileName;

	/**
	 * @Column(type="datetime", name="created_at")
	 * @var \DateTime
	 */
	protected $creationTime;

	/**
	 * @Column(type="datetime", name="modified_at")
	 * @var \DateTime
	 */
	protected $modificationTime;

	/**
	 * @Column(type="boolean", name="public")
	 * @var integer
	 */
	protected $public = true;

	/**
	 * @var string
	 */
	protected $originalFileName;

	/**
	 * @OneToOne(targetEntity="Supra\Package\Cms\Entity\FilePath", cascade={"remove", "persist", "merge"}, fetch="EAGER")
	 * @var FilePath
	 */
	protected $path;
	
	/**
	 * Custom property collection
	 * 
	 * @OneToMany(targetEntity="Supra\Package\Cms\Entity\FileProperty", mappedBy="file", cascade={"all"}, indexBy="name")
	 * @var Collections\Collection
	 */
	protected $properties;

	/**
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->properties = new Collections\ArrayCollection;
	}
	
	/**
	 * @return FilePath 
	 */
	public function getPathEntity()
	{
		if (is_null($this->path)) {
			$this->path = new FilePath();
			$this->path->setId($this->getId());
		}

		return $this->path;
	}

	/**
	 * @param FilePath $pathEntity
	 */
	public function setPathEntity(FilePath $pathEntity)
	{
		$this->path = $pathEntity;
	}

	/**
	 * Returns path 
	 * @param string $separator
	 * @param boolean $includeNode
	 * @return string
	 */
	public function getPath($separator = '/', $includeNode = true)
	{
		$filePath = $this->getPathEntity();

		if ( ! $filePath instanceof FilePath) {
			\Log::warn("File: {$this->getFileName()} ({$this->getId()}) has empty path. Run regenerate command.");
			return null;
		}

		$path = $filePath->getSystemPath();
		$pathParts = explode(DIRECTORY_SEPARATOR, $path);

		if ( ! $includeNode) {
			array_pop($pathParts);
		}

		$path = implode($separator, $pathParts);

		return $path;
	}

	/**
	 * Get left value
	 * @return int
	 */
	public function getLeftValue()
	{
		return $this->left;
	}

	/**
	 * Get right value
	 * @return int
	 */
	public function getRightValue()
	{
		return $this->right;
	}

	/**
	 * Get depth level
	 * @return int
	 */
	public function getLevel()
	{
		return $this->level;
	}

	/**
	 * Returns creation time
	 * @return \DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}

	/**
	 * Sets creation time
	 * @param \DateTime $time
	 */
	public function setCreationTime(\DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new \DateTime('now');
		}
		$this->creationTime = $time;
	}

	/**
	 * Returns last modification time
	 * @return \DateTime
	 */
	public function getModificationTime()
	{
		return $this->modificationTime;
	}

	/**
	 * Sets modification time
	 * @param \DateTime $time
	 */
	public function setModificationTime(\DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new \DateTime('now');
		}
		$this->modificationTime = $time;
	}

	/**
	 * Set left value
	 * @param int $left
	 * @return File
	 */
	public function setLeftValue($left)
	{
		$this->left = $left;
		if (isset($this->nestedSetNode)) {
			$this->nestedSetNode->setLeftValue($left);
		}
		return $this;
	}

	/**
	 * Set right value
	 * @param int $right
	 * @return File
	 */
	public function setRightValue($right)
	{
		$this->right = $right;
		if (isset($this->nestedSetNode)) {
			$this->nestedSetNode->setRightValue($right);
		}
		return $this;
	}

	/**
	 * Set depth level
	 * @param int $level
	 * @return File
	 */
	public function setLevel($level)
	{
		$this->level = $level;

		if (isset($this->nestedSetNode)) {
			$this->nestedSetNode->setLevel($level);
		}
		return $this;
	}

	/**
	 * Move left value by the difference
	 * @param int $diff
	 * @return File
	 */
	public function moveLeftValue($diff)
	{
		$this->left += $diff;
		if (isset($this->nestedSetNode)) {
			$this->nestedSetNode->moveLeftValue($diff);
		}
		return $this;
	}

	/**
	 * Move right value by the difference
	 * @param int $diff
	 * @return File
	 */
	public function moveRightValue($diff)
	{
		$this->right += $diff;
		if (isset($this->nestedSetNode)) {
			$this->nestedSetNode->moveRightValue($diff);
		}
		return $this;
	}

	/**
	 * Move depth level by the difference
	 * @param int $diff
	 * @return File
	 */
	public function moveLevel($diff)
	{
		$this->level += $diff;

		if (isset($this->nestedSetNode)) {
			$this->nestedSetNode->moveLevel($diff);
		}
		return $this;
	}

	/**
	 * Nested node title
	 * @return string
	 */
	public function getNodeTitle()
	{
		return $this->__toString();
	}

	/**
	 * Try the unknown method against the nested set node
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($method, $arguments)
	{
		$node = $this->nestedSetNode;
		if (is_null($this->nestedSetNode)) {
			throw new BadMethodCall("Method $method does not exist for class " . __CLASS__ . " and it's node object is not initialized. Try persisting object first.");
		}

		if ( ! method_exists($node, $method)) {
			throw new BadMethodCall("Method $method does not exist for class " . __CLASS__ . " and it's node object.");
		}
		$callable = array($node, $method);
		$result = call_user_func_array($callable, $arguments);

		// Compare the result with $node and return $this on match to keep method chaining
		if ($result === $node) {
			$result = $this;
		}

		return $result;
	}

	/**
	 * So the full path generation method of the file would use the cloned and changed entity
	 */
	public function __clone()
	{
		if ( ! empty($this->id)) {

			// Don't regenerate path. The "clone" functionality is used for
			// keeping track of OLD and NEW objects when modifying.
//			$this->regenerateId();

			// Don't need to clone nested set node as well
//			if ( ! empty($this->nestedSetNode)) {
//				$this->nestedSetNode = clone($this->nestedSetNode);
//				$this->nestedSetNode->belongsTo($this);
//			}

			$this->path = clone($this->path);
		}
	}

	/**
	 * Free the node unsetting the pointers to other objects.
	 * MUST clear entity manager after doing this!
	 */
	public function free()
	{
		if ( ! is_null($this->nestedSetNode)) {
			$this->nestedSetNode->free($this);
			$this->nestedSetNode = null;
		}
	}

	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getNestedSetRepositoryClassName()
	{
		// One nested set repository for folders, files, images
		$className = __CLASS__;

		return $className;
	}

	/**
	 * @param string $fileName
	 */
	public function setFileName($fileName)
	{		
		$fileName = trim(preg_replace('/\s+/i', ' ', $fileName));
	
		if ( ! is_null($this->fileName)) {
			$this->originalFileName = $this->fileName;
		}

		$this->fileName = $fileName;
	}

	/**
	 * Returns file title / file name
	 * 
	 * @return string
	 */
	public function getFileName()
	{
		return $this->fileName;
	}
	
	/**
	 * Returns the file filename in filesystem
	 * 
	 * @return string
	 */
	public function getRealFileName()
	{
		$path = $this->path->getSystemPath();
		
		$pathParts = explode('/', $path);
		
		return array_pop($pathParts);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getFileName();
	}

	/**
	 * Get public state
	 *
	 * @return boolean
	 */
	public function isPublic()
	{
		return $this->public;
	}

	/**
	 * Set public state
	 *
	 * @param boolean $public 
	 */
	public function setPublic($public)
	{
		$this->public = $public;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setNestedSetNode(DoctrineNode $nestedSetNode)
	{
		$this->nestedSetNode = $nestedSetNode;
	}

	/**
	 * {@inheritdoc}
	 * @return NestedSet\Node\DoctrineNode
	 */
	public function getNestedSetNode()
	{
		return $this->nestedSetNode;
	}

	/**
	 * Loads item info array
	 * @param string $locale
	 * @return array
	 */
	public function getInfo($locale = null)
	{
		$info = array(
			'id' => $this->getId(),
			'filename' => $this->getFileName(),
			'type' => static::TYPE_ID,
			'created' => $this->getCreationTime()->format('c'),
			'modified' => $this->getModificationTime()->format('c'),
		);

		return $info;
	}

	/**
	 * @return string
	 */
	public static function getAlias()
	{
		return 'file';
	}

	/**
	 * Used to improve audit log readability
	 */
	public function getOriginalTitle()
	{
		return $this->originalFileName;
	}

	/**
	 * Wrapper
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getFileName();
	}
	
	/**
	 * @param FileProperty $fileProperty
	 */
	public function addCustomProperty(FileProperty $fileProperty)
	{
		$name = $fileProperty->getName();

		if ($this->properties->containsKey($name)) {
			// non unique exception
			throw new \RuntimeException("Property with name {$name} already exists in collection");
		}
	}
	
	/**
	 * @return Collections\Collection
	 */
	public function getCustomProperties()
	{
		return $this->properties;
	}
}
