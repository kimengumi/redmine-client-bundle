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

namespace Kimengumi\RedmineClientBundle\Services\Redmine;


class RedmineUtils {

	/**
	 * Get custom filed value by custom field id, for a record returned by the redmine API
	 *
	 * @param $entity
	 * @param $customFieldId
	 * @param $defaultValue
	 *
	 * @return bool
	 */
	public function getCFValue( $entity, $customFieldId, $defaultValue = false ) {

		if ( isset( $entity['custom_fields'] ) ) {
			foreach ( $entity['custom_fields'] as $customField ) {
				if ( (int) $customField['id'] == (int) $customFieldId ) {
					return $customField['value'] ?? $defaultValue;
				}
			}
		}

		return $defaultValue;
	}

	/*
	 * Check if have value in key/value collection
	 *
	 * @param array $entity
	 * @param string $collection
	 * @param $value
	 *
	 * @return bool
	 */
	public function haveEnumerationValue( array $collection, $toCheck ) {

		if ( ! is_array( $toCheck ) ) {
			$toCheck = [ $toCheck ];
		}

		$count = 0;

		foreach ( $collection as $enumeration ) {
			foreach ( $toCheck as $item ) {
				if ( is_array( $enumeration ) ) {
					if ( $enumeration['name'] == $item ) {
						$count ++;
					}
				} else if ( $enumeration == $item ) {
					$$count ++;
				}
			}
		}

		return ( $count == count( $toCheck ) );
	}

	/**
	 * @param array $collection
	 * @param array|string $toAdd
	 *
	 * @return array
	 */
	public function mergeEnumerationNames( array $collection, $toAdd ) {
		$merge = [];
		if ( ! is_array( $toAdd ) ) {
			$toAdd = [ $toAdd ];
		}
		foreach ( $collection as $item ) {
			if ( is_array( $item ) && isset( $item['name'] ) ) {
				$merge[ $item['name'] ] = $item['name'];
			} else {
				$merge[ $item ] = $item;
			}
		}
		foreach ( $toAdd as $item ) {
			if ( is_array( $item ) && isset( $item['name'] ) ) {
				$merge[ $item['name'] ] = $item['name'];
			} else {
				$merge[ $item ] = $item;
			}
		}


		return $merge;

	}

	/**
	 * Return a user-browsable url for the given params. (same arguments as getCollection)
	 *
	 * @param string $endpoint API end point
	 * @param array $params optional parameters to be passed to the api (offset, limit, ...)
	 * @param string $extraGetParams raw parmas directly added in the GET uri query (optional)
	 *
	 * @return array elements found
	 */
	public function getWebLink( $endpoint, array $params = [], $extraGetParams = null ) {

		$params['set_filter'] = 1;

		return $this->url . $endpoint . '?' . ( $extraGetParams ? $extraGetParams . '&' : '' ) .
		       preg_replace( '/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query( $params ) );
	}

	/**
	 * @param array $data
	 * @param $rootTag
	 *
	 * @return string
	 */
	public static function arrayToXml( array $data, string $rootTag ) {
		$xml = new \SimpleXMLElement( '<' . $rootTag . '/>' );
		self::arrayNodeToXmlNode( $xml, $data );

		return $xml->asXML();
	}

	/**
	 * @param SimpleXMLElement $object
	 * @param array $data
	 */
	public static function arrayNodeToXmlNode( \SimpleXMLElement &$object, array $data ) {
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$new_object = $object->addChild( $key );
				self::arrayNodeToXmlNode( $new_object, $value );
			} else {
				$object->addChild( $key, $value );
			}
		}
	}
}