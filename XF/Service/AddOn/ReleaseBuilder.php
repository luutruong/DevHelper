<?php

namespace DevHelper\XF\Service\AddOn;

use DevHelper\Util\ZipArchiveToDir;

class ReleaseBuilder extends XFCP_ReleaseBuilder
{
    /**
     * @var string
     */
    protected $zipArchiveToDir = '';

    /**
     * @return void
     */
    protected function prepareFsAdapters()
    {
        parent::prepareFsAdapters();

        $this->zipArchiveToDir = strval(getenv('DEVHELPER_ZIP_ARCHIVE_TO_DIR'));
        if ($this->zipArchiveToDir !== '') {
            $ds = DIRECTORY_SEPARATOR;
            $dir = $this->addOn->getReleasesDirectory() . $ds . ltrim($this->zipArchiveToDir, $ds);

            /** @var \ZipArchive $zipArchiveToDir */
            $zipArchiveToDir = new ZipArchiveToDir($dir);
            $this->zipArchive = $zipArchiveToDir;
        }
    }

    /**
     * @return void
     */
    public function finalizeRelease()
    {
        parent::finalizeRelease();

        if ($this->zipArchiveToDir !== '') {
            $dir = $this->zipArchive->getStatusString();
            $list = ['src/addons/' . $this->addOn->getAddOnId()];

            $additionalFiles = $this->addOn->offsetGet('additional_files');
            if (is_array($additionalFiles)) {
                $list = array_merge($list, $additionalFiles);
            }

            file_put_contents($dir . DIRECTORY_SEPARATOR . 'list.txt', implode("\n", $list) . "\n");
        }
    }
}