<?php

namespace Supra\Controller\Pages\Request;

use Supra\Request\HttpRequest;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Controller\Pages\Entity\Abstraction\Page;

/**
 * Page controller request object on view method
 */
class PageRequestView extends PageRequest
{
	/**
	 * @param HttpRequest $request
	 */
	public function __construct(HttpRequest $request)
	{
		// Not nice but functional method to downcast the request object
		foreach ($request as $field => $value) {
			$this->$field = $value;
		}
		
		$localeManager = ObjectRepository::getLocaleManager($this);
		$localeId = $localeManager->getCurrent()->getId();
		$this->setLocale($localeId);
	}
	
	/**
	 * Overriden with page detection
	 * @return Page
	 */
	public function getRequestPageData()
	{
		$data = parent::getRequestPageData();
		
		if (empty($data)) {
			$data = $this->detectRequestPageData();
			
			$this->setRequestPageData($data);
		}

		return $data;
	}
	
	/**
	 * @return Page
	 */
	protected function detectRequestPageData()
	{
		$action = $this->getActionString();
		$action = trim($action, '/');

		$em = $this->getDoctrineEntityManager();
		$er = $em->getRepository(static::PAGE_DATA_ENTITY);

		$searchCriteria = array(
			'locale' => $this->getLocale(),
			'path' => $action,
		);

		//TODO: think about "enable path params" feature

		/* @var $page Entity\PageData */
		$pageData = $er->findOneBy($searchCriteria);

		if (empty($pageData)) {
			//TODO: 404 page

			// for better exception message presentation
			if(empty($action)) {
				$action = '/';
			}

			throw new ResourceNotFoundException("No page found by path '$action' in pages controller");
		}

		return $pageData;
	}
}
