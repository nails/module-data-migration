<?php

/**
 * The class runs data migration recipes
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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Run
 *
 * @package Nails\DataMigration\Console\Command
 */
class Run extends Base
{
    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('datamigration:run')
            ->setDescription('Runs data migration recipes')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Whether to perform a dry-run or not');
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
        $this->banner('Data Migration: Run');

        /** @var DataMigration $oService */
        $oService   = Factory::service('DataMigration', Constants::MODULE_SLUG);
        $aPipelines = $oService->getPipelines();

        if (empty($aPipelines)) {
            $oOutput->writeln('No data migration pipelines discovered.');
            $oOutput->writeln('');
            return static::EXIT_CODE_SUCCESS;
        }

        $oOutput->writeln('The following data migration pipelines will be run:');
        foreach ($aPipelines as $oRecipe) {
            $oOutput->writeln(sprintf(
                '– <info>%s</info>',
                get_class($oRecipe)
            ));
        }
        $oOutput->writeln('');

        if ($this->confirm('Continue?', true)) {
            if ($oInput->getOption('dry-run')) {
                $oService->dryRun($aPipelines, $oOutput);
            } else {
                $oService->run($aPipelines, $oOutput);
            }
        }

        return static::EXIT_CODE_SUCCESS;
    }
}
