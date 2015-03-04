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

namespace Supra\Package\CmsAuthentication\Command;

use Supra\Core\Console\AbstractCommand;
use Supra\Package\CmsAuthentication\Entity\Group;
use Supra\Package\CmsAuthentication\Entity\User;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UsersAddCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('users:add')
			->setDescription('Adds users, provide --em to use different EntityManager')
			->addOption('em', null, InputOption::VALUE_OPTIONAL, 'Entity manager name')
			->addArgument('email', 		InputArgument::REQUIRED, 'User\'s email')
			->addArgument('password', 	InputArgument::REQUIRED, 'User\'s password')
			->addArgument('name', 		InputArgument::OPTIONAL, 'User\'s name')
			->addArgument('group',		InputArgument::OPTIONAL, 'Group name');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$em = $this->container->getDoctrine()->getManager($input->getOption('em'));

		$user = new User();

		$email = $input->getArgument('email');

		if ($em->getRepository('CmsAuthentication:User')->findOneByEmail($email) !== null) {
			throw new \Exception(sprintf('User with email [%s] already exists.', $email));
		}

		$user->setEmail($email);
		$user->setEmailConfirmed(true);
		$user->setActive(true);

		$user->setLogin($email);

		$user->setName($input->getArgument('name') ? $input->getArgument('name') : $user->getLogin());

		$user->setRoles(array('ROLE_USER'));

		if ($input->getArgument('group')) {

			$group = $em->getRepository('CmsAuthentication:Group')->findOneByName($input->getArgument('group'));

			if ($group) {
				$user->setGroup($group);
			} else {
				throw new \Exception(sprintf('Group [%s] does not exist.', $input->getArgument('group')));
			}
		}

		$encoded = $this->container['cms_authentication.encoder_factory']
			->getEncoder($user)
			->encodePassword($input->getArgument('password'), $user->getSalt());

		$user->setPassword($encoded);

		$em->persist($user);
		$em->flush();

		$output->writeln('User created!');
	}

	/**
	 * {@inheritDoc}
	 */
	protected function interact(InputInterface $input, OutputInterface $output)
	{
		$em = $this->container->getDoctrine()->getManager($input->getOption('em'));
		/* @var $em \Doctrine\ORM\EntityManager */

		if (! $input->getArgument('email')) {
			$email = $this->getHelper('dialog')->askAndValidate(
				$output,
				'Please choose an email: ',
				function ($email) use ($em) {
					if (empty($email)) {
						throw new \Exception('Email can not be empty.');
					}

					if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
						throw new \Exception(sprintf('[%s] is not a valid email address.', $email));
					}

					if ($em->getRepository('CmsAuthentication:User')->findOneByEmail($email) !== null) {
						throw new \Exception(sprintf('User with email [%s] already exists.', $email));
					}

					return $email;
				}
			);

			$input->setArgument('email', $email);
		}

		if (! $input->getArgument('password')) {
			$password = $this->getHelper('dialog')->askHiddenResponseAndValidate(
				$output,
				'Please choose a password: ',
				function($password) {
					if (empty($password)) {
						throw new \Exception('Password can not be empty.');
					}

					return $password;
				}
			);

			$input->setArgument('password', $password);
		}

		if (! $input->getArgument('name')) {
			$name = $this->getHelper('dialog')->ask($output, 'Please choose a name: ');
			$input->setArgument('name', $name);
		}

		if (! $input->getArgument('group')) {

			$groupName = $this->getHelper('dialog')->askAndValidate(
				$output,
				'Please choose an group: ',
				function ($groupName) use ($em) {
					if (empty($groupName)) {
						return null;
					}

					if ($em->getRepository('CmsAuthentication:Group')->findOneByName($groupName) === null) {
						throw new \Exception(sprintf('Group [%s] does not exist.', $groupName));
					}

					return $groupName;
				}
			);

			$input->setArgument('group', $groupName);
		}
	}
}
