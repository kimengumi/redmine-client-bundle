<?php
/*
 * This file is part of the Redmine API client bundle for Symfony.
 *
 * Copyright (c) 2017-2020 Antonio Rossetti <antonio@kimengumi.fr>
 *
 * Licensed under the EUPL, Version 1.2 or - as soon they will be approved by the European Commission - subsequent
 * versions of the EUPL (the "Licence"); You may not use this work except in compliance with the Licence.
 * You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/software/page/eupl
 *
 * Unless required by applicable law or agreed to in writing, software distributed under the Licence is distributed
 * on an "AS IS" basis, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the Licence for the specific language governing permissions and limitations under the Licence.
 */


namespace Kimengumi\RedmineClientBundle\Services;

use Html2Text\Html2Text;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RedmineClient
 * @package Kimengumi\RedmineClientBundle\Services
 * @property RmGenericApi $api
 * @property RmProjectApi $project
 * @property RmUtils $utils
 */
Class RmClient extends \Redmine\Client {

	public const DEFAULT_PAGINATION = 100;

	/**
	 * Memory cache
	 *
	 * @var array
	 */
	protected $cache = array();

	/**
	 * Memory cache activation
	 *
	 * @var bool
	 */
	protected $cacheEnabled = true;

	/**
	 * File cache
	 *
	 * @var \Symfony\Component\Cache\Simple\FilesystemCache
	 */
	protected $fileCache;

	/**
	 * File cache activation
	 *
	 * @var bool
	 */
	protected $fileCacheEnabled = true;

	/**
	 * Symfony console output
	 *
	 * @var \Symfony\Component\Console\Output\ConsoleOutput
	 */
	protected $output;

	/**
	 * Same principle as parent
	 *
	 * @var array APIs
	 */
	private $apis = [];

	/**
	 * Same principle as parent
	 *
	 * @var array
	 */
	private $classes = [
		'api'     => 'RmGenericApi',
		'project' => 'RmProjectApi',
		'utils'   => 'RmUtils'
	];

	/**
	 * Redmine constructor.
	 *
	 * @param $url
	 * @param $apikeyOrUsername
	 * @param null $pass
	 */
	public function __construct( $url, $apikeyOrUsername, $pass = null, $fileCacheLifetime = 3600, $projectDir = './' ) {

		$this->url       = $url;
		$this->fileCache = new FilesystemCache( md5( $this->url ), $fileCacheLifetime, $projectDir . '/var/cache/redmine' );

		return parent::__construct( $this->url, $apikeyOrUsername, $pass );
	}

	/**
	 * Same principle as parent
	 *
	 * @param string $name
	 *
	 * @return mixed|\Redmine\Api\AbstractApi
	 */
	public function api( $name ) {

		if ( ! isset( $this->classes[ $name ] ) ) {
			return parent::api( $name );
		}
		if ( isset( $this->apis[ $name ] ) ) {
			return $this->apis[ $name ];
		}
		$class               = __NAMESPACE__ . '\\' . $this->classes[ $name ];
		$this->apis[ $name ] = new $class( $this );

		return $this->apis[ $name ];

	}

	/**
	 * Set the console output
	 * To be used when calling service from a CLI script
	 *
	 * @param \Symfony\Component\Console\Output\ConsoleOutput $output
	 *
	 * @return $this
	 */
	public function setConsole( $output ) {

		if ( $output instanceof OutputInterface ) {
			$this->output = $output;
			$this->console( '<fg=magenta>[RDM] Connect to ' . $this->url . '</>' );
		}

		return $this;
	}

	/**
	 * @param $messages
	 * @param int $options
	 */
	protected function console( $messages, $options = OutputInterface::OUTPUT_NORMAL ) {
		if ( $this->output ) {
			$this->output->writeln( $messages, $options );
		}
	}

	/**
	 * @param bool $file
	 * @param bool $mem
	 */
	public function enableCache( $mem = true, $file = true ) {
		$this->cacheEnabled     = (bool) $mem;
		$this->fileCacheEnabled = (bool) $file;

		return $this;
	}

	/**
	 * HTTP GETs a json $path and tries to decode it.
	 *
	 * @param string $path
	 * @param bool $decode
	 *
	 * @return array|string|false
	 */
	public function get( $path, $decode = true ) {

		$cacheHash = md5( $path ) . strval( $decode );

		// Take from mem cache
		if ( $this->cacheEnabled && isset( $this->cache[ $cacheHash ] ) ) {
			$this->console( '<fg=magenta>[RDM] MEM ' . $path . '</>', OutputInterface::VERBOSITY_DEBUG );

			return $this->cache[ $cacheHash ];
		}

		// Take from file cache
		if ( $this->fileCacheEnabled && $this->fileCache->has( $cacheHash ) ) {
			$this->console( '<fg=magenta>[RDM] FIL ' . $path . '</>', OutputInterface::VERBOSITY_DEBUG );

			return $this->cache[ $cacheHash ] = $this->fileCache->get( $cacheHash );
		}

		// Run request
		$return = $this->runRequest( $path, 'GET' );

		// Json decode
		if ( $return && $decode ) {
			$decoded = $this->decode( $return );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				$source = new \Html2Text\Html2Text( $return, [ 'do_links' => 'none', 'width' => 0 ] );
				$return = [ 'errors' => [ 'JSON decode error : ' . $decoded . ' :', $source->getText() ] ];
			} else {
				$return = $decoded;
			}
		}

		// Error
		if ( $this->haveError( $return, 'GET', $path ) ) {
			return false;
		}

		// Store in cache
		if ( $this->cacheEnabled ) {
			$this->cache[ $cacheHash ] = $return;
		}
		if ( $this->fileCacheEnabled ) {
			$this->fileCache->set( $cacheHash, $return );
		}

		return $return;
	}

	/**
	 * @codeCoverageIgnore Ignore due to untestable curl_* function calls.
	 *
	 * @param string $path
	 * @param string $method
	 * @param string $data
	 *
	 * @return false|\SimpleXMLElement|string
	 * @throws \Exception If anything goes wrong on curl request
	 *
	 */
	protected function runRequest( $path, $method = 'GET', $data = '' ) {

		$this->console( '<fg=magenta>[RDM] ' . $method . ' ' . $path . '</>', OutputInterface::VERBOSITY_VERY_VERBOSE );

		$return = parent::runRequest( '/' . $path, $method, $data );

		if ( $method !== 'GET' ) {
			$this->haveError( $return, $method, $path );
		}

		return $return;
	}

	/**
	 * Analyse errors returned from well know redmine api returns (XML/JSON)
	 *
	 * @param $apiReturn
	 * @param null $method
	 * @param null $path
	 */
	public function haveError( $apiReturn, $method = null, $path = null ) {

		$httpResponseCode = $this->getResponseCode();
		$unknownContent   = null;

		if ( ! is_array( $apiReturn ) && ! is_object( $apiReturn ) ) {
			if ( $apiReturn ) {
				$source         = new \Html2Text\Html2Text( $apiReturn, [ 'do_links' => 'none', 'width' => 0 ] );
				$unknownContent = $source->getText();
			}
			$apiReturn = array();
		}

		switch ( $httpResponseCode ) {
			case 200:
			case 201:
			case 204:
				$error = false;
				break;
			default:
				$error = '';
		}
		if ( isset( $apiReturn->error ) ) {
			$error = $apiReturn->error;
		} elseif ( isset( $apiReturn->errors ) ) {
			$error = $apiReturn->errors;
		} elseif ( isset( $apiReturn['error'] ) ) {
			$error = is_array( $apiReturn['error'] ) ? implode( $apiReturn['error'], "\n" ) : $apiReturn['error'];
		} elseif ( isset( $apiReturn['errors'] ) ) {
			$error = is_array( $apiReturn['errors'] ) ? implode( $apiReturn['errors'], "\n" ) : $apiReturn['errors'];
		}

		if ( $error !== false ) {
			if ( $this->output ) {
				if ( $method || $path ) {
					$this->output->writeln( '<fg=red>[RDM] ' . $method . ' ' . $path . ' (HTTP ' . $httpResponseCode . ')</>', OutputInterface::VERBOSITY_QUIET );
				}
				if ( $error ) {
					$this->output->writeln( '<fg=red>          ' . $error . '</>', OutputInterface::VERBOSITY_QUIET );
				}
				if ( $unknownContent ) {
					$this->output->writeln( '<fg=red>          ' . $unknownContent . '</>', OutputInterface::VERBOSITY_QUIET );
				}
			}

			return true;
		}

		return false;
	}
}