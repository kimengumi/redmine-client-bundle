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

class RedminelistenumerationsCommand extends ContainerAwareCommand {

	protected static $defaultName = 'redmine:list:enumerations';

	protected function configure() {

		$this->setDescription( "List enumerations fields available on the redmine connected instance" );
		$this->setHelp( $this->getDescription() );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {

		/** @var $rm RmClient */
		$rm = $this->getContainer()
		           ->get( 'redmine.client' )
		           ->setConsole( $output );

		$tableRows = [];
		/*
		 * Check for standard & Easy entry points
		 */
		foreach (
			[
				'IssuePriority',
				'DocumentCategory',
				'TimeEntryActivity',
				'EasyPersonalFinancePaymentMethod',
				'EasyInvoiceStatus',
				'EasyInvoicePaymentMethod',
				'EasyProjectPriority',
				'EasyEntityActivityCategory',
				'EasyCustomFieldGroup'
			] as $enumType
		) {
			if ( $rmEnumerations = $rm->api->getCollection( 'enumerations/' . $enumType ) ) {
				foreach ( $rmEnumerations as $rmEnum ) {
					$tableRows[ $rmEnum['id'] ] = [
						'type' => $enumType,
						'id'   => $rmEnum['id'],
						'name' => $rmEnum['name'],
					];
				}
			}
		}

		$output->writeln( 'ENUMERATIONS' );
		$t = new Table( $output );
		$t->setHeaders( [
			'Entity Type',
			'Id',
			'Name',
		] )->setRows( $tableRows )->render();
	}
}
