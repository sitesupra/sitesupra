<?php

namespace Supra\Controller\Pages;

/**
 * Template place holder class
 * @Entity
 * @Table(name="layout_place_holder")
 */
class LayoutPlaceHolder extends EntityAbstraction
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $id;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @ManyToOne(targetEntity="Layout", inversedBy="placeHolders")
	 * @var Layout
	 */
	protected $layout;

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set name
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Get name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Get layout
	 * @param Layout $layout
	 */
	public function setLayout(Layout $layout)
	{
		$this->layout = $layout;
	}

	/**
	 * Set layout
	 * @return Layout
	 */
	public function getLayout()
	{
		return $this->layout;
	}
}