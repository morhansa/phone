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

namespace MagoArab\PhoneMail\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Store\Model\StoreManagerInterface;

class DomainOptions implements ArrayInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor
     *
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Get options array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        
        // Add store domain as option
        try {
            $store = $this->storeManager->getStore();
            $baseUrl = $store->getBaseUrl();
            $parsedUrl = parse_url($baseUrl);
            $domain = $parsedUrl['host'] ?? '';
            $domain = preg_replace('/^www\./', '', $domain);
            
            $options[] = [
                'value' => $domain,
                'label' => __('Store Domain (%1)', $domain)
            ];
        } catch (\Exception $e) {
            // In case of any error, provide default option
            $options[] = [
                'value' => 'store-domain.com',
                'label' => __('Store Domain')
            ];
        }
        
        // Add custom domain option
        $options[] = [
            'value' => 'custom',
            'label' => __('Custom Domain')
        ];
        
        return $options;
    }
}