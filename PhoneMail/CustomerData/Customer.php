<?php
/**
 * MagoArab PhoneMail Module
 *
 * @category  MagoArab
 * @package   MagoArab_PhoneMail
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\CustomerData;

use Magento\Customer\CustomerData\Customer as MagentoCustomer;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Helper\View;

class Customer extends MagentoCustomer
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param CurrentCustomer $currentCustomer
     * @param View $customerViewHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        CurrentCustomer $currentCustomer,
        View $customerViewHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($currentCustomer, $customerViewHelper);
        $this->customerRepository = $customerRepository;
    }

    /**
     * Get customer section data
     *
     * @return array
     */
    public function getSectionData()
    {
        $data = parent::getSectionData();
        
        if ($this->currentCustomer->getCustomerId()) {
            try {
                $customer = $this->customerRepository->getById($this->currentCustomer->getCustomerId());
                $addresses = $customer->getAddresses();
                
                // Get telephone from the default shipping address or first available address
                foreach ($addresses as $address) {
                    if ($address->getTelephone()) {
                        $data['telephone'] = $address->getTelephone();
                        break;
                    }
                }
            } catch (\Exception $e) {
                // Do nothing if we can't get the customer data
            }
        }
        
        return $data;
    }
}