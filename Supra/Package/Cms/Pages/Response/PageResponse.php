<?php

namespace Supra\Package\Cms\Pages\Response;

use Symfony\Component\HttpFoundation\Response;

class PageResponse extends Response
{
	/**
	 * @var ResponseContext
	 */
	protected $context;

	/**
	 * @return ResponseContext
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * @param ResponseContext $context
	 */
	public function setContext(ResponseContext $context)
	{
		$this->context = $context;
	}
}