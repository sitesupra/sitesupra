<?php

namespace Supra\Seo\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity;
use Supra\Exception\FilesystemPermissionException;
use Supra\Info;
use Supra\Uri\Path;

class GenerateSitemapCommand extends Command
{

	private $host;
//	private $notIncludedInSearch = array();

	protected function configure()
	{
		$this->setName('su:seo:generate_sitemap')
				->setDescription('Generates sitemap.xml and robots.txt.')
				->setHelp('Generates sitemap.xml and robots.txt.
					Includes only records which are included in search and visible in sitemap');
	}

	/**
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$systemInfo = ObjectRepository::getSystemInfo($this);
		$this->host = $systemInfo->getHostName(Info::WITH_SCHEME);
		
		$records = $this->prepareSitemap($output);

		$this->generateSitemapXml($records);
		$this->generateRobotsTxt();

		$output->writeln('Generated sitemap.xml and robots.txt in webroot');
	}

	/**
	 * @param Console\Output\OutputInterface $output
	 * @return array 
	 */
	private function prepareSitemap(Console\Output\OutputInterface $output)
	{
		$em = ObjectRepository::getEntityManager($this);

		$pageFinder = new \Supra\Controller\Pages\Finder\PageFinder($em);
		$localizationFinder = new \Supra\Controller\Pages\Finder\LocalizationFinder($pageFinder);
		$localizationFinder->isActive(true);
		$localizationFinder->isPublic(true);
		$localizationFinder->isRedirect(false);
		$localizationFinder->addCustomCondition('l.includedInSearch = true OR e.level = 0');

		$result = $localizationFinder->getResult();

		$records = array();
		$revisions = array();
		
		foreach ($result as $record) {
			
			$locale = $record->getLocale();
			$revisions[] = $record->getRevisionId();

			$records[$record->getId()] = array(
				'loc' => $this->host . '/' . $locale . $record->getPath()->getFullPath(Path::FORMAT_BOTH_DELIMITERS),
				'lastmod' => $record->getCreationTime()->format('c'),
				'changefreq' => $record->getChangeFrequency(),
				'priority' => $record->getPagePriority(),
			);
		}

		// Run only if any revision is found
		if ( ! empty($revisions)) {
			$qb = $em->createQueryBuilder();
			$qb->from(Entity\PageRevisionData::CN(), 'r');
			$qb->select('r');
			$qb->where('r.id IN (?0)')
					->setParameter(0, $revisions);
			$result = $qb->getQuery();
			$dql = $result->getDQL();
			$result = $result->getResult();

			foreach ($result as $revision) {
				/* @var $revision Entity\PageRevisionData */
				$pageId = $revision->getReferenceId();
				if ( ! isset($records[$pageId])) {
					continue;
				}

				$records[$pageId]['lastmod'] = $revision->getCreationTime()->format('c');
			}
		}

		return $records;
	}

	/**
	 * Generates sitemap and stores to webroot folder
	 * @param array $records
	 * @throws FilesystemPermissionException
	 */
	private function generateSitemapXml($records = array())
	{
		$xmlContent = '<?xml version="1.0" encoding="utf-8"?>'
				. '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';

		$xml = new \SimpleXMLElement($xmlContent);

		foreach ($records as $record) {
			$subnode = $xml->addChild("url");
			foreach ($record as $key => $value) {
				if ($value != '') {
					$subnode->addChild("$key", "$value");
				}
			}
		}

		$xmlData = $xml->asXML(SUPRA_WEBROOT_PATH . 'sitemap.xml');
		if ( ! $xmlData) {
			throw new FilesystemPermissionException('Failed to create/overwrite sitemap.xml in ' . SUPRA_WEBROOT_PATH);
		}
	}

	private function generateRobotsTxt()
	{
		$path = SUPRA_WEBROOT_PATH . 'robots.txt';
		
		$content = 'User-agent: *' . PHP_EOL;

//		foreach ($this->notIncludedInSearch as $record) {
//			$content .= "Disallow: {$record}$" . PHP_EOL;
//		}
		
		$content .= "Sitemap: {$this->host}/sitemap.xml" . PHP_EOL;

		$fp = fopen($path, 'w');

		if ( ! fwrite($fp, $content)) {
			throw new FilesystemPermissionException('Failed to write into robots.txt');
		}

		fclose($fp);
	}

}