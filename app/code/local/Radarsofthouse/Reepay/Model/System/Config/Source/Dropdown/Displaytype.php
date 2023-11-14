<?php
/**
 * Billwerk+ payment extension for Magento
 *
 * @author      Radarsofthouse Team <info@radarsofthouse.dk>
 * @category    Radarsofthouse
 * @package     Radarsofthouse_Reepay
 * @copyright   Radarsofthouse (https://www.radarsofthouse.dk/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class Radarsofthouse_Reepay_Model_System_Config_Source_Dropdown_Displaytype
{
    /**
     * Return Reepay payment display type
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label' => __('Embedded')),
            array('value' => 2, 'label' => __('Overlay (Modal)')),
            array('value' => 3, 'label' => __('Window')),
        );
    }
}
