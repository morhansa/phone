<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Controller\Ajax
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;

class CheckPhone implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    
    /**
     * @var CollectionFactory
     */
    private $customerCollectionFactory;
    
    /**
     * @var RequestInterface
     */
    private $request;
    
    /**
     * Constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CollectionFactory $customerCollectionFactory
     * @param RequestInterface $request
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CollectionFactory $customerCollectionFactory,
        RequestInterface $request
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->request = $request;
    }
    
    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $phone = $this->request->getParam('phone');
        
        if (!$phone) {
            return $result->setData(['exists' => false]);
        }
        
        // Clean phone number
        $phone = preg_replace('/\D/', '', $phone);
        
        // Check if phone exists in customer attribute
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToFilter('telephone', $phone);
        
        $exists = $collection->getSize() > 0;
        
        return $result->setData([
            'exists' => $exists,
            'loginUrl' => $exists ? $this->getUrl('customer/account/login') : '',
            'forgotUrl' => $exists ? $this->getUrl('customer/account/forgotpassword') : ''
        ]);
    }
    
    /**
     * Get URL
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    private function getUrl($route, $params = [])
    {
        return $this->_url->getUrl($route, $params);
    }
}