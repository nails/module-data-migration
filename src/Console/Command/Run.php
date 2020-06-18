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
        $bDryRun    = (bool) $oInput->getOption('dry-run');
        $aPipelines = $oService->getPipelines();

        if (empty($aPipelines)) {
            $oOutput->writeln('No data migration pipelines discovered.');
            $oOutput->writeln('');
            return static::EXIT_CODE_SUCCESS;
        }

        if ($bDryRun) {
            $this->outputBlock(['Dry-run: migrations will not be comitted'], 'warning');
            $oOutput->writeln('');
        }

        $oOutput->writeln('The following data migration pipelines will be processed:');
        foreach ($aPipelines as $oRecipe) {
            $oOutput->writeln(sprintf(
                '– <info>%s</info>',
                get_class($oRecipe)
            ));
        }
        $oOutput->writeln('');

        if ($this->confirm('Continue?', true)) {
            $oService
                ->setDryRun($bDryRun)
                ->prepare($aPipelines, $oOutput);

            $oOutput->writeln('');

            $aErrors = $oService->prepareErrors();

            if (!empty($aErrors)) {

                $oOutput->writeln('<error>There were errors during preparation:</error>');
                $oOutput->writeln('');

                foreach ($aErrors as $oError) {

                    $oOutput->writeln(sprintf(
                        '[<info>%s</info>] Source ID: <info>%s</info>; %s',
                        $oError->pipeline,
                        $oError->source_id,
                        $oError->error,
                    ));
                }

                $oOutput->writeln('');
                $oOutput->writeln('<error>' . count($aErrors) . ' errors detected, migrations were NOT comitted</error>');
                $oOutput->writeln('');

            } elseif ($this->confirm('Pipelines prepared, ready to commit?', true)) {
                $oService->commit($aPipelines, $oOutput);

                $oOutput->writeln('');
                $oOutput->writeln('Finished data migrations');
                $oOutput->writeln('');
            }
        }

        return static::EXIT_CODE_SUCCESS;
    }
}
