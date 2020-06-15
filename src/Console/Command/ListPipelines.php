<?php

/**
 * The class lists data migration recipies
 *
 * @package     Nails
 * @subpackage  module-data-migration
 * @category    Console
 * @author      Nails Dev Team
 */

namespace Nails\DataMigration\Console\Command;

use Nails\Console\Command\Base;
use Nails\DataMigration\Constants;
use Nails\DataMigration\Service\DataMigration;
use Nails\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListPipelines
 *
 * @package Nails\DataMigration\Console\Command
 */
class ListPipelines extends Base
{
    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('datamigration:list')
            ->setDescription('Lists data migration pipelines');
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param InputInterface  $oInput  The Input Interface provided by Symfony
     * @param OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     * @throws FactoryException
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);
        $this->banner('Data Migration: List');

        /** @var DataMigration $oService */
        $oService   = Factory::service('DataMigration', Constants::MODULE_SLUG);
        $aPipelines = $oService->getPipelines();

        if (empty($aPipelines)) {
            $oOutput->writeln('No data migration pipelines discovered.');
            $oOutput->writeln('');
            return static::EXIT_CODE_SUCCESS;
        }

        $oOutput->writeln('The following data migration pipelines were discovered:');
        foreach ($aPipelines as $oRecipe) {
            $oOutput->writeln(sprintf(
                '– <info>%s</info>',
                get_class($oRecipe)
            ));
        }
        $oOutput->writeln('');

        return static::EXIT_CODE_SUCCESS;
    }
}
