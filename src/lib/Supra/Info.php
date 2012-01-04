<?php

namespace Supra;

use Supra\Configuration\Exception\ConfigurationMissing;
use Supra\Configuration\Exception\InvalidConfiguration;

class Info implements Configuration\ConfigurationInterface
{
	const NO_SCHEME = 1;
	const WITH_SCHEME = 2;
	
	public $systemId;
	
	public $hostName;

	public function configure()
	{
		//@todo: add to ObjectRepository from here.
	}
	
	/**
	 * @param int $format
	 * @return string
	 */
	public function getHostName($format = self::NO_SCHEME)
	{
		if (empty($this->hostName)) {
			throw new ConfigurationMissing("Host name not configured for the system");
		}
		
		$hostname = $this->hostName;
		
		$url = parse_url($hostname);
		
		if ( ! isset($url['scheme'])) {
			$hostname = 'http://' . $hostname;
		}
		
		$url = parse_url($hostname);
		
		$string = null;

		if (isset($url['host'])) {
			// Glue the URL
			$string = ($url['user'] ? $url['user'] . ($url['pass'] ? ':' . $url['pass'] : '') . '@' : '')
					. $url['host']
					. ($url['port'] ? ':' . $url['host'] : '');
		} else {
			throw new InvalidConfiguration("Invalid system hostname value");
		}
		
		if ($format == self::WITH_SCHEME) {
			$string = $url['scheme'] . '://' . $string;
		}
		
		return $string;
	}

}