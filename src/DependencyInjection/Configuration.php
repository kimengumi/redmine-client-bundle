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

namespace Kimengumi\RedmineClientBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface {
	/**
	 * {@inheritdoc}
	 */
	public function getConfigTreeBuilder() {
		$treeBuilder = new TreeBuilder();
		$rootNode    = $treeBuilder->root( 'redmine' );
		$rootNode->children()
		         ->scalarNode( 'url' )->isRequired()->end()
		         ->scalarNode( 'key' )->isRequired()->end()
		         ->scalarNode( 'cacheLifetime' )->defaultValue( 3600 )->end()
		         ->end();

		return $treeBuilder;
	}
}
