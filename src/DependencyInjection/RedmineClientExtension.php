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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class RedmineClientExtension extends Extension {
	/**
	 * {@inheritdoc}
	 */
	public function load( array $configs, ContainerBuilder $container ) {
		$configuration = new Configuration();
		$config        = $this->processConfiguration( $configuration, $configs );

		$loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__ . '/../Resources/config' ) );
		$loader->load( 'services.yml' );

		$container->setParameter( sprintf( '%s.url', $this->getAlias() ), $config['url'] );
		$container->setParameter( sprintf( '%s.token', $this->getAlias() ), $config['key'] );
		$container->setParameter( sprintf( '%s.token', $this->getAlias() ), $config['cacheLifetime'] );
	}
}
