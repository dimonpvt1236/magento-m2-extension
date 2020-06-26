<?php

namespace Drip\Connect\Observer\Customer;

/**
 * After address save observer
 */
class AfterAddressSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerCustomerFactory;

    /** @var \Magento\Framework\Serialize\Serializer\Json */
    protected $json;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Model\ConfigurationFactory $configFactory,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory
    ) {
        parent::__construct($configFactory, $logger);
        $this->registry = $registry;
        $this->customerHelper = $customerHelper;
        $this->customerCustomerFactory = $customerCustomerFactory;
        $this->json = $json;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        // change was not done in address we use in drip
        if (empty($this->registry->registry(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR))) {
            return;
        }

        $address = $observer->getDataObject();

        $customer = $this->customerCustomerFactory->create()->load($address->getCustomerId());

        // TODO: This might be triggering in the context of the user. We should
        //       have a test for this and use the store view context if available.
        $storeId = $this->customerHelper->getCustomerStoreId($customer);
        $config = $this->configFactory->create($storeId);

        if ($this->isAddressChanged($address)) {
            $this->customerHelper->proceedAccount($customer, $config);
        }

        $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR);
    }

    /**
     * compare orig and new data
     *
     * @param \Magento\Customer\Model\Address $address
     */
    protected function isAddressChanged($address)
    {
        $oldData = $this->registry->registry(self::REGISTRY_KEY_CUSTOMER_OLD_ADDR);
        $newData = $this->customerHelper->getAddressFields($address);

        return ($this->json->serialize($oldData) != $this->json->serialize($newData));
    }
}
