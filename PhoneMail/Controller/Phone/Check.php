<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Controller\Phone
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Controller\Phone;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use MagoArab\PhoneMail\Model\PhoneEmailGenerator;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Check implements HttpPostActionInterface, ActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    
    /**
     * @var PhoneEmailGenerator
     */
    private $phoneEmailGenerator;
    
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param PhoneEmailGenerator $phoneEmailGenerator
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        PhoneEmailGenerator $phoneEmailGenerator,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->phoneEmailGenerator = $phoneEmailGenerator;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $phone = $this->request->getParam('phone');
            
            if (empty($phone)) {
                return $result->setData([
                    'success' => false,
                    'exists' => false,
                    'message' => __('Phone number is required')
                ]);
            }
            
            // Clean the phone number
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            
            // Generate email from the phone
            $storeId = (int)$this->storeManager->getStore()->getId();
            
            try {
                // Try to generate email (will throw exception if exists)
                $email = $this->phoneEmailGenerator->generateEmailFromPhone($cleanPhone, $storeId);
                
                // If we get here, phone doesn't exist
                return $result->setData([
                    'success' => true,
                    'exists' => false,
                    'message' => ''
                ]);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                // Check if the message indicates the phone already exists
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    return $result->setData([
                        'success' => true,
                        'exists' => true,
                        'message' => __('An account with this phone number already exists.')
                    ]);
                }
                
                // Other errors
                return $result->setData([
                    'success' => false,
                    'exists' => false,
                    'message' => $e->getMessage()
                ]);
            }
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'exists' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}