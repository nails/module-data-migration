<?php

/**
 * The class manages data migrations
 *
 * @package     Nails
 * @subpackage  module-data-migration
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\DataMigration\Service;

use HelloPablo\DataMigration\Interfaces\Pipeline;
use HelloPablo\DataMigration\Manager;
use Nails\Components;
use Symfony\Component\Console\Output\OutputInterface;

class DataMigration
{
    /** @var Manager */
    protected $oManager;

    // --------------------------------------------------------------------------

    /**
     * DataMigration constructor.
     */
    public function __construct(Manager $oManager = null)
    {
        $this->oManager = $oManager ?? new Manager();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the manager instance
     *
     * @return Manager
     */
    public function getManager(): Manager
    {
        return $this->oManager;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns all discovered pipelines
     *
     * @return Pipeline[]
     */
    public function getPipelines(): array
    {
        $aPipelines = [];
        foreach (Components::available() as $oComponent) {

            $aClasses = $oComponent
                ->findClasses('DataMigration\\Pipeline')
                ->whichImplement(Pipeline::class);

            foreach ($aClasses as $sClass) {
                $aPipelines[] = new $sClass();
            }
        }

        return $aPipelines;
    }

    // --------------------------------------------------------------------------

    /**
     * Runs the supplied migration Pipelines
     *
     * @param Pipeline[]           $aPipelines The Pipelines to run
     * @param OutputInterface|null $oOutput    An OutputInterface to log to
     *
     * @return $this
     */
    public function run(array $aPipelines, OutputInterface $oOutput = null): self
    {
        $this->oManager->run($aPipelines, $oOutput);
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Runs the supplied migration Pipelines (as a dry-run)
     *
     * @param Pipeline[]           $aPipelines The Pipelines to run
     * @param OutputInterface|null $oOutput    An OutputInterface to log to
     *
     * @return $this
     */
    public function dryRun(array $aPipelines, OutputInterface $oOutput = null): self
    {
        $this->oManager->dryRun($aPipelines, $oOutput);
        return $this;
    }
}
