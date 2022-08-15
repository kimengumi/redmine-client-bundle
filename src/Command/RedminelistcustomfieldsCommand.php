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


namespace Kimengumi\RedmineClientBundle\Command;

use Kimengumi\RedmineClientBundle\Services\RmClient;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RedminelistcustomfieldsCommand extends ContainerAwareCommand {

	protected static $defaultName = 'redmine:list:customfields';

	protected function configure() {

		$this->setDescription( "List custom fields available on the redmine connected instance" );
		$this->setHelp( $this->getDescription() );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {

		/** @var $rm RmClient */
		$rm = $this->getContainer()
		           ->get( 'redmine.client' )
		           ->setConsole( $output );


		$rmCFs = $rm->api->getCollection( 'custom_fields' );

		foreach ( $rmCFs as $rmCF ) {
			if ( ($rmCF['field_format'] == 'enumeration') && isset($rmCF['possible_values']) ) {
				foreach ( $rmCF['possible_values'] as $rmCFEnum ) {
					$tableRows[ $rmCF['id'] . $rmCFEnum['value'] ] = [
						'customized_type' => $rmCF['customized_type'] ?? null,
						'id'              => $rmCF['id'],
						'name'            => $rmCF['name'],
						'field_format'    => $rmCF['field_format'],
						'enum_value'      => $rmCFEnum['value'],
						'enum_label'      => $rmCFEnum['label'],

					];
				}
			} else {
				$tableRows[ $rmCF['id'] ] = [
					'customized_type' => $rmCF['customized_type'] ?? null,
					'id'              => $rmCF['id'],
					'name'            => $rmCF['name'],
					'field_format'    => $rmCF['field_format'],
					'enum_value'      => null,
					'enum_label'      => null,
				];
			}

		}

		$output->writeln( 'CUSTOM FIELDS & CUSTOM FIELDS ENUMERATIONS' );
		$t = new Table( $output );
		$t->setHeaders( [
			'Customized entity type',
			'CF id',
			'Custom Field Name',
			'Field Format',
			'Enum value',
			'Enumeration Label'
		] )->setRows( $tableRows )->render();
	}
}
