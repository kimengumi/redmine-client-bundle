<?php
/*
 * Redmine client bundle
 *
 * Licensed under the EUPL, Version 1.2 or – as soon they will be approved by
 * the European Commission - subsequent versions of the EUPL (the "Licence");
 * You may not use this work except in compliance with the Licence.
 * You may obtain a copy of the Licence at:
 *
 * https://joinup.ec.europa.eu/software/page/eupl
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the Licence is distributed on an "AS IS" basis,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the Licence for the specific language governing permissions and
 * limitations under the Licence.
 *
 * @author Antonio Rossetti <antonio@rossetti.fr>
 * @copyright since 2017 Antonio Rossetti
 * @license <https://joinup.ec.europa.eu/software/page/eupl> EUPL
 */


namespace Kimengumi\RedmineClientBundle\Command;

use Kimengumi\RedmineClientBundle\Services\RmClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RedminelistenumerationsCommand extends Command
{

    protected static $defaultName = 'redmine:list:enumerations';

    /** @var RmClient */
    protected $rm;

    public function __construct( RmClient $rmClient )
    {
        parent::__construct();
        $this->rm = $rmClient;
    }

    protected function configure()
    {

        $this->setDescription( "List enumerations fields available on the redmine connected instance" );
        $this->setHelp( $this->getDescription() );
    }

    protected function execute( InputInterface $input, OutputInterface $output ): int
    {
        $this->rm->setConsole( $output );

        $tableRows = [];
        /*
         * Check for standard & Easy entry points
         */
        foreach (
            [
                'IssuePriority',
                'DocumentCategory',
                'TimeEntryActivity',
                'EasyInvoiceStatus',
                'EasyInvoicePaymentMethod',
                'EasyProjectPriority',
                'EasyEntityActivityCategory',
                'EasyCustomFieldGroup',
                'EasyVersionCategory',
                'TestCaseIssueExecutionResult',
            ] as $enumType
        ) {
            if ( $rmEnumerations = $this->rm->api->getCollection( 'enumerations/' . $enumType ) ) {
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

        return 0;
    }
}
