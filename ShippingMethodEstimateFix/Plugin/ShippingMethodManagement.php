<?php

namespace Chuffman\ShippingMethodEstimateFix\Plugin;

class ShippingMethodManagement
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;
    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    private $quoteAddressFactory;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory
    )
    {
        $this->quoteRepository = $quoteRepository;
        $this->addressRepository = $addressRepository;
        $this->quoteAddressFactory = $quoteAddressFactory;
    }

    /**
     * Set address on quote and estimate shipping by full address.
     * Inspired by comments on this issue: https://github.com/magento/magento2/issues/3789
     *
     * @param \Magento\Quote\Model\ShippingMethodManagement $subject
     * @param callable $proceed
     * @param $cartId
     * @param $addressId
     * @return array|\Magento\Quote\Api\Data\ShippingMethodInterface[]
     */
    public function aroundEstimateByAddressId(\Magento\Quote\Model\ShippingMethodManagement $subject, callable $proceed, $cartId, $addressId)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }

        $address = $this->addressRepository->getById($addressId);

        //convert customer address to quote address
        $quoteAddress = $this->quoteAddressFactory->create();
        $quoteAddress->importCustomerAddressData($address);

        //get all shipping methods using the full ("extended") address
        $shippingMethods = $subject->estimateByExtendedAddress($cartId, $quoteAddress);

        return $shippingMethods;
    }
}