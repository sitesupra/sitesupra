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

namespace Supra\Package\Cms\Entity;

/**
 * @Entity(readOnly=true)
 */
class EditLock extends Abstraction\Entity implements Abstraction\TimestampableInterface
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $userName;

	/**
	 * @Column(type="datetime", nullable=true)
	 * @var \DateTime
	 */
	protected $creationTime;
	
	/**
	 * @Column(type="datetime", nullable=true)
	 * @var \DateTime
	 */
	protected $modificationTime;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $localizationRevision;

	/**
	 * Returns creation time.
	 *
	 * @return \DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}
	
	/**
	 * Sets creation time.
	 * 
	 * @param \DateTime $time
	 */
	public function setCreationTime(\DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new \DateTime();
		}
		$this->creationTime = $time;
	}

	/**
	 * Returns last modification time.
	 * 
	 * @return \DateTime
	 */
	public function getModificationTime()
	{
		return $this->modificationTime;
	}

	/**
	 * Sets modification time.
	 * 
	 * @param \DateTime $time
	 */
	public function setModificationTime(\DateTime $time = null)
	{
		$this->modificationTime = $time ? $time : new \DateTime();
	}

	/**
	 * Returns username of user who created lock.
	 * 
	 * @return string
	 */
	public function getUserName()
	{
		return $this->userName;
	}

	/**
	 * @param string $userName
	 */
	public function setUserName($userName)
	{
		$this->userName = $userName;
	}

	/**
	 * Returns localization revision on the lock creation moment.
	 *
	 * @return string
	 */
	public function getLocalizationRevision()
	{
		return $this->localizationRevision;
	}

	/**
	 * @param int $localizationRevision
	 */
	public function setLocalizationRevision($localizationRevision)
	{
		$this->localizationRevision = $localizationRevision;
	}
}
