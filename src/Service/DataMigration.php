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
use HelloPablo\Exception\PipelineException;
use HelloPablo\Exception\PipelineException\CommitException;
use HelloPablo\Exception\PipelineException\PrepareException;
use Nails\Common\Service\FileCache;
use Nails\Components;
use Nails\Factory;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DataMigration
 *
 * @package Nails\DataMigration\Service
 */
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
        if ($oManager === null) {
            /** @var FileCache $oFileCache */
            $oFileCache = Factory::service('FileCache');
            /** @var \DateTime $oNow */
            $oNow = \Nails\Factory::factory('DateTime');

            $oManager = new Manager(
                $oFileCache->getDir() . 'data-migration-' . $oNow->format('Ymdhis') . '-' . uniqid()
            );
        }

        $this->oManager = $oManager;
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
     * Sets the output interfce to use
     *
     * @param OutputInterface|null $oOutputInterface
     */
    public function setOutputInterface(?OutputInterface $oOutputInterface): self
    {
        $this->oManager->setOutputInterface($oOutputInterface);
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the active OutputInterface
     *
     * @return OutputInterface|null
     */
    public function getOutputInterface(): ?OutputInterface
    {
        return $this->oManager->getOutputInterface();
    }

    // --------------------------------------------------------------------------

    /**
     * Set the debug mode
     *
     * @param bool $bDebug Whether to turn on debug mode or not
     *
     * @return $this
     */
    public function setDebug(bool $bDebug): self
    {
        $this->oManager->setDebug($bDebug);
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the system is in debug mode or not
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->oManager->isDebug();
    }

    // --------------------------------------------------------------------------

    /**
     * Set the dry-run mode
     *
     * @param bool $bDryRun Whether to turn on dry-run mode or not
     *
     * @return $this
     */
    public function setDryRun(bool $bDryRun): self
    {
        $this->oManager->setDryRun($bDryRun);
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the system is in dry-run mode or not
     *
     * @return bool
     */
    public function isDryRun(): bool
    {
        return $this->oManager->isDryRun();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns all discovered Pipelines
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
     * Checks connectors
     *
     * @param Pipeline[] $aPipelines The Pipelines to check
     *
     * @return $this
     */
    public function checkConnectors(array $aPipelines): self
    {
        $this
            ->oManager
            ->checkConnectors($aPipelines);

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Prepares the supplied migration Pipelines
     *
     * @param Pipeline[] $aPipelines The Pipelines to prepare
     *
     * @return $this
     */
    public function prepare(array $aPipelines): self
    {
        $this
            ->oManager
            ->prepare($aPipelines);

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Commits the supplied migration Pipelines
     *
     * @param Pipeline[] $aPipelines The Pipelines to commit
     *
     * @return $this
     */
    public function commit(array $aPipelines): self
    {
        $this
            ->oManager
            ->commit($aPipelines);

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns any warnings which have bveen generated
     *
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->oManager->getWarnings();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns any errors encountered during preparation
     *
     * @return PrepareException[]
     */
    public function getPrepareErrors(): array
    {
        return $this->oManager->getPrepareErrors();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns any errors encountered during commit
     *
     * @return CommitException[]
     */
    public function getCommitErrors(): array
    {
        return $this->oManager->getCommitErrors();
    }
}
