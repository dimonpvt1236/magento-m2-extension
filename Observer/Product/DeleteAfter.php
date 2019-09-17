<?php

namespace Drip\Connect\Observer\Product;

class DeleteAfter extends \Drip\Connect\Observer\Base
{
    /** @var \Drip\Connect\Helper\Product */
    protected $productHelper;

    /**
     * constructor
     */
    public function __construct(
        \Drip\Connect\Helper\Product $productHelper,
        \Drip\Connect\Logger\Logger $logger,
        \Drip\Connect\Helper\Data $connectHelper
    ) {
        $this->productHelper = $productHelper;
        parent::__construct($connectHelper, $logger);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function executeWhenEnabled(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();

        if (! $product->getId()) {
            return;
        }

        $this->proceedProductDelete($product);
    }

    /**
     * drip actions for product create
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    protected function proceedProductDelete($product)
    {
        $this->productHelper->proceedProductDelete($product);
    }
}
