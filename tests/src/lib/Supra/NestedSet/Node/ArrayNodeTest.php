<?php

namespace Supra\Tests\NestedSet\Node;

use Supra\Tests\NestedSet\Fixture,
		Supra\NestedSet\ArrayRepository,
		Supra\NestedSet\Node\ArrayNode;

/**
 * Test class for ArrayNode.
 * Generated by PHPUnit on 2010-08-10 at 14:41:40.
 */
class ArrayNodeTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var ArrayRepository
	 */
	protected $repository;

	/**
	 * @var ArrayNode
	 */
	protected $food;

	/**
	 * @var ArrayNode
	 */
	protected $beef;

	/**
	 * @var ArrayNode
	 */
	protected $yellow;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$rep = Fixture\NestedSet::foodTree();
		$this->food = $rep->byTitle('Food');
		$this->beef = $rep->byTitle('Beef');
		$this->yellow = $rep->byTitle('Yellow');
		$this->repository = $rep;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {

	}

	/**
	 */
	public function testSetTitle() {
		$this->food->setTitle('Yam-yam');
		self::assertEquals('Yam-yam', $this->food->getTitle());
	}

	/**
	 */
	public function testGetTitle() {
		self::assertEquals('Food', $this->food->getTitle());
		self::assertEquals('Beef', $this->beef->getTitle());
		self::assertEquals('Yellow', $this->yellow->getTitle());
	}

	/**
	 */
	public function testSetRepository() {
		$rep = new ArrayRepository();
		$this->food->setRepository($rep);
		self::assertEquals($rep, $this->food->getRepository());
	}

	/**
	 */
	public function testGetLeftValue() {
		self::assertEquals(0, $this->food->getLeftValue());
		self::assertEquals(12, $this->beef->getLeftValue());
		self::assertEquals(6, $this->yellow->getLeftValue());
	}

	/**
	 */
	public function testGetRightValue() {
		self::assertEquals(17, $this->food->getRightValue());
		self::assertEquals(13, $this->beef->getRightValue());
		self::assertEquals(9, $this->yellow->getRightValue());
	}

	/**
	 */
	public function testAddChild() {
		$badBeef = $this->repository->createNode('Bad Beef');
		$this->beef->addChild($badBeef);

		$output = $this->repository->drawTree();

		$expected = <<<DOC
(0; 19) 0 Food
  (1; 10) 1 Fruit
    (2; 5) 2 Red
      (3; 4) 3 Cherry
    (6; 9) 2 Yellow
      (7; 8) 3 Banana
  (11; 18) 1 Meat
    (12; 15) 2 Beef
      (13; 14) 3 Bad Beef
    (16; 17) 2 Pork

DOC;

		self::assertEquals(<<<DOC
(0; 19) 0 Food
  (1; 10) 1 Fruit
    (2; 5) 2 Red
      (3; 4) 3 Cherry
    (6; 9) 2 Yellow
      (7; 8) 3 Banana
  (11; 18) 1 Meat
    (12; 15) 2 Beef
      (13; 14) 3 Bad Beef
    (16; 17) 2 Pork

DOC
				, $output);
	}

	/**
	 */
	public function testDelete() {
		$this->yellow->delete();

		$output = $this->repository->drawTree();
		self::assertEquals(<<<DOC
(0; 13) 0 Food
  (1; 6) 1 Fruit
    (2; 5) 2 Red
      (3; 4) 3 Cherry
  (7; 12) 1 Meat
    (8; 9) 2 Beef
    (10; 11) 2 Pork

DOC
				, $output);

		$this->beef->delete();
		$output = $this->repository->drawTree();
		self::assertEquals(<<<DOC
(0; 11) 0 Food
  (1; 6) 1 Fruit
    (2; 5) 2 Red
      (3; 4) 3 Cherry
  (7; 10) 1 Meat
    (8; 9) 2 Pork

DOC
				, $output);
		
		$this->food->delete();
		$output = $this->repository->drawTree();
		self::assertEquals('', $output);
	}

	/**
	 */
	public function testGetAncestors() {
		self::assertEquals(array(), $this->food->getAncestors());
		self::assertEquals(1, count($this->food->getAncestors(0, true)));
		self::assertEquals(2, count($this->yellow->getAncestors()));
		self::assertEquals(1, count($this->yellow->getAncestors(1)));

		$nodes = $this->yellow->getAncestors();
		self::assertEquals('Fruit', $nodes[0]->getTitle());
		self::assertEquals('Food', $nodes[1]->getTitle());
	}

	/**
	 */
	public function testGetDescendants() {
		self::assertEquals(array(), $this->beef->getDescendants());
		self::assertEquals(1, count($this->beef->getDescendants(0, true)));
		self::assertEquals(8, count($this->food->getDescendants()));
		self::assertEquals(3, count($this->food->getDescendants(1, true)));

		$nodes = $this->yellow->getDescendants();
		self::assertEquals('Banana', $nodes[0]->getTitle());
		$nodes = $this->yellow->getDescendants(0, true);
		self::assertEquals('Yellow', $nodes[0]->getTitle());
		self::assertEquals('Banana', $nodes[1]->getTitle());
	}

	/**
	 */
	public function testGetFirstChild() {
		$child = $this->food->getFirstChild();
		self::assertEquals('Fruit', $child->getTitle());

		$child = $this->yellow->getFirstChild();
		self::assertEquals('Banana', $child->getTitle());
		
		$child = $this->beef->getFirstChild();
		self::assertEquals(null, $child);
	}

	/**
	 */
	public function testGetLastChild() {
		$child = $this->food->getLastChild();
		self::assertEquals('Meat', $child->getTitle());

		$child = $this->yellow->getLastChild();
		self::assertEquals('Banana', $child->getTitle());

		$child = $this->beef->getLastChild();
		self::assertEquals(null, $child);
	}

	/**
	 */
	public function testHasNextSibling() {
		self::assertEquals(false, $this->food->hasNextSibling());
		self::assertEquals(true, $this->beef->hasNextSibling());
		self::assertEquals(false, $this->yellow->hasNextSibling());
	}

	/**
	 */
	public function testHasPrevSibling() {
		self::assertEquals(false, $this->food->hasPrevSibling());
		self::assertEquals(false, $this->beef->hasPrevSibling());
		self::assertEquals(true, $this->yellow->hasPrevSibling());
	}

	/**
	 */
	public function testGetNextSibling() {
		self::assertEquals(null, $this->food->getNextSibling());
		self::assertNotNull($this->beef->getNextSibling());
		self::assertEquals('Pork', $this->beef->getNextSibling()->getTitle());
		self::assertEquals(null, $this->yellow->getNextSibling());
	}

	/**
	 */
	public function testGetPrevSibling() {
		self::assertEquals(null, $this->food->getPrevSibling());
		self::assertEquals(null, $this->beef->getPrevSibling());
		self::assertNotNull($this->yellow->getPrevSibling());
		self::assertEquals('Red', $this->yellow->getPrevSibling()->getTitle());
	}

	/**
	 */
	public function testGetNumberChildren() {
		self::assertEquals(2, $this->food->getNumberChildren());
		self::assertEquals(0, $this->beef->getNumberChildren());
		self::assertEquals(1, $this->yellow->getNumberChildren());
	}

	/**
	 */
	public function testGetNumberDescendants() {
		self::assertEquals(8, $this->food->getNumberDescendants());
		self::assertEquals(0, $this->beef->getNumberDescendants());
		self::assertEquals(1, $this->yellow->getNumberDescendants());
	}

	/**
	 */
	public function testHasParent() {
		self::assertEquals(false, $this->food->hasParent());
		self::assertEquals(true, $this->beef->hasParent());
		self::assertEquals(true, $this->yellow->hasParent());
	}

	/**
	 */
	public function testGetParent() {
		self::assertEquals(null, $this->food->getParent());
		self::assertEquals('Meat', $this->beef->getParent()->getTitle());
		self::assertEquals('Fruit', $this->yellow->getParent()->getTitle());
	}

	/**
	 */
	public function testGetPath() {
		self::assertEquals('Food', $this->food->getPath());
		self::assertEquals('Food X Meat X Beef', $this->beef->getPath(' X '));
		self::assertEquals('Food > Fruit', $this->yellow->getPath(' > ', false));
	}

	/**
	 */
	public function testGetChildren() {
		$children = $this->food->getChildren();
		self::assertEquals(2, count($children));
		self::assertEquals('Fruit', $children[0]->getTitle());
		self::assertEquals('Meat', $children[1]->getTitle());
		
		$children = $this->yellow->getChildren();
		self::assertEquals(1, count($children));
		self::assertEquals('Banana', $children[0]->getTitle());
		
		$children = $this->beef->getChildren();
		self::assertEquals(0, count($children));
	}

	/**
	 */
	public function testGetSiblings() {
		$siblings = $this->food->getSiblings();
		self::assertEquals(1, count($siblings));
		self::assertEquals('Food', $siblings[0]->getTitle());
		
		$siblings = $this->food->getSiblings(false);
		self::assertEquals(0, count($siblings));

		$siblings = $this->yellow->getSiblings();
		self::assertEquals(2, count($siblings));
		self::assertEquals('Red', $siblings[0]->getTitle());
		self::assertEquals('Yellow', $siblings[1]->getTitle());
		
		$siblings = $this->yellow->getSiblings(false);
		self::assertEquals(1, count($siblings));
		self::assertEquals('Red', $siblings[0]->getTitle());

		$siblings = $this->beef->getSiblings();
		self::assertEquals(2, count($siblings));
		self::assertEquals('Beef', $siblings[0]->getTitle());
		self::assertEquals('Pork', $siblings[1]->getTitle());

		$siblings = $this->beef->getSiblings(false);
		self::assertEquals(1, count($siblings));
		self::assertEquals('Pork', $siblings[0]->getTitle());

	}

	/**
	 */
	public function testGetLevel() {
		self::assertEquals(0, $this->food->getLevel());
		self::assertEquals(2, $this->yellow->getLevel());
		self::assertEquals(2, $this->beef->getLevel());
	}

	/**
	 */
	public function testHasChildren() {
		self::assertEquals(true, $this->food->hasChildren());
		self::assertEquals(true, $this->yellow->hasChildren());
		self::assertEquals(false, $this->beef->hasChildren());
	}

	/**
	 */
	public function testMoveAsNextSiblingOf() {
		try {
			$this->food->moveAsNextSiblingOf($this->yellow);
			self::fail("Shoudln't be able to move parent after it's child");
		} catch (\Exception $e) {}

		$this->beef->moveAsNextSiblingOf($this->yellow);
		$output = $this->repository->drawTree();

		self::assertEquals('(0; 17) 0 Food
  (1; 12) 1 Fruit
    (2; 5) 2 Red
      (3; 4) 3 Cherry
    (6; 9) 2 Yellow
      (7; 8) 3 Banana
    (10; 11) 2 Beef
  (13; 16) 1 Meat
    (14; 15) 2 Pork
', $output);
	}

	/**
	 */
	public function testMoveAsPrevSiblingOf() {
		try {
			$this->food->moveAsPrevSiblingOf($this->yellow);
			self::fail("Shoudln't be able to move parent before it's child");
		} catch (\Exception $e) {}

		$this->yellow->moveAsPrevSiblingOf($this->food);
		$output = $this->repository->drawTree();

		self::assertEquals('(0; 3) 0 Yellow
  (1; 2) 1 Banana
(4; 17) 0 Food
  (5; 10) 1 Fruit
    (6; 9) 2 Red
      (7; 8) 3 Cherry
  (11; 16) 1 Meat
    (12; 13) 2 Beef
    (14; 15) 2 Pork
', $output);
	}

	/**
	 */
	public function testMoveAsFirstChildOf() {
		try {
			$this->food->moveAsFirstChildOf($this->yellow);
			self::fail("Shoudln't be able to move parent under it's child");
		} catch (\Exception $e) {}

		$this->yellow->moveAsFirstChildOf($this->food);
		$output = $this->repository->drawTree();

		self::assertEquals('(0; 17) 0 Food
  (1; 4) 1 Yellow
    (2; 3) 2 Banana
  (5; 10) 1 Fruit
    (6; 9) 2 Red
      (7; 8) 3 Cherry
  (11; 16) 1 Meat
    (12; 13) 2 Beef
    (14; 15) 2 Pork
', $output);
	}

	/**
	 */
	public function testMoveAsLastChildOf() {
		try {
			$this->food->moveAsLastChildOf($this->yellow);
			self::fail("Shoudln't be able to move parent under it's child");
		} catch (\Exception $e) {}

		$this->yellow->moveAsLastChildOf($this->food);
		$output = $this->repository->drawTree();

		self::assertEquals('(0; 17) 0 Food
  (1; 6) 1 Fruit
    (2; 5) 2 Red
      (3; 4) 3 Cherry
  (7; 12) 1 Meat
    (8; 9) 2 Beef
    (10; 11) 2 Pork
  (13; 16) 1 Yellow
    (14; 15) 2 Banana
', $output);
	}

	/**
	 */
	public function testIsLeaf() {
		self::assertEquals(false, $this->food->isLeaf());
		self::assertEquals(false, $this->yellow->isLeaf());
		self::assertEquals(true, $this->beef->isLeaf());
	}

	/**
	 */
	public function testIsRoot() {
		self::assertEquals(true, $this->food->isRoot());
		self::assertEquals(false, $this->yellow->isRoot());
		self::assertEquals(false, $this->beef->isRoot());
	}

	/**
	 */
	public function testIsAncestorOf() {
		self::assertEquals(true, $this->food->isAncestorOf($this->yellow));
		self::assertEquals(false, $this->beef->isAncestorOf($this->yellow));
		self::assertEquals(false, $this->beef->isAncestorOf($this->food));
		self::assertEquals(true, $this->food->isAncestorOf($this->beef));
		self::assertEquals(false, $this->food->isAncestorOf($this->food));
	}

	/**
	 */
	public function testIsDescendantOf() {
		self::assertEquals(false, $this->food->isDescendantOf($this->yellow));
		self::assertEquals(false, $this->beef->isDescendantOf($this->yellow));
		self::assertEquals(true, $this->beef->isDescendantOf($this->food));
		self::assertEquals(false, $this->food->isDescendantOf($this->beef));
		self::assertEquals(false, $this->food->isDescendantOf($this->food));
	}

	/**
	 */
	public function testIsEqualTo() {
		self::assertEquals(false, $this->food->isEqualTo($this->yellow));
		self::assertEquals(true, $this->food->isEqualTo($this->yellow->getParent()->getParent()));
	}

}
