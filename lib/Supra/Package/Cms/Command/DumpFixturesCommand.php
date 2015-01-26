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

namespace Supra\Package\Cms\Command;

use Doctrine\ORM\EntityManager;
use Supra\Core\Console\AbstractCommand;
use Supra\Package\CmsAuthentication\Entity\Group;
use Supra\Package\CmsAuthentication\Entity\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DumpFixturesCommand extends AbstractCommand
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected $entityMap = array();

    protected $entityAliasMap = array(
        'Supra\Package\CmsAuthentication\Entity\Group' => 'group',
        'Supra\Package\CmsAuthentication\Entity\User' => 'user'
    );

    protected function configure()
    {
        $this->setName('supra:fixtures:dump')
            ->setDescription('Dumps fixtures from database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->container->getDoctrine()->getManager();
        /* @var $em EntityManager */

        $data = array();

        //groups
        foreach ($this->em->getRepository('CmsAuthentication:Group')->findAll() as $group) {
            /* @var $group Group */
            $name = $this->registerEntity($group);

            $data[$name] = array('name' => $group->getName(), 'isSuper' => $group->isSuper());
        }

        $output->writeln(Yaml::dump(array('group' => $data), 2));

        $data = array();

        //users
        foreach ($this->em->getRepository('CmsAuthentication:User')->findAll() as $user) {
            /* @var $user User */
            $name = $this->registerEntity($user);

            $data[$name] = array(
                'name' => $user->getName(),
                'login' => $user->getLogin(),
                'plainPassword' => $user->getPassword(),
                'salt' => $user->getSalt(),
                'email' => $user->getEmail(),
                'active' => $user->isActive(),
                'group' => $this->findEntity($user->getGroup()),
                'roles' => $user->getRoles()
            );
        }

        $output->writeln(Yaml::dump(array('user' => $data), 3));
    }

    protected function findEntity($entity)
    {
        $class = get_class($entity);

        if (!isset($this->entityAliasMap[$class])) {
            throw new \Exception(sprintF('Entity "%s" is not known', $class));
        }

        $alias = $this->entityAliasMap[$class];

        foreach ($this->entityMap[$alias] as $name => $object) {
            if ($object == $entity) {
                return $name;
            }
        }

        throw new \Exception(sprintf('Referenced entity of class "%s" was not found', $class));
    }

    protected function registerEntity($entity)
    {
        $class = get_class($entity);

        if (!isset($this->entityAliasMap[$class])) {
            throw new \Exception(sprintF('Entity "%s" is not known', $class));
        }

        $alias = $this->entityAliasMap[$class];

        $name = null;

        switch ($alias) {
            case 'group':
                $name = $entity->getName();
                break;
            case 'user':
                $name = $this->cleanupName($entity->getName());
                break;
            default:
                throw new \Exception(sprintf('Can not resolve entity name for class "%s"', $class));
                break;
        }

        $this->entityMap[$alias][$name] = $entity;

        return $name;
    }

    protected function cleanupName($name)
    {
        $name = preg_replace('/[^a-z]/i', '_', $name);

        return $name;
    }
}
