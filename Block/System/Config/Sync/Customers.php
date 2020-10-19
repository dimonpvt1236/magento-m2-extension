<?php
namespace Drip\Connect\Block\System\Config\Sync;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Customers extends \Drip\Connect\Block\System\Config\Sync\Button
{
    const BUTTON_TEMPLATE = 'system/config/sync/customers.phtml';

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('drip/batch/customers');
    }

    public function isSyncAvailable()
    {
        if (!$this->config->isEnabled()) {
            return false;
        }
        $syncState = $this->config->getCustomersSyncState();
        if ($syncState != \Drip\Connect\Model\Source\SyncState::READY &&
            $syncState != \Drip\Connect\Model\Source\SyncState::READYERRORS) {
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getStateLabel()
    {
        return \Drip\Connect\Model\Source\SyncState::getLabel($this->config->getCustomersSyncState());
    }
}