<?php
namespace Drip\Connect\Model\ApiCalls\Helper;

class RecordAnEvent extends \Drip\Connect\Model\ApiCalls\Helper
{
    const EVENT_CUSTOMER_NEW = 'Customer created';
    const EVENT_CUSTOMER_UPDATED = 'Customer updated';
    const EVENT_CUSTOMER_DELETED = 'Customer deleted';
    const EVENT_CUSTOMER_LOGIN = 'Customer logged in';
    const EVENT_ORDER_CREATED = 'Order created';
    const EVENT_ORDER_COMPLETED = 'Order fulfilled';
    const EVENT_ORDER_REFUNDED = 'Order refunded';
    const EVENT_ORDER_CANCELED = 'Order canceled';
    const EVENT_WISHLIST_ADD_PRODUCT = 'Added item to wishlist';
    const EVENT_WISHLIST_REMOVE_PRODUCT = 'Removed item from wishlist';

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /** @var \Drip\Connect\Model\ApiCalls\BaseFactory */
    protected $connectApiCallsBaseFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Request\BaseFactory */
    protected $connectApiCallsRequestBaseFactory;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var \Magento\Framework\App\ProductMetadataInterface */
    protected $productMetadata;

    /** @var \Magento\Framework\Module\ResourceInterface */
    protected $moduleResource;

    public function __construct(
        \Drip\Connect\Model\ApiCalls\BaseFactory $connectApiCallsBaseFactory,
        \Drip\Connect\Model\ApiCalls\Request\BaseFactory $connectApiCallsRequestBaseFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Drip\Connect\Helper\Data $connectHelper,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        $data = []
    ) {
        $this->connectApiCallsBaseFactory = $connectApiCallsBaseFactory;
        $this->connectApiCallsRequestBaseFactory = $connectApiCallsRequestBaseFactory;
        $this->scopeConfig = $scopeConfig;
        $this->connectHelper = $connectHelper;
        $this->moduleResource = $moduleResource;
        $this->productMetadata = $productMetadata;

        $this->apiClient = $this->connectApiCallsBaseFactory->create([
            'options' => [
                'endpoint' => $this->scopeConfig->getValue(
                    'dripconnect_general/api_settings/account_id',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ) . '/' . self::ENDPOINT_EVENTS
            ]
        ]);

        if (!empty($data) && is_array($data)) {
            $data['properties']['source'] = 'magento';
            $data['properties']['magento_source'] = $this->connectHelper->getArea();
            $data['properties']['version'] = 'Magento ' . $this->productMetadata->getVersion() . ', '
                                           . 'Drip Extension ' . $this->moduleResource->getDbVersion('Drip_Connect');
        }

        $eventsInfo = [
            'events' => [
                $data
            ]
        ];

        $this->request = $this->connectApiCallsRequestBaseFactory->create()
            ->setMethod(\Zend_Http_Client::POST)
            ->setRawData(json_encode($eventsInfo));
    }
}
