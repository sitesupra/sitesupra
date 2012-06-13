<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Controller\Pages\BlockControllerCollection;
use Supra\Loader\Loader;
use Supra\Configuration\ConfigurationInterface;
use Supra\Configuration\ComponentConfiguration;
use Symfony\Component\Form;
use \ReflectionClass;
use Supra\Form\FormField;
use Symfony\Component\Validator\Constraint;

class FormBlockControllerConfiguration extends BlockControllerConfiguration
{
	const FORM_GROUP_ID_ERROR = 'form_errors';
	const FORM_GROUP_LABEL_ERROR = 'Form error messages';

	const FORM_GROUP_ID_LABELS = 'form_labels';
	const FORM_GROUP_LABEL_LABELS = 'Form field lables';

	const BLOCK_PROPERTY_FORM_PREFIX = 'form_field_';

	/**
	 * @var array 
	 */
	public $constraints;

	/**
	 * @var array 
	 */
	public $fields;

	/**
	 * @var \Symfony\Component\Form\Form
	 */
	public $form;

	public function configure()
	{
		// processing annotations
		$this->fields = $formFields = $this->processAnnotations();

		// configuring field groups: labels and errors
		if ( ! empty($formFields)) {
			// groups 
			$groups = array(
				self::FORM_GROUP_ID_ERROR => self::FORM_GROUP_LABEL_ERROR,
				self::FORM_GROUP_ID_LABELS => self::FORM_GROUP_LABEL_LABELS,
			);

			foreach ($groups as $key => $value) {
				$group = new BlockPropertyGroupConfiguration();
				$group->id = $key;
				$group->type = 'sidebar';
				$group->label = $value;

				$group = $group->configure();

				if ($group instanceof BlockPropertyGroupConfiguration) {
					$this->propertyGroups[$group->id] = $group;
				}
			}
		}

		// processing fields
		foreach ($formFields as $field) {
			/* @var $field FormField */

			$messages = array();

			/**
			 * mapping messages
			 */
			foreach ($field->getConstraints() as $constraint) {
				/* @var $constraint Constraint */
				foreach ($constraint->propertyMessages as $property => $originalMessage) {
					$messages[$constraint->$property] = $originalMessage;
				}
			}

			/**
			 * adding labels to form block property list
			 */
			// splitting camelCase into words
			$labelParts = preg_split('/(?=[A-Z])/', $field->getName());
			$fieldLabel = ucfirst(mb_strtolower(join(' ', $labelParts)));

			$blockProperty = new BlockPropertyConfiguration();
			$editable = new \Supra\Editable\String("Field \"{$fieldLabel}\" label");

			$editable->setDefaultValue($fieldLabel);

//			$editable->setGroupId(self::FORM_GROUP_ID_LABELS);

			$editableName = static::generateEditableName(self::FORM_GROUP_ID_LABELS, $field->getName());
			$this->properties[] = $blockProperty->fillFromEditable($editable, $editableName);

			/**
			 * adding errors to form block property list
			 */
			$i = 1;
			foreach ($messages as $key => $value) {
				$blockProperty = new BlockPropertyConfiguration();
				$editable = new \Supra\Editable\String("Field \"$fieldLabel\" error #{$i}");
				$editable->setDefaultValue($value);

//				$editable->setGroupId(self::FORM_GROUP_ID_LABELS);

				$editableName = static::generateEditableName(self::FORM_GROUP_ID_ERROR, $field->getName()) . '_' . $key;
				$this->properties[] = $blockProperty->fillFromEditable($editable, $editableName);
				$i ++;
			}
		}

		parent::configure();
	}

	/**
	 * Generates editable name
	 * 
	 * @param string $propertyGroup
	 * @param FormFieldConfiguration or string $field 
	 * @throws \RuntimeException if $propertyGroup is not on of FORM_GROUP_ID constants
	 * @return string 
	 */
	public static function generateEditableName($propertyGroup, $field)
	{
		if ( ! in_array($propertyGroup, array(self::FORM_GROUP_ID_ERROR, self::FORM_GROUP_ID_LABELS))) {
			throw new \RuntimeException('');
		}

		if ($field instanceof FormField) {
			return self::BLOCK_PROPERTY_FORM_PREFIX . $propertyGroup . '_' . $field->getName();
		} else {
			return self::BLOCK_PROPERTY_FORM_PREFIX . $propertyGroup . '_' . (string) $field;
		}
	}

	/**
	 * Reads Form Entity class and returns all annotation classes in array of FormField objects
	 * @return array of FormField objects
	 */
	public function processAnnotations()
	{
		// creating reflection
		$reader = new \Doctrine\Common\Annotations\AnnotationReader();
		$reflection = new ReflectionClass($this->form);

		$annotations = array();

		// gathering property annotations
		foreach ($reflection->getProperties() as $property) {
			/* @var $property ReflectionProperty */
			$formField = false;

			$propertyAnnotations = $reader->getPropertyAnnotations($property);

			// gathering FormFields and unsetting not Constraint Annotations
			foreach ($propertyAnnotations as $key => $annotation) {
				if ($annotation instanceof FormField) {
					$annotation->setName($property->getName());
					$annotations[$property->getName()] = $annotation;
					unset($propertyAnnotations[$key]);
					continue;
				}

				if ( ! $annotation instanceof Constraint) {
					unset($propertyAnnotations[$key]);
				}
			}

			$formField = $annotations[$property->getName()];

			if ( ! $formField instanceof FormField) {
				continue;
			}

			if ( ! empty($propertyAnnotations)) {
				$formField->addConstraints(array_values($propertyAnnotations));
			}
		}

		return $annotations;
	}

}