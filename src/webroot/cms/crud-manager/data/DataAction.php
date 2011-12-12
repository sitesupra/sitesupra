<?php

namespace Supra\Cms\CrudManager\Data;

use Supra\Cms\CrudManager\CrudManagerAbstractAction;
use Supra\Editable;
use Supra\ObjectRepository\ObjectRepository;

class DataAction extends CrudManagerAbstractAction
{

	public function datalistAction()
	{
		$resultsPerRequest = $this->getRequest()->getParameter('resultsPerRequest');
		if ( ! is_numeric($resultsPerRequest)) {
			$resultsPerRequest = 40;
		}

		$offset = $this->getRequest()->getParameter('offset');
		if ( ! is_numeric($offset)) {
			$offset = 0;
		}
		
		$configuration = ObjectRepository::getApplicationConfiguration($this);
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository($configuration->entity);
		
		$order = null;
		if($repo->isSortable()){
			$order = array(
				'position' => 'asc'
			);
		}
		
		$results = $repo->findBy(array(), $order, $resultsPerRequest, $offset);
		
		$query = $em->createQuery("SELECT COUNT(e) as totalCount FROM {$configuration->entity} e");
		$queryResult = $query->getResult();
		$totalCount = $queryResult[0]['totalCount'];

		$data = array();
		foreach ($results as $result) {
			$data[] = $result->getEditValues();
		}

		$output = array(
			'offset' => $offset,
			'total' => $totalCount,
			'results' => $data,
		);

		$response = $this->getResponse();
		$response->setResponseData($output);
	}

	public function configurationAction()
	{
		$configuration = ObjectRepository::getApplicationConfiguration($this);
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository($configuration->entity);
		/* @var $repo Gjensidige\Branches\Repository\BranchesCrudRepository */

		$entityParts = explode('\\', $configuration->entity);
		$managerId = mb_strtolower(end($entityParts));

		$attributes = array(
			'id' => $managerId,
			'title' => $configuration->title,
			'delete' => $repo->isDeletable(),
			'create' => $repo->isCreatable(),
			'sortable' => $repo->isSortable(),
			'locale' => $repo->isLocalized(),
		);

		$fieldObjects = $repo->getEditableFields();
		$fields = array();
		
		foreach ($fieldObjects as $key => $fieldObject) {

			$data = array(
				'label' => $fieldObject->getLabel(),
				'type' => $fieldObject->getEditorType(),
			);

			if ($fieldObject instanceof Editable\Select) {

				$content = $fieldObject->getContent();
				$defaultValue = $fieldObject->getDefaultValue();

				$data['values'] = $content;

				if ( ! empty($defaultValue)) {
					$data['value'] = $defaultValue;
				}
			}

			$fields[$key] = $data;
		}

		$output = array(
			array(
				'attributes' => $attributes,
				'fields' => $fields,
				'ui_list' => array_keys($repo->getListFields()),
				'ui_edit' => array_keys($fields),
				'lists' => array(),
			)
		);

		$response = $this->getResponse();
		$response->setResponseData($output);
	}

}