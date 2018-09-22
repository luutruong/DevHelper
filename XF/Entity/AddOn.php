<?php

namespace DevHelper\XF\Entity;

use DevHelper\XF\AddOn\Manager;

class AddOn extends XFCP_AddOn
{
    protected function _preSave()
    {
        parent::_preSave();

        if ($this->isChanged('active') && $this->active) {
            /** @var Manager $addOnManager */
            $addOnManager = $this->app()->addOnManager();
            /** @var \XF\AddOn\AddOn|null $addOn */
            $addOn = $addOnManager->getById($this->addon_id);
            if (!$addOn) {
                return;
            }

            $config = $addOnManager->getDevHelperConfig($addOn);
            if (!empty($config[Manager::CONFIG_ADDON_IDS_AUTO_ENABLE])) {
                foreach ($config[Manager::CONFIG_ADDON_IDS_AUTO_ENABLE] as $addOnId) {
                    /** @var \XF\Entity\AddOn|null $addOn */
                    $addOn = $this->_em->find('XF:AddOn', $addOnId);
                    if ($addOn !== null && !$addOn->active) {
                        $addOn->active = true;
                        $this->addCascadedSave($addOn);
                    }
                }
            }

        }
    }
}
