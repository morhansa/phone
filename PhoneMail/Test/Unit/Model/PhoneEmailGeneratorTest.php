<?php
/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\Test\Unit\Model
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
declare(strict_types=1);

namespace MagoArab\PhoneMail\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use MagoArab\PhoneMail\Model\PhoneEmailGenerator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PhoneEmailGeneratorTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var PhoneEmailGenerator
     */
    private $phoneEmailGenerator;

    /**
     * Set up test
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);

        $this->phoneEmailGenerator = new PhoneEmailGenerator(
            $this->scopeConfig,
            $this->storeManager,
            $this->customerRepository
        );
    }

    /**
     * Test generating email using store domain
     */
    public function testGenerateEmailFromPhoneUsingStoreDomain()
    {
        // Test data
        $phoneNumber = '+1 (234) 567-8901';
        $storeId = 1;
        $storeDomain = 'example.com';
        $expectedEmail = '12345678901@example.com';

        // Configure mocks
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(
                PhoneEmailGenerator::XML_PATH_USE_CUSTOM_DOMAIN,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn(false);

        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn('https://www.example.com/');

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->willReturn($store);

        $this->customerRepository->expects($this->once())
            ->method('get')
            ->with($expectedEmail)
            ->willThrowException(new NoSuchEntityException(__('No such entity')));

        // Execute test
        $result = $this->phoneEmailGenerator->generateEmailFromPhone($phoneNumber, $storeId);

        // Assert result
        $this->assertEquals($expectedEmail, $result);
    }

    /**
     * Test generating email using custom domain
     */
    public function testGenerateEmailFromPhoneUsingCustomDomain()
    {
        // Test data
        $phoneNumber = '+1 (234) 567-8901';
        $storeId = 1;
        $customDomain = 'custom-domain.com';
        $expectedEmail = '12345678901@custom-domain.com';

        // Configure mocks
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(
                PhoneEmailGenerator::XML_PATH_USE_CUSTOM_DOMAIN,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn(true);

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                PhoneEmailGenerator::XML_PATH_CUSTOM_DOMAIN,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn($customDomain);

        $this->customerRepository->expects($this->once())
            ->method('get')
            ->with($expectedEmail)
            ->willThrowException(new NoSuchEntityException(__('No such entity')));

        // Execute test
        $result = $this->phoneEmailGenerator->generateEmailFromPhone($phoneNumber, $storeId);

        // Assert result
        $this->assertEquals($expectedEmail, $result);
    }

    /**
     * Test exception is thrown when email already exists
     */
    public function testExceptionWhenEmailAlreadyExists()
    {
        // Test data
        $phoneNumber = '+1 (234) 567-8901';
        $storeId = 1;
        $customDomain = 'existing-email-domain.com';
        $existingEmail = '12345678901@existing-email-domain.com';
        
        // Configure mocks
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(
                PhoneEmailGenerator::XML_PATH_USE_CUSTOM_DOMAIN,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn(true);

        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(
                PhoneEmailGenerator::XML_PATH_CUSTOM_DOMAIN,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn($customDomain);

        $customerMock = $this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class);
        $this->customerRepository->expects($this->once())
            ->method('get')
            ->with($existingEmail)
            ->willReturn($customerMock);

        // Set exception expectation
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('A customer with the same phone number already exists');

        // Execute test
        $this->phoneEmailGenerator->generateEmailFromPhone($phoneNumber, $storeId);
    }
    
    /**
     * Test exception is thrown when phone number is empty
     */
    public function testExceptionWhenPhoneNumberIsEmpty()
    {
        // Set exception expectation
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Phone number is required to generate email address');

        // Execute test with empty phone number
        $this->phoneEmailGenerator->generateEmailFromPhone('');
    }
}