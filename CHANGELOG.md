
# Changelog
## Version 1.0.59 on 23 June 2025
- Rebranded Billwerk+ to Frisbii

## Version 1.0.58 on 12 January 2024
- Added an automatic invoice creation function for auto-capture mode and auto-capture payment.

## Version 1.0.57 on 17 November 2023
- Changed "BLIK One Click" payment to the "auto_capture" payment flow.

## Version 1.0.56 on 14 November 2023
- Changed "Reepay" labels to "Billwerk+"
- Changed "BLIK" payment to "BLIK One Click" payment
- Changed "Przelewy24 (P24)" payment to the "auto_capture" payment flow

## Version 1.0.55 on 30 May 2023
- Handle PHP notice in Google Pay and Apple Pay

## Version 1.0.54 on 17 February 2023
- Changed "AnyDay" to "Anyday"

## Version 1.0.53 on 1 February 2023
- Added the missing payment info templates in the backend.
- Fixed the wrong payment additional info when capture and refund.

## Version 1.0.52 on 12 January 2023
- Changed "iDEAL" payment to the "auto_capture" payment flow.

## Version 1.0.51 on 10 January 2023
- Added new payment method "Bancontact".
- Remove "SEPA Direct Debit" and "Verkkopankki" from payment options of main method.

## Version 1.0.50 on 4 January 2023
- Added new payment methods "AnyDay", "Klarna Direct Bank Transfer" and "Klarna Direct Debit".

## Version 1.0.49 on 18 November 2022
- Fixed multiple currency issue when create do capture and refund.

## Version 1.0.48 on 12 October 2022
- Changed "SEPA Direct Debit" and "Verkkopankki" payments to the "auto_capture" payment flow.

## Version 1.0.47 on 11 July 2022
- Add new payment methods "iDEAL", "BLIK", "Przelewy24 (P24)", "Verkkopankki", "giropay" and "SEPA Direct Debit".

## Version 1.0.46 on 8 November 2021
- Fixed customer handle bug. (ref. issue report #2)

## Version 1.0.45 on 7 June 2021
- Add "Google Pay" payment method.
- Allowed "Apple Pay" payment only Safari browser.

## Version 1.0.44 on 15 February 2021
- Add backend configuration to control order cancellation.

## Version 1.0.43 on 18 January 2021
- implement customer handle solution.
- implement webhook setting button in Magento backend.

## Version 1.0.42 on 14 December 2020
- Change admin label and set default "send_email_after_payment"

## Version 1.0.41 on 2 December 2020
- Block the cancel action if order has authorized payment status
- Add delay to webhooks to avoid immediately call back

## Version 1.0.40 on 22 October 2020
- Force Vipps, Resurs Bank and Apple Pay to be opened in "Window" display type.
- Disable cache for Reepay block

## Version 1.0.39 on 16 October 2020
- Fix 'ordertext' blank issue.

## Version 1.0.38 on 28 September 2020
- add "Klarna Slice It" and "Vipps" payment options.

## Version 1.0.37 on 21 September 2020
- Not delete Reepay session when payment success.

## Version 1.0.36 on 10 September 2020
- Fixed invoice issue for Swish payment.

## Version 1.0.35 on 5 August 2020
- Add "Send order lines" option.
- Prevent capture amount more than authorized amount.

## Version 1.0.34 on 14 July 2020
- Skip order cancelation if already has capture transaction.

## Version 1.0.33 on 13 July 2020
- Add refund function when cancel order (only Swish payment).
- Small fix for PHP notice

## Version 1.0.32 on 3 July 2020
- Small fix for PHP notice

## Version 1.0.31 on 23 June 2020
- Add "Swish Bank", "Resurs" and "Forbrugsforeningen" payment methods.
- Split Klarna payment method to "Klarna Pay Now" and "Klarna Pay Later".

## Version 1.0.30 on 2 June 2020
- separate payment methods for Klarna, ApplePay and Paypal.

## Version 1.0.29 on 27 February 2020
- add "Resurs Bank" payment option.
- Fix checkout session for thank you page.

## Version 1.0.28 on 26 February 2020
- add payment method validation on cancel order observer.
- Fix checkout session for thank you page.

## Version 1.0.27 on 20 February 2020
- Add API error handle when capture and refund from Magento
- Change logic to calculate "Other" line for order lines.
- Fixed integer parse issue

## Version 1.0.26 on 9 January 2020
- Save payment additional data when authorize, settled and refund 
- Fixed PHP Notice: Undefined index

## Version 1.0.25 on 4 November 2019
- Fixed PHP strict notice

## Version 1.0.24 on 1 October 2019
- Update session validation

## Version 1.0.23 on 18 September 2019
- Add new payment options (Apple Pay, Paypal)

## Version 1.0.22 on 9 September 2019
- Force order state to processing when authorized and settled
- Prepare customer email from order data

## Version 1.0.21 on 28 August 2019
- Fix after payment order status issue for custom order statuses

## Version 1.0.20 on 16 August 2019

 - Fixed double email sending issue. (remove action in the accept callback and leave in the authorize webhook)
 - Implement send order email in the settled webhook (for the auto capture function).

## Version 1.0.19 on 11 July 2019

 - Force order status in the "invoice_authorized" webhook.

## Version 1.0.18 on 09 July 2019

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
