<?php

namespace Drip\Connect\Observer\Customer;

class GuestSubscriberAttempt extends \Drip\Connect\Observer\Base
{
    /** @var \Magento\Newsletter\Model\SubscriberFactory */
    protected $subscriberFactory;

    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {
        parent::__construct($connectHelper, $registry);
        $this->subscriberFactory = $subscriberFactory;
        $this->request = $request;
    }

    /**
     * guest subscribe on site
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->connectHelper->isModuleActive()) {
            return;
        }

        $email = $this->request->getParam('email');

        $subscriber = $this->subscriberFactory->create()->loadByEmail($email);

        $this->registry->unregister(self::REGISTRY_KEY_NEW_GUEST_SUBSCRIBER);

        if (! $subscriber->getId()) {
            $this->registry->register(self::REGISTRY_KEY_NEW_GUEST_SUBSCRIBER, true);
        }
    }
}
