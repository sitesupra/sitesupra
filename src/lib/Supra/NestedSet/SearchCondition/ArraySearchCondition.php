<?php

namespace Supra\NestedSet\SearchCondition;

use Supra\NestedSet\Node\NodeInterface,
		Supra\NestedSet\Exception;

/**
 * 
 */
class ArraySearchCondition extends SearchConditionAbstraction
{
	public function getSearchClosure()
	{
		$conditions = $this->conditions;
		$filter = function (NodeInterface $node) use (&$conditions) {
			foreach ($conditions as $condition) {
				$field = $condition[ArraySearchCondition::FIELD_POS];
				switch ($field) {
					case SearchConditionAbstraction::LEFT_FIELD:
						$testValue = $node->getLeftValue();
						break;
					case SearchConditionAbstraction::RIGHT_FIELD:
						$testValue = $node->getRightValue();
						break;
					case SearchConditionAbstraction::LEVEL_FIELD:
						$testValue = $node->getLevel();
						break;
					default:
						throw new Exception\InvalidOperation("Field $field is not recognized");
				}
				$relation = $condition[ArraySearchCondition::RELATION_POS];
				$value = $condition[ArraySearchCondition::VALUE_POS];
				switch ($relation) {
					case SearchConditionAbstraction::RELATION_EQUALS:
						$result = ($testValue == $value);
						break;
					case SearchConditionAbstraction::RELATION_LESS_OR_EQUALS:
						$result = ($testValue <= $value);
						break;
					case SearchConditionAbstraction::RELATION_MORE_OR_EQUALS:
						$result = ($testValue >= $value);
						break;
					case SearchConditionAbstraction::RELATION_LESS:
						$result = ($testValue < $value);
						break;
					case SearchConditionAbstraction::RELATION_MORE:
						$result = ($testValue > $value);
						break;
					case SearchConditionAbstraction::RELATION_NOT_EQUALS:
						$result = ($testValue != $value);
						break;
					default:
						throw new Exception\InvalidOperation("Relation $relation is not recognized");
				}
				if ( ! $result) {
					return false;
				}
			}
			return true;
		};

		return $filter;
	}
}