<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Supra\Controller\Pages\Entity;
use Supra\Database\Doctrine;
use Supra\Log\Writer\WriterAbstraction;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Simple fixture creation class
 */
class FixtureHelper
{
	/**
	 * @var EntityManager
	 */
	private $entityManager;
	
	/**
	 * @var WriterAbstraction
	 */
	private $log;
	
	protected $headerTemplateBlock;

	protected $rootPage;
	
	protected $template;
	
	public function __construct(\Doctrine\ORM\EntityManager $em)
	{
		$this->log = ObjectRepository::getLogger($this);
		$this->entityManager = $em;
	}
	
	/**
	 * Generates random text
	 * @return string
	 */
	protected function randomText()
	{
		$possibilities = array(
			0 => array(1 => 1, 2 => 1),
			1 => array(1 => 0.3, 2 => 1),
			2 => array(1 => 1, 2 => 0.5),
		);

		$prevType = 0;
		$txt = '';
		$letters = rand(100, 2000);
		$pow = 1;
		for ($i = 0; $i < $letters; null) {
			$chr = \chr(rand(97, 122));
			//\Log::debug("Have chosen $chr");
			if (\in_array($chr, array('e', 'y', 'u', 'i', 'o', 'a'))) {
				$type = 1;
			} else {
				$type = 2;
			}
			//\Log::debug("Type is $type");

			$possibility = $possibilities[$prevType][$type];
			if ($possibility != 1) {
				if ($possibility == 0) {
					continue;
				}
				$possibility = pow($possibility, $pow);
				//\Log::debug("Possibility is $possibility");
				$rand = \rand(0, 100) / 100;
				if ($rand > $possibility) {
					//\Log::debug("Skipping because of no luck");
					continue;
				}
			}

			$txt .= $chr;
			if ($type == $prevType) {
				$pow++;
				//\Log::debug("Increasing power to $pow");
			} else {
				$pow = 1;
				//\Log::debug("Resetting power");
			}
			$prevType = $type;
			$i++;
		}

		$list = array();
		while (strlen($txt) > 10) {
			$length = rand(5, 10);
			$list[] = substr($txt, 0, $length);
			$txt = substr($txt, $length);
		}
		if ( ! empty($txt)) {
			$list[] = $txt;
		}

		$s = array();
		while (count($list) > 0) {
			$length = rand(4, 10);
			$length = min($length, count($list));
			$s[] = \array_splice($list, 0, $length);
		}

		$txt = '<p>';
		foreach ($s as $sentence) {
			$sentence = implode(' ', $sentence);
			$sentence .= '. ';
			if (rand(0, 5) == 1) {
				$sentence .= '</p><p>';
			}
			$sentence = \ucfirst($sentence);
			$txt .= $sentence;
		}
		$txt .= '</p>';

		return $txt;
	}

	public function rebuild()
	{
		$em = $this->entityManager;
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
		$metaDatas = $em->getMetadataFactory()->getAllMetadata();

		$classFilter = function(\Doctrine\ORM\Mapping\ClassMetadata $classMetadata) {
			return (strpos($classMetadata->namespace, 'Supra\Controller\Pages\Entity') === 0);
		};
		$metaDatas = \array_filter($metaDatas, $classFilter);

		$schemaTool->dropSchema($metaDatas);
		$schemaTool->createSchema($metaDatas);
	}

	/**
	 */
	public function build()
	{
		$this->rebuild();

		$em = $this->entityManager;
		
		$this->template = $this->createTemplate();
		
		$rootPage = $this->createPage(0, null, $this->template->getParent());
		$em->persist($rootPage);
		$em->flush();
		$this->rootPage = $rootPage;

		$page = $this->createPage(1, $rootPage, $this->template);
		$em->persist($page);
		$em->flush();

		$page2 = $this->createPage(2, $page, $this->template);
		$em->persist($page2);
		$em->flush();
		
		$publicEm = \Supra\ObjectRepository\ObjectRepository::getEntityManager('');

		foreach (array($this->template->getParent(), $this->template, $rootPage, $page, $page2) as $pageToPublish) {
			
			$this->log->debug("Publishing object $pageToPublish");
			
			$request = new \Supra\Controller\Pages\Request\PageRequestEdit('en_LV', Entity\Layout::MEDIA_SCREEN);
			$request->blockFlushing();
			$request->setDoctrineEntityManager($em);
			$request->setRequestPageData($pageToPublish->getData('en_LV'));
			$request->publish($publicEm);
			
			$em->clear();
			$publicEm->clear();
		}
	}

	protected static $constants = array(
		0 => array(
			'title' => 'Home',
			'pathPart' => '',
		),
		1 => array(
			'title' => 'About',
			'pathPart' => 'about',
		),
		2 => array(
			'title' => 'Contacts',
			'pathPart' => 'contacts',
		),
	);

	protected function createTemplate()
	{
		$template = new Entity\Template();
		$this->entityManager->persist($template);

		$layout = $this->createLayout();
		$template->addLayout('screen', $layout);

		$templateData = new Entity\TemplateData('en_LV');
		$this->entityManager->persist($templateData);
		$templateData->setTemplate($template);
		$templateData->setTitle('Root template');

		foreach (array('header', 'main', 'footer', 'sidebar') as $name) {
			$templatePlaceHolder = new Entity\TemplatePlaceHolder($name);
			$this->entityManager->persist($templatePlaceHolder);
			if ($name == 'header' || $name == 'footer') {
				$templatePlaceHolder->setLocked();
			}
			$templatePlaceHolder->setTemplate($template);

			if ($name == 'header') {
				$block = new Entity\TemplateBlock();
				$this->entityManager->persist($block);
				$block->setComponentClass('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
				$block->setPosition(100);
				$block->setLocale('en_LV');

				// used later in page
				$this->headerTemplateBlock = $block;

				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->entityManager->persist($blockProperty);
				$blockProperty->setBlock($block);
				$blockProperty->setData($template->getData('en_LV'));
				$blockProperty->setValue('Template Header');
			}

			if ($name == 'main') {
				$block = new Entity\TemplateBlock();
				$this->entityManager->persist($block);
				$block->setComponentClass('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
				$block->setPosition(100);
				$block->setLocale('en_LV');

				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->entityManager->persist($blockProperty);
				$blockProperty->setBlock($block);
				$blockProperty->setData($template->getData('en_LV'));
				$blockProperty->setValue('Template source');
				
//				// A locked block
//				$block = new Entity\TemplateBlock();
//				$this->entityManager->persist($block);
//				$block->setComponentClass('Project\Text\TextController');
//				$block->setPlaceHolder($templatePlaceHolder);
//				$block->setPosition(200);
//				$block->setLocked(true);
//				$block->setLocale('en_LV');
//
//				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
//				$this->entityManager->persist($blockProperty);
//				$blockProperty->setBlock($block);
//				$blockProperty->setData($template->getData('en_LV'));
//				$blockProperty->setValue('Template locked block');
			}

			if ($name == 'footer') {
				$block = new Entity\TemplateBlock();
				$this->entityManager->persist($block);
				$block->setComponentClass('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
				$block->setPosition(100);
				$block->setLocale('en_LV');
				$block->setLocked();

				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->entityManager->persist($blockProperty);
				$blockProperty->setBlock($block);
				$blockProperty->setData($template->getData('en_LV'));
				$blockProperty->setValue('Bye <strong>World</strong>!<br />');
			}
			
			if ($name == 'sidebar') {
				$block = new Entity\TemplateBlock();
				$this->entityManager->persist($block);
				$block->setComponentClass('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
				$block->setPosition(100);
				$block->setLocale('en_LV');

				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->entityManager->persist($blockProperty);
				$blockProperty->setBlock($block);
				$blockProperty->setData($template->getData('en_LV'));
				$blockProperty->setValue('<h2>Sidebar</h2><p>' . $this->randomText() . '</p>');
			}
		}
		$this->entityManager->persist($template);
		$this->entityManager->flush();
		
		$childTemplate = new Entity\Template();
		
		$childTemplateData = new Entity\TemplateData('en_LV');
		$this->entityManager->persist($childTemplateData);
		$childTemplateData->setTemplate($childTemplate);
		$childTemplateData->setTitle('Child template');
		
		$templatePlaceHolder = new Entity\TemplatePlaceHolder('sidebar');
		$this->entityManager->persist($templatePlaceHolder);
		$templatePlaceHolder->setTemplate($childTemplate);
		
		$templatePlaceHolder = new Entity\TemplatePlaceHolder('main');
		$this->entityManager->persist($templatePlaceHolder);
		$templatePlaceHolder->setTemplate($childTemplate);
		
		// A locked block
		$block = new Entity\TemplateBlock();
		$this->entityManager->persist($block);
		$block->setComponentClass('Project\Text\TextController');
		$block->setPlaceHolder($templatePlaceHolder);
		$block->setPosition(200);
		$block->setLocale('en_LV');
		$block->setLocked(true);

		$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
		$this->entityManager->persist($blockProperty);
		$blockProperty->setBlock($block);
		$blockProperty->setData($childTemplateData);
		$blockProperty->setValue('<h2>Template locked block</h2>');
		
		$this->entityManager->persist($childTemplate);
		$childTemplate->moveAsLastChildOf($template);
		$this->entityManager->flush();
		
		return $childTemplate;
	}

	protected function createLayout()
	{
		$layout = new Entity\Layout();
		$this->entityManager->persist($layout);
		$layout->setFile('root.html');

		foreach (array('header', 'main', 'footer', 'sidebar') as $name) {
			$layoutPlaceHolder = new Entity\LayoutPlaceHolder($name);
			$layoutPlaceHolder->setLayout($layout);
		}
		return $layout;
	}

	protected function createPage($type = 0, Entity\Page $parentNode = null, Entity\Template $template = null)
	{
		$page = new Entity\Page();
		$this->entityManager->persist($page);

		if ( ! is_null($parentNode)) {
			$parentNode->addChild($page);
		}
		$this->entityManager->flush();

		$pageData = new Entity\PageData('en_LV');
		$pageData->setTemplate($template);
		$this->entityManager->persist($pageData);
		$pageData->setTitle(self::$constants[$type]['title']);

		$pageData->setPage($page);

		$this->entityManager->flush();
		
		// Path is generated on updates ONLY!
		$pageData->setPathPart(self::$constants[$type]['pathPart']);
		$this->entityManager->flush();

		foreach (array('header', 'main', 'footer') as $name) {

			if ($name == 'header') {
				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->entityManager->persist($blockProperty);
				$blockProperty->setBlock($this->headerTemplateBlock);
				$blockProperty->setData($page->getData('en_LV'));
				$blockProperty->setValue('<h1>Hello SiteSupra in page /' . $pageData->getPath() . '</h1>');
				
				$placeHolder = new Entity\PagePlaceHolder('header');
				$this->entityManager->persist($placeHolder);
				$placeHolder->setMaster($page);
				
				$block = new Entity\PageBlock();
				$this->entityManager->persist($block);
				$block->setComponentClass('Project\Text\TextController');
				$block->setPlaceHolder($placeHolder);
				$block->setPosition(0);
				$block->setLocale('en_LV');
				
				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->entityManager->persist($blockProperty);
				$blockProperty->setBlock($block);
				$blockProperty->setData($pageData);
				$blockProperty->setValue('this shouldn\'t be shown');
			}

			if ($name == 'main') {
				$pagePlaceHolder = new Entity\PagePlaceHolder($name);
				$this->entityManager->persist($pagePlaceHolder);
				$pagePlaceHolder->setPage($page);

				foreach (\range(1, 2) as $i) {
					$block = new Entity\PageBlock();
					$this->entityManager->persist($block);
					$block->setComponentClass('Project\Text\TextController');
					$block->setPlaceHolder($pagePlaceHolder);
					// reverse order
					$block->setPosition(100 * $i);
					$block->setLocale('en_LV');

					$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
					$this->entityManager->persist($blockProperty);
					$blockProperty->setBlock($block);
					$blockProperty->setData($page->getData('en_LV'));
					$blockProperty->setValue('<h2>Section Nr ' . $i . '</h2><p>' . $this->randomText() . '</p>');
				}
			}

		}

		return $page;
	}
}
