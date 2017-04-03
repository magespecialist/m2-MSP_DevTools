/**
 * MageSpecialist
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magespecialist.it so we can send you a copy immediately.
 *
 * @category   MSP
 * @package    MSP_DevTools
 * @copyright  Copyright (c) 2017 Skeeller srl (http://www.magespecialist.it)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

var config = {
    map: {
        '*': {
            // For version 2.1.x
            'Magento_Ui/js/lib/knockout/template/loader': 'MSP_DevTools/js/lib/knockout/template/loader',
            'Core:Magento_Ui/js/lib/knockout/template/loader': 'Magento_Ui/js/lib/knockout/template/loader',

            // For version 2.0.x
            'Magento_Ui/js/lib/ko/template/loader': 'MSP_DevTools/js/lib/ko/template/loader',
            'Core:Magento_Ui/js/lib/ko/template/loader': 'Magento_Ui/js/lib/ko/template/loader'
        }
    }
};
