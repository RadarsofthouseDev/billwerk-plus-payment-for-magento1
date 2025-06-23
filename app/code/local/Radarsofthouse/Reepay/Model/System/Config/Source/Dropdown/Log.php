<?php

/**
 * Frisbii Pay extension for Magento
 *
 * @author      Radarsofthouse Team <info@radarsofthouse.dk>
 * @category    Radarsofthouse
 * @package     Radarsofthouse_Reepay
 * @copyright   Radarsofthouse (https://www.radarsofthouse.dk/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class Radarsofthouse_Reepay_Model_System_Config_Source_Dropdown_Log
{
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label' => __('Disabled')),
            array('value' => 1, 'label' => __('Only Frisbii API')),
            array('value' => 2, 'label' => __('Debug mode')),
        );
    }
}
