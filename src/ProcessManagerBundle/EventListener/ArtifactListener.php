<?php

declare(strict_types=1);

namespace ProcessManagerBundle\EventListener;

use Pimcore\Event\Model\AssetEvent;
use Pimcore\File as FileHelper;
use Pimcore\Model\Asset;
use ProcessManagerBundle\Model\Process;
use ProcessManagerBundle\Model\ProcessInterface;
use ProcessManagerBundle\Monolog\ProcessLogEvent;

/**
 * Class ArtifactListener.
 */
class ArtifactListener
{
    private const METADATA_MARKER = 'process_manager.process';

    /**
     * @param ImportDefinitionEvent $event
     */
    public function onProcessLogEvent(ProcessLogEvent $event)
    {
        // receives it via the logger from self::onArtifactEvent()
        $record = $event->getRecord();

        if (false === array_key_exists('artifact', $record['context'])) {
            return;
        }
        
        /** @var ProcessInterface $process */
        $process = $record['extra']['process'];

        // ProcessInterface instance must have a relation to its ExecutableInterface instance
        // TODO: need asset path in Executable config, hardcoded for now
        $artifactAssetPath = '/exports';

        $artifactPath = $record['context']['artifact'];
        $fileHandle = fopen($artifactPath, 'rb', false, FileHelper::getContext());

        $artifact = new Asset();
        $artifact->setParent(Asset\Service::createFolderByPath($artifactAssetPath));
        $artifact->setStream($fileHandle);
        // TODO: name the file something like "<executable name> artifact <current datetime>" or provide an artifact name template in the Executable config
        $artifact->setFilename(pathinfo($artifactPath, PATHINFO_FILENAME));
        // TODO: add an event listener which sets Artifact=null (or deletes the Process) if the user deletes this asset
        $artifact->addMetadata(self::METADATA_MARKER, 'number', $process->getId());
        $artifact->save();

        fclose($fileHandle);

        $process->setArtifact($artifact);
        $process->save();
    }

    public function onArtifactAssetDelete(AssetEvent $event)
    {
        $asset = $event->getAsset();

        $id = $asset->getMetadata(self::METADATA_MARKER);
        if (empty($id)) {
            return;
        }

        $process = Process::getById($id);
        if (null === $process) {
            return;
        }
        $process->setArtifact(null);
        $process->save();
    }
}