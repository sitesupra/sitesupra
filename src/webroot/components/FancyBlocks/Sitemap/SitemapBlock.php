<?php

namespace Project\FancyBlocks\Sitemap;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Repository;
use Supra\Controller\Pages\Finder\PageFinder;
use Supra\Controller\Pages\Finder\LocalizationFinder;

/**
 * SitemapBlock
 */
class SitemapBlock extends LinksBlock
{

	public static function getPropertyDefinition()
	{
		return array();
	}

	public function doExecute()
	{
		$response = $this->getResponse();
		$em = ObjectRepository::getEntityManager($this);
		$locale = $this->getRequest()->getLocale();
		/* @var $pageRepo Repository\PageRepository */

		$localizations = array();

		$pageFinder = new PageFinder($em);
		$pageFinder->addLevelFilter(1, 5);

		$localizationFinder = new LocalizationFinder($pageFinder);

		// these are defaults in fact
		//$localizationFinder->isActive(true);
		//$localizationFinder->isPublic(true);
		// custom
		$localizationFinder->setLocale($locale);

		//FIXME: Problem – what if parent is not visible?
		//$localizationFinder->isVisibleInSitemap(true);

//		$organizer = new LocalizationOrganizer();
//		$organizer->organize($localizationFinder);
//		
//		$resultTree = $organizer->getResults();
//		
//		$resultTree->getNode();
//		foreach ($resultTree->getChildren() as $child) {
//			$child->getNode();
//		}

		$localizations = $localizationFinder->getResult();
		$map = $this->addRealLevels($localizations);

		/* @var $theme Entity\Theme\Theme */
		$theme = $this->getRequest()->getLayout()->getTheme();
		
		$response->getContext()
				->addCssLinkToLayoutSnippet('css', $theme->getUrlBase() . '/assets/css/page-sitemap.css');

		$response->assign('map', $map);
		$response->outputTemplate('sitemap.html.twig');
	}

	/**
	 * Appends real level information to localizations (ignores groups, strips out the news articles)
	 * @param array $localizations
	 * @return array
	 */
	private function addRealLevels($localizations)
	{
		$interval = array(array(-1, PHP_INT_MAX));
		$stopInterval = array(-1, -1);
		$level = 0;

		$map = array();

		foreach ($localizations as $localization) {
			/* @var $localization Entity\PageLocalization */
			//\Log::debug('LLL: ', $localization->getId(), ' - ', $localization->isVisibleInSitemap());
			$lft = $localization->getMaster()->getLeftValue();
			$rgt = $localization->getMaster()->getRightValue();

			// under the parent
			if ($lft > $interval[$level][0] && $rgt < $interval[$level][1]) {
				$level ++;
				$interval[$level] = array($lft, $rgt);
			} else {
				while ($lft > $interval[$level - 1][1]) {
					$level --;

					if ($level < 0) {
						throw new \OutOfBoundsException("Negative sitemap level reached");
					}
				}
				$interval[$level] = array($lft, $rgt);
			}

			// Fix to hide news under the news application
			if ($lft > $stopInterval[0] && $rgt < $stopInterval[1]) {
				continue;
			}

			$path = $localization->getPathEntity();

			if (empty($path)) {
				continue;
			}

			$visibleInSitemap = $path->isVisibleInSitemap() && $localization->isVisibleInSitemap();
			$isActive = $path->isActive() && $localization->isActive();

			if ($visibleInSitemap && $isActive) {
				
				$map[] =  array(
					'level' => $level,
					'localization' => $localization
				);
			}

			$updateStopper = false;

			// Fix for publications
			if ($localization instanceof Entity\ApplicationLocalization) {
				$updateStopper = true;
			}

			// Don't show children pages for parent with visibility OFF as well
			if ( ! ($visibleInSitemap || $isActive)) {
				$updateStopper = true;
			}

			if ($updateStopper) {
				// Don't change stopper if current interval includes the page
				if ( ! ($stopInterval[0] <= $lft && $stopInterval[1] >= $rgt)) {
					$stopInterval = array($lft, $rgt);
				}
			}
		}

		return $map;
	}

}