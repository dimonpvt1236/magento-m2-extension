<?php

namespace Drip\Connect\Helper;

class Customer extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var \Magento\Customer\Model\GroupFactory */
    protected $customerGroupFactory;

    /** @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress */
    protected $remoteAddress;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateSubscriberFactory */
    protected $connectApiCallsHelperCreateUpdateSubscriberFactory;

    /** @var \Drip\Connect\Model\ApiCalls\Helper\RecordAnEventFactory */
    protected $connectApiCallsHelperRecordAnEventFactory;

    /** @var \Magento\Framework\HTTP\Header */
    protected $header;

    /** @var \Drip\Connect\Helper\Data */
    protected $connectHelper;

    /**
     * constructor
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\GroupFactory $customerGroupFactory,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Drip\Connect\Model\ApiCalls\Helper\CreateUpdateSubscriberFactory $connectApiCallsHelperCreateUpdateSubscriberFactory,
        \Drip\Connect\Model\ApiCalls\Helper\RecordAnEventFactory $connectApiCallsHelperRecordAnEventFactory,
        \Magento\Framework\HTTP\Header $header,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        parent::__construct($context);
        $this->customerGroupFactory = $customerGroupFactory;
        $this->remoteAddress = $remoteAddress;
        $this->connectApiCallsHelperCreateUpdateSubscriberFactory = $connectApiCallsHelperCreateUpdateSubscriberFactory;
        $this->connectApiCallsHelperRecordAnEventFactory = $connectApiCallsHelperRecordAnEventFactory;
        $this->header = $header;
        $this->connectHelper = $connectHelper;
    }

    /**
     * prepare array of customer data we use to send in drip
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param bool $updatableOnly leave only those fields which are used in update action
     */
    public function prepareCustomerData($customer, $updatableOnly = true)
    {
        if ($customer->getOrigData() && $customer->getData('email') != $customer->getOrigData('email')) {
            $newEmail = $customer->getData('email');
        } else {
            $newEmail = '';
        }
        $data = array (
            'email' => $customer->getEmail(),
            'new_email' => ($newEmail ? $newEmail : ''),
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'user_agent' => $this->header->getHttpUserAgent(),
            'custom_fields' => array(
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'birthday' => $customer->getDob(),
                'gender' => $this->getGenderText($customer->getGender()),
                'magento_source' => $this->connectHelper->getArea(),
                'magento_account_created' => $customer->getCreatedAt(),
                'magento_customer_group' => $this->customerGroupFactory->create()->load($customer->getGroupId())->getCustomerGroupCode(),
                'magento_store' => (int) $customer->getStoreId(),
                'accepts_marketing' => ($customer->getIsSubscribed() ? 'yes' : 'no'),
            ),
        );

        /*if ($customer->getDefaultShippingAddress()) {
            $data = array_merge_recursive($data, array('custom_fields'=>$this->getAddressFields($customer->getDefaultShippingAddress())));
        }*/

        if ($updatableOnly) {
            unset($data['custom_fields']['magento_account_created']);
            unset($data['ip_address']);
            unset($data['user_agent']);
        }

        return $data;
    }

    /**
     * get address fields
     *
     * @param \Magento\Customer\Model\Address $address
     */
    public function getAddressFields($address)
    {
        return array (
            'city' => (string) $address->getCity(),
            'state' => (string) $address->getRegion(),
            'zip_code' => (string) $address->getPostcode(),
            'country' => (string) $address->getCountry(),
            'phone_number' => (string) $address->getTelephone(),
        );
    }

    /**
     * @param int $genderCode
     *
     * @return string
     */
    public function getGenderText($genderCode) {
        if ($genderCode == 1) {
            $gender = 'Male';
        } else if ($genderCode == 2) {
            $gender = 'Female';
        } else {
            $gender = '';
        }
        return $gender;
    }

    /**
     * drip actions for customer account create
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function proceedAccountNew($customer)
    {
        $customerData = $this->prepareCustomerData($customer, false);

        $this->connectApiCallsHelperCreateUpdateSubscriberFactory->create([
            'data' => $customerData
        ])->call();

        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'data' => [
                'email' => $customer->getEmail(),
                'action' => \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_NEW,
            ]
        ])->call();
    }

    /**
     * drip actions for customer account change
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function proceedAccount($customer)
    {
        $customerData = $this->prepareCustomerData($customer);

        $this->connectApiCallsHelperCreateUpdateSubscriberFactory->create([
            'data' => $customerData
        ])->call();

        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'data' => [
                'email' => $customer->getEmail(),
                'action' => \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_UPDATED,
            ]
        ])->call();
    }

    /**
     * drip actions for customer account delete
     *
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function proceedAccountDelete($customer)
    {
        $this->connectApiCallsHelperRecordAnEventFactory->create([
            'data' => [
                'email' => $customer->getEmail(),
                'action' => \Drip\Connect\Model\ApiCalls\Helper\RecordAnEvent::EVENT_CUSTOMER_DELETED,
            ]
        ])->call();
    }
}
