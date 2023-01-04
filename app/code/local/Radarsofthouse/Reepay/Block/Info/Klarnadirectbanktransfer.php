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
class Radarsofthouse_Reepay_Block_Info_Klarnadirectbanktransfer extends Mage_Payment_Block_Info
{
    protected $_instructions;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('reepay/info/klarnadirectbanktransfer.phtml');
    }

    public function getInstructions()
    {
        if ($this->_instructions === null) {
            $this->_instructions = $this->getInfo()->getAdditionalInformation('instructions');
            if (empty($this->_instructions)) {
                $this->_instructions = $this->getMethod()->getInstructions();
            }
        }

        return $this->_instructions;
    }

    public function getPaymentIcons()
    {
        return '';
    }
}
