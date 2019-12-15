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


use Redmine\Api\Project;

class RmProjectApi extends Project {

	/**
	 * @param $projectId
	 * @param $userId
	 * @param $roleId
	 *
	 * @return bool|void
	 * @throws \Exception
	 */
	public function updateOrCreateProjectMembership( $projectId, $userId, $roleId ) {

		$membershipId = null;
		$allRoles     = [];
		$directRoles  = [];

		// Get existing roles for the user
		$allMembers = $this->client->api->getCollection( '/projects/' . (int) $projectId . '/memberships' );
		foreach ( $allMembers as $oneMember ) {
			if ( ( isset( $oneMember['user'] ) && ( $oneMember['user']['id'] == $userId ) ) ||
			     ( isset( $oneMember['group'] ) && ( $oneMember['group']['id'] == $userId ) ) ) {
				$membershipId = $oneMember['id'];
				foreach ( $oneMember['roles'] as $oneRole ) {
					$allRoles[ $oneRole['id'] ] = true;
					if ( ! isset( $oneRole['inherited'] ) || ( $oneRole['inherited'] == 0 ) ) {
						$directRoles[ $oneRole['id'] ] = true;
					}
				}
			}
		}

		//Requested role is already assigned to user
		if ( isset( $allRoles[ $roleId ] ) ) {
			return true;
		}

		// Add new role in parameters
		$directRoles[ $roleId ] = true;

		if ( $membershipId ) {
			$run = $this->client->membership->update( $membershipId, [
				'role_ids' => array_keys( $directRoles )
			] );
		} else {
			$run = $this->client->membership->create( $projectId, [
				'user_id'  => $userId,
				'role_ids' => array_keys( $directRoles ),
			] );
		}

		// Error
		return $this->client->haveError( $run, 'PUT', 'membership' );
	}
}