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
use Supra\Package\Cms\Entity\Image;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\CmsAuthentication\Entity\Group;
use Supra\Package\CmsAuthentication\Entity\User;
use Symfony\Component\Console\Input\InputArgument;
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
        'Supra\Package\CmsAuthentication\Entity\User' => 'user',
        'Supra\Package\Cms\Entity\Template' => 'template'
    );

    protected function configure()
    {
        $this->setName('supra:fixtures:dump')
            ->addArgument('folder', InputArgument::OPTIONAL, 'Target folder, files will not be saved unless it is specified')
            ->setDescription('Dumps fixtures from database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->container->getDoctrine()->getManager();
        /* @var $em EntityManager */

        $targetFolder = $input->getArgument('folder');

        if ($targetFolder && !is_dir($targetFolder)) {
            throw new \Exception(sprintf('You have specified "%s" as a target folder but it does not exist', $targetFolder));
        }

        $yaml = '';

        //groups
        $data = array();

        foreach ($this->em->getRepository('CmsAuthentication:Group')->findAll() as $group) {
            /* @var $group Group */
            $name = $this->registerEntity($group);

            $data[$name] = array('name' => $group->getName(), 'isSuper' => $group->isSuper());
        }

        $yaml .= Yaml::dump(array('group' => $data), 2);

        //users
        $data = array();

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

        $yaml .= Yaml::dump(array('user' => $data), 3);

        //templates
        $data = array();

        foreach ($this->em->getRepository('Cms:Template')->findAll() as $template) {
            $name = $this->registerEntity($template);
            /* @var $template Template */

            $tpl = array(
                'media' => 'screen',
                'layoutName' => $template->getTemplateLayouts()['screen']->getLayoutName(),
                'localizations' => array()
            );

            foreach ($template->getLocalizations() as $locale => $localization) {
                $tpl['localizations'][$locale] = $localization->getTitle();
            }

            $data[$name] = $tpl;
        }

        $yaml .= Yaml::dump(array('template' => $data), 3);

        //images
        $data = array();

        foreach ($this->em->getRepository('Cms:Image')->findAll() as $image) {
            /* @var $image Image */

            $data[] = $image;
        }

        $yaml .= Yaml::dump(array('image' => $data), 3);

        $output->writeln($yaml);

        if ($targetFolder) {
            file_put_contents($targetFolder . DIRECTORY_SEPARATOR . 'fixtures.yml', $yaml);
        }
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
            case 'template':
                $layouts = $entity->getTemplateLayouts();
                if (!isset($layouts['screen'])) {
                    throw new \Exception(sprintf('Template "%s" does not have a "screen" layout', $entity->getId()));
                }
                $name = $this->cleanupName($layouts['screen']->getLayoutName());
                break;
        }

        if (is_null($name)) {
            throw new \Exception(sprintf('Can not resolve entity name for class "%s"', $class));
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
