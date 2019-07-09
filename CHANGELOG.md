
# Changelog
## Version 1.0.17 on 03 July 2019

 - Force order status in the "invoice_settled" webhook.

## Version 1.0.17 on 03 July 2019

 - Fixed custom order status issue

## Version 1.0.16 on 27 June 2019

 - Check double authorization and double send email

## Version 1.0.15 on 4 June 2019

 - Check session expired when cancel order from payment window which session expired
 - Supportted to PHP 7.2.x
	
## Version 1.0.14 on 28 May 2019
 - Add new payment options (Klarna Pay Later, Klarna Pay Now, Forbrugsforeningen)
	
## Version 1.0.13 on 16 May 2019
 - Implement "Other" row for the extra fee or discount
 - Fixed shipping address error for virtual product order
 - Restore items in shopping cart when cancel from payment window and overlay 

## Version 1.0.12 on 14 May 2019

 - Implement partial capture and partial refund

## Version 1.0.11 on 8 May 2019

 - Implement vat in order lines
 - Implement "invoice_authorized" webhook
 - Fixed PHP notice on system.log

## Version 1.0.10 on 24 April 2019

 - Fixed configuration scope issue for multi-stores affecting order creation from Magento 1 back end

## Version 1.0.9 on 28 March 2019

 - Disabled refund webhook (Avoid refund request from magento to Reepay again).
 - Fixed PHP warning unserialize() and curl_setopt()

## Version 1.0.8 on 15 March 2019

 - Add payment link email template to installation file
	
## Version 1.0.7 on 4 March 2019
 - Change "order_status_before_payment" configuration to "order_status"
	
## Version 1.0.6 on 1 March 2019
 - Add condition to send payment link only Reepay payments

## Version 1.0.5 on 28 February 2019

 - Implement payment link
 - Fixed multi API keys issue for multi store

## Version 1.0.4 on 21 February 2019

 - Force viabill payment method into payment window always
 - Add local mapping between Magento and Reepay

## Version 1.0.3 on 14 February 2019

 - Add condition check mobile pay response
 - Change ajax post type
	
## Version 1.0.2 on 7 February 2019
 - Always request reepay session
 - Remove public API key setting
	
## Version 1.0.1 on 2 January 2019
 - Fixed table prefix issue when install module

## Version 1.0.0 on 19 October 2018

 - First release
