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

use HelloPablo\DataMigration\Interfaces\Pipeline;
use HelloPablo\DataMigration\Exception\PipelineException;
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
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Whether to perform a dry-run or not')
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter pipelines', null)
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Run in debug mode');
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
        $oService = Factory::service('DataMigration', Constants::MODULE_SLUG);
        $oService
            ->setOutputInterface($oOutput)
            ->setDebug((bool) $oInput->getOption('debug'))
            ->setDryRun((bool) $oInput->getOption('dry-run'));

        $aPipelines = $oService->getPipelines();
        $aFilter    = $oInput->getOption('filter');

        if (!empty($aFilter)) {
            $aPipelines = array_filter(
                $aPipelines,
                function (Pipeline $oPipeline) use ($aFilter) {
                    foreach ($aFilter as $sFilter) {
                        if (strpos(strtolower(get_class($oPipeline)), strtolower($sFilter)) !== false) {
                            return true;
                        }
                    }
                    return false;
                }
            );
        }

        if (empty($aPipelines)) {
            $oOutput->writeln('No data migration pipelines discovered.');
            $oOutput->writeln('');
            return static::EXIT_CODE_SUCCESS;
        }

        if ($oService->isDryRun()) {
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

            $oService->checkConnectors($aPipelines);
            $aWarnings = $oService->getWarnings();
            if (!empty($aWarnings) && !$this->renderWarnings($aWarnings, 'testing')) {
                return static::EXIT_CODE_FAILURE;
            }

            $oService->prepare($aPipelines);

            $oOutput->writeln('');

            $aErrors   = $oService->getPrepareErrors();
            $aWarnings = $oService->getWarnings();

            if (!empty($aErrors)) {
                return $this->renderErrors($aErrors, 'preparation');

            } elseif (!empty($aWarnings) && !$this->renderWarnings($aWarnings, 'preparation')) {
                return static::EXIT_CODE_FAILURE;
            }

            if ($this->confirm('Pipelines prepared, ready to commit?', true)) {

                $oService->commit($aPipelines);

                $aErrors   = $oService->getCommitErrors();
                $aWarnings = $oService->getWarnings();

                if (!empty($aErrors)) {
                    return $this->renderErrors($aErrors, 'commit');

                } elseif (!empty($aWarnings)) {
                    $this->renderWarnings($aWarnings, 'commit', false);
                }

                $oOutput->writeln('');
                $oOutput->writeln('Finished data migration');
                $oOutput->writeln('');
            }
        }

        return static::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders errors
     *
     * @param PipelineException $aErrors The errors to render
     * @param string            $sLabel  Human friendly label describing when the errors happened
     *
     * @return int
     */
    protected function renderErrors(array $aErrors, string $sLabel): int
    {
        $this->oOutput->writeln('');
        $this->outputBlock(['There were errors during ' . $sLabel . '.'], 'error');
        $this->oOutput->writeln('');

        /** @var PipelineException $oError */
        foreach ($aErrors as $oError) {

            $oError->getPrevious();

            $this->oOutput->writeln(sprintf(
                '<comment>%s</comment>',
                get_class($oError->getPipeline()),
            ));

            $aItems = array_filter([
                ['Source ID', $oError->getUnit()->getSourceId()],
                ['Error', $oError->getMessage()],
                $oError->getPrevious()
                    ? ['Exception', get_class($oError->getPrevious())]
                    : null,
            ]);

            foreach ($aItems as $aItem) {
                $this->oOutput->writeln(sprintf(
                    '↳ <info>%s:</info> %s',
                    $aItem[0],
                    $aItem[1],
                ));
            }

            $this->oOutput->writeln('');
        }

        $this->oOutput->writeln('');
        $this->outputBlock([count($aErrors) . ' errors detected, see above for details.'], 'error');
        $this->oOutput->writeln('');

        return static::EXIT_CODE_FAILURE;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders warnings
     *
     * @param string[] $aWarnings      Warnings to render
     * @param string   $sLabel         Human friendly label describing when the errors happened
     * @param bool     $bAllowContinue Whether to allow the user to continue
     *
     * @return bool
     */
    protected function renderWarnings(array $aWarnings, string $sLabel, bool $bAllowContinue = true): bool
    {
        $this->oOutput->writeln('');
        $this->outputBlock(['Warnings were encountered during ' . $sLabel . '.'], 'warning');
        $this->oOutput->writeln('');

        foreach ($aWarnings as $sWarning) {
            $this->oOutput->writeln(' – ' . $sWarning);
        }

        $this->oOutput->writeln('');
        return $bAllowContinue
            ? $this->confirm('Continue?', true)
            : false;
    }
}
