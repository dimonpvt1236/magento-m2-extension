<?php

namespace Drip\Connect\Observer\Customer;

class NewsletterSave extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Customer */
    protected $customerHelper;

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /** @var \Magento\Newsletter\Model\SubscriberFactory */
    protected $subscriberFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Framework\App\Request\Http */
    protected $request;

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
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
    ) {
        parent::__construct($connectHelper, $logger);
        $this->registry = $registry;
        $this->subscriberFactory = $subscriberFactory;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->customerHelper = $customerHelper;
    }

    /**
     * save old customer subscription state
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $customerEmail = $this->customerSession->getCustomer()
            ->setStoreId($this->storeManager->getStore()->getId())
            ->getEmail();

        $subscriber = $this->subscriberFactory->create()->loadByEmail($customerEmail);

        if (! $subscriber->getId()) {
            $acceptsMarketing = 'no';
        } else {
            if ($subscriber->getSubscriberStatus() == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
                $acceptsMarketing = 'yes';
            } else {
                $acceptsMarketing = 'no';
            }
        }

        $this->registry->unregister(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE);
        $this->registry->register(self::REGISTRY_KEY_SUBSCRIBER_PREV_STATE, $acceptsMarketing);

        if ((int) $this->request->getparam('is_subscribed')) {
            $this->registry->unregister(self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT);
            $this->registry->register(self::REGISTRY_KEY_SUBSCRIBER_SUBSCRIBE_INTENT, 1);
        }
    }
}
