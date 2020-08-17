<?php

namespace CodeCustom\Portmone\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Language implements OptionSourceInterface
{

    /**
     * @return array|\string[][]
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'ru', 'label' => 'ru'],
            ['value' => 'uk', 'label' => 'uk'],
            ['value' => 'en', 'label' => 'en']
        ];
    }

}
