<?php

namespace Drip\Connect\Observer\Customer\Admin;

/**
 * This observer exists to allow the SaveAfter observer to know what the data
 * looked like before. This is accomplished with the Registry.
 */

class SaveBefore extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Newsletter\Model\SubscriberFactory */
    protected $subscriberFactory;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerCustomerFactory;

    /** @var \Magento\Framework\Session\SessionManagerInterface */
    protected $coreSession;

    /** @var \Magento\Framework\Registry */
    protected $registry;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Registry $registry,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Customer $customerHelper,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Customer\Model\CustomerFactory $customerCustomerFactory
    ) {
        parent::__construct($connectHelper, $logger);
        $this->registry = $registry;
        $this->customerHelper = $customerHelper;
        $this->subscriberFactory = $subscriberFactory;
        $this->coreSession = $coreSession;
        $this->customerCustomerFactory = $customerCustomerFactory;
    }

    /**
     * - check if customer new
     * - store old customer data (which is used in drip) to compare with later
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();

        $acceptsMarketing = $this->acceptsMarketing($customer->getEmail());

        if (empty($this->coreSession->getCustomerIsNew())) {
            // need session here instead of registry
            // b/c of two 'before' events occurs on customer create
            // and one final adminhtml_customer_save_after
            // (which is used to track customer's newsletter state in admin)
            $this->coreSession->setCustomerIsNew((int)$customer->isObjectNew());
        }

        if (!$customer->isObjectNew()) {
            $orig = $this->customerCustomerFactory->create()->load($customer->getId());
            $data = $this->customerHelper->prepareCustomerData($orig);
            $data['custom_fields']['accepts_marketing'] = $acceptsMarketing ? 'yes' : 'no';
            $this->registry->unregister(self::REGISTRY_KEY_CUSTOMER_OLD_DATA);
            $this->registry->register(self::REGISTRY_KEY_CUSTOMER_OLD_DATA, $data);
        } else {
            $customer->setDrip(1);
        }
    }

    protected function acceptsMarketing($email) {
        $subscriber = $this->subscriberFactory->create()->loadByEmail($email);
        return $subscriber->getId() && $subscriber->getSubscriberStatus() == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED;
    }
}
