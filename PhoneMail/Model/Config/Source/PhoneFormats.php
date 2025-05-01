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

use Magento\Framework\Data\OptionSourceInterface;

class PhoneFormats implements OptionSourceInterface
{
    /**
     * Get options array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'international', 'label' => __('International Format (+XX)')],
            ['value' => 'national', 'label' => __('National Format (0X)')],
            ['value' => 'digits_only', 'label' => __('Digits Only')],
            ['value' => 'with_spaces', 'label' => __('Allow Spaces')],
            ['value' => 'with_dashes', 'label' => __('Allow Dashes')]
        ];
    }
}