<?php
/**
 * Reepay payment extension for Magento
 *
 * @author      Radarsofthouse Team <info@radarsofthouse.dk>
 * @category    Radarsofthouse
 * @package     Radarsofthouse_Reepay
 * @copyright   Radarsofthouse (https://www.radarsofthouse.dk/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class Radarsofthouse_Reepay_Block_Form_Vipps extends Mage_Payment_Block_Form
{
    protected $_instructions;
    protected $_paymenticons;
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('reepay/form/vipps.phtml');
    }

    public function getInstructions()
    {
        if ($this->_instructions === null) {
            $this->_instructions = $this->getMethod()->getInstructions();
        }

        return $this->_instructions;
    }

    public function getPaymentIcons()
    {
        if ($this->_paymenticons === null) {
            $this->_paymenticons = $this->getMethod()->getPaymentIcons();
        }

        return $this->_paymenticons;
    }
}
