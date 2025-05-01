<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Plugin\Quote
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Plugin\Quote;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;

class QuoteRepository
{
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CustomerSession
     */
    private $customerSession;
    
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * Constructor
     *
     * @param CartManagementInterface $cartManagement
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession = null,
        LoggerInterface $logger = null
    ) {
        $this->cartManagement = $cartManagement;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession ?: ObjectManager::getInstance()->get(CheckoutSession::class);
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * Around get plugin
     *
     * @param CartRepositoryInterface $subject
     * @param \Closure $proceed
     * @param mixed $cartId
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function aroundGet(
        CartRepositoryInterface $subject,
        \Closure $proceed,
        $cartId
    ) {
        // سجل معلومات للتشخيص
        $this->logger->info('PhoneMail: aroundGet called with cartId: ' . ($cartId ?: 'empty'));
        
        // إذا كان هناك cartId، فقط استمر في التنفيذ الأصلي
        if (!empty($cartId)) {
            try {
                return $proceed($cartId);
            } catch (NoSuchEntityException $e) {
                $this->logger->info('PhoneMail: Cart not found with ID: ' . $cartId);
                // سنتعامل مع هذا الخطأ لاحقًا
            }
        }
        
        // إذا وصلنا إلى هنا، فلا يوجد cartId صالح
        // تحقق مما إذا كان المستخدم مسجل الدخول
        if ($this->customerSession->isLoggedIn()) {
            $customerId = (int)$this->customerSession->getCustomerId();
            $this->logger->info('PhoneMail: Customer is logged in, ID: ' . $customerId);
            
            try {
                // محاولة إنشاء عربة جديدة للعميل
                $newCartId = $this->cartManagement->createEmptyCartForCustomer($customerId);
                $this->logger->info('PhoneMail: Created new cart ID: ' . $newCartId);
                
                // وضع عربة التسوق الجديدة في الجلسة
                $this->checkoutSession->setQuoteId($newCartId);
                
                // تنفيذ العملية الأصلية مع عربة التسوق الجديدة
                return $proceed($newCartId);
            } catch (\Exception $createException) {
                $this->logger->error('PhoneMail: Error creating cart: ' . $createException->getMessage());
                // سنتجاهل هذا الخطأ والاستمرار في التنفيذ
            }
        } else {
            $this->logger->info('PhoneMail: Customer not logged in');
        }
        
        // كتدبير أخير، قم بإنشاء عربة فارغة مؤقتة للزوار
        if (empty($cartId) && !$this->customerSession->isLoggedIn()) {
            try {
                // إنشاء عربة للزائر
                $guestCartId = $this->cartManagement->createEmptyCart();
                $this->logger->info('PhoneMail: Created guest cart ID: ' . $guestCartId);
                
                // وضع عربة التسوق الجديدة في الجلسة
                $this->checkoutSession->setQuoteId($guestCartId);
                
                // تنفيذ العملية الأصلية مع عربة التسوق الجديدة
                return $proceed($guestCartId);
            } catch (\Exception $guestException) {
                $this->logger->error('PhoneMail: Error creating guest cart: ' . $guestException->getMessage());
            }
        }
        
        // كتدبير أخير جدًا، فقط استمر ودع النظام يتعامل مع الخطأ
        return $proceed($cartId);
    }
}