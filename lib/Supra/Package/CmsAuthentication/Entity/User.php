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

namespace Supra\Package\CmsAuthentication\Entity;

use Supra\Package\Cms\Entity\Abstraction\TimestampableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User object
 * @Entity(repositoryClass="UserRepository")
 * @Table(name="user")
 */
class User extends AbstractUser implements UserInterface, TimestampableInterface
{

	/**
	 * @Column(type="string", name="password", nullable=true)
	 * @var string
	 */
	protected $password;

	/**
	 * @Column(type="string", name="login", nullable=false, unique=true)
	 * @var string
	 */
	protected $login;

	/**
	 * @Column(type="string", name="email", nullable=false, unique=true)
	 * @var string
	 */
	protected $email;

	/**
	 * @Column(type="boolean", name="email_confirmed", nullable=true)
	 * @var boolean
	 */
	protected $emailConfirmed;

	/**
	 * @ManyToOne(targetEntity="Group", fetch="EAGER")
	 * @JoinColumn(name="group_id", referencedColumnName="id")
	 */
	protected $group;

	/**
	 * @Column(type="datetime", name="last_login_at", nullable=true)
	 * @var \DateTime
	 */
	protected $lastLoginTime;

	/**
	 * @Column(type="boolean", name="active")
	 * @var boolean
	 */
	protected $active = true;

	/**
	 * @Column(type="string", nullable=false, length=23)
	 * @var string
	 */
	protected $salt;

	/**
	 * @Column(type="string", name="status", nullable=true)
	 * @var string
	 */
	protected $status;

	/**
	 * @Column(type="array")
	 * @var array
	 */
	protected $roles = array();

	/**
	 * Generates random salt for new users
	 */
	public function __construct()
	{
		parent::__construct();

		$this->resetSalt();
	}

	/**
	 * @param array $roles
	 */
	public function setRoles($roles)
	{
		$this->roles = $roles;
	}

	/**
	 * @return array
	 */
	public function getRoles()
	{
		return $this->roles;
	}

	/**
	 * See UserInterface
	 */
	public function eraseCredentials()
	{
		$this->roles = array();
	}

	/**
	 * Alias for UserInterface
	 *
	 * @return string
	 */
	public function getUsername()
	{
		return $this->getLogin();
	}

	/**
	 * Returns user password
	 * @return string
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * Sets user password
	 * @param string $password 
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}

	/**
	 * @return string
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * @param string $login 
	 */
	public function setLogin($login)
	{
		$this->login = $login;
	}

	/**
	 * Returns user email 
	 * @return string 
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * Sets user email
	 * @param string $email 
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * Returns user last logged in time 
	 * @return \DateTime 
	 */
	public function getLastLoginTime()
	{
		return $this->lastLoginTime;
	}

	/**
	 * Sets user last logged in time 
	 * @param \DateTime $time
	 */
	public function setLastLoginTime(\DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new \DateTime();
		}
		$this->lastLoginTime = $time;
	}

	/**
	 * Get if the user is active
	 * @return boolean
	 */
	public function isActive()
	{
		return $this->active;
	}

	/**
	 * Sets user status
	 * @param boolean $active 
	 */
	public function setActive($active)
	{
		$this->active = $active;
	}

	/**
	 * Returns is user email confirmed
	 * @return bool
	 */
	public function getEmailConfirmed()
	{
		return $this->emailConfirmed;
	}

	/**
	 * Sets is user email confirmed
	 * @param bool $confirmed 
	 */
	public function setEmailConfirmed($confirmed)
	{
		$this->emailConfirmed = $confirmed;
	}

	/**
	 * Returns salt
	 * @return string 
	 */
	public function getSalt()
	{
		return $this->salt;
	}

	/**
	 * Resets salt and returns
	 * @return string
	 */
	public function resetSalt()
	{
		// Generates 23 character salt
		$this->salt = uniqid('', true);

		return $this->salt;
	}

	/**
	 * @return Group
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * @param Group $group
	 */
	public function setGroup($group)
	{
		$this->group = $group;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isSuper()
	{
		return $this->getGroup() ? $this->getGroup()->isSuper() : false;
	}

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
 	 * @param string $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	 * @param int $size
	 * @param string $protocol
	 * @return string
	 */
	public function getGravatarUrl($size = 48, $protocol = 'http')
	{
		$defaultImageset = 'identicon'; // [ 404 | mm | identicon | monsterid | wavatar ]
		//$size = 48; // Size in pixels
		$maxAllowedDecencyRating = 'g'; // [ g | pg | r | x ]
        
        $url = $protocol . '://www.gravatar.com/avatar/';
		$url .= md5(strtolower(trim($this->getEmail())));
		$url .= "?s=$size&d=$defaultImageset&r=$maxAllowedDecencyRating";

		return $url;
	}

}
