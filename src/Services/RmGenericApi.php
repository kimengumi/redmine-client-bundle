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

use Redmine\Api\AbstractApi;
use Redmine\Client;

class RmGenericApi extends AbstractApi {

	/**
	 * Generic function for retrieving all the elements of any given endpoint returning a list of elements
	 * (even if the total number of elements is greater than self::DEFAULT_PAGINATION).
	 * This function avoid the use of fixed subclasses and allow access to extra/custom Redmine endpoints.
	 *
	 * @param string $endpoint API end point
	 * @param array $params optional parameters to be passed to the api (offset, limit, ...)
	 * @param string $extraGetParams raw parmas directly added in the GET uri query (optional)
	 * @param int $pagination Pagination used for webservices call
	 * @param bool $collectionOnly Return only the collection level of the, or the full response tree
	 *
	 * @return array elements found
	 */
	public function getCollection( $endpoint, array $params = [], $extraGetParams = null, $pagination = RmClient::DEFAULT_PAGINATION, $collectionOnly = true ) {

		$params['set_filter'] = 1;
		$limit                = $params['limit'] ?? PHP_INT_MAX;
		$offset               = $params['offset'] ?? 0;
		$ret                  = [];

		while ( $limit > 0 ) {
			if ( $limit > $pagination ) {
				$_limit = $pagination;
				$limit  -= $pagination;
			} else {
				$_limit = $limit;
				$limit  = 0;
			}
			$params['limit']  = $_limit;
			$params['offset'] = $offset;

			$newDataSet = (array) $this->get(
				$endpoint . '.json?' . ( $extraGetParams ? $extraGetParams . '&' : '' ) .
				preg_replace( '/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query( $params ) ) );
			$ret        = array_merge_recursive( $ret, $newDataSet );

			$offset += $_limit;
			if ( empty( $newDataSet ) || ! isset( $newDataSet['limit'] ) || (
					isset( $newDataSet['offset'] ) &&
					isset( $newDataSet['total_count'] ) &&
					$newDataSet['offset'] >= $newDataSet['total_count']
				)
			) {
				$limit = 0;
			}
		}

		return ! $collectionOnly ? $ret : $ret[ $endpoint ] ?? reset( $ret ) ?? [];
	}

	/**
	 * @param string $singleName
	 * @param string|int $id
	 * @param string|null $endpoint
	 *
	 * @return mixed|null
	 */
	public function getOne( string $singleName, $id, array $params = [], string $customEndpoint = null ) {

		if ( ! $path = $customEndpoint ) {
			$path = $singleName . 's';
		}

		$path .= '/' . $id . '.json';
		if ( ! empty( $params ) ) {
			$path .= '?' . preg_replace( '/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query( $params ) );
		}

		$result = (array) $this->client->get( $path );

		return $result[ $singleName ] ?? null;
	}

	/**
	 * @param string $singleName
	 * @param string|int $id
	 * @param array $updateData
	 * @param string|null $endpoint
	 *
	 * @return false|\SimpleXMLElement|string
	 * @throws \Exception
	 */
	public function update( string $singleName, $id, array $data, string $endpoint = null ) {

		if ( ! $endpoint ) {
			$endpoint = $singleName . 's';
		}

		$this->output->writeln( self::arrayToXml( $data, $singleName ) );

		return $this->client->put( $endpoint . '/' . $id . '.xml', self::arrayToXml( $data, $singleName ) );

	}

	/**
	 * @param string $singleName
	 * @param array $data
	 * @param string|null $endpoint
	 *
	 * @return false|\SimpleXMLElement|string
	 * @throws \Exception
	 */
	public function create( string $singleName, array $data, string $endpoint = null ) {

		if ( ! $endpoint ) {
			$endpoint = $singleRecordName . 's';
		}


		return $this->client->post( $endpoint . '.xml', $this->client->utils->arrayToXml( $data, $singleName ) );
	}


}