=== WP e Commerce UK VAT Shipping ===
Contributors: peoplesgeek
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=79GGL6EX3V74W
Tags: e-commerce, wp-e-commerce, shipping, UK VAT, VAT
Requires at least: 3.4
Tested up to: 3.5
Stable tag: 3.8.9.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

UK VAT Aware Shipping module for the WP e-commerce system. Calculates proportional shipping on mixed VATable items.

== Description ==

In the UK, VAT is not chargeable on some items such as books but is charged on other items such as CD's. The additional VAT on shipping is dependent on the value of the items in the shopping cart that have shipping and are subject to VAT.

This module will allow you to select different areas of the world for shipping (UK, EU, Rest of world) and a set of weight ranges for each region. It will then correctly calculate the proportion of tax on shipping required if there is a mix of items subject to VAT and other items not subject to VAT. You also have the choice of storing the extra tax in the total or separate in the PnP field (extra coding is required on your part to complete the PnP option)

* Select different base shipping amounts for different areas - UK, EU, Other
* Select different base shipping based on weight of the cart being shipped
* Choose if you want to show the additional VAT in the total tax amount or as a postage and packing (PnP) amount

   If you use PnP then you will probably need to make other changes to your theme and code to display the amounts correctly on the standard WP e-Commerce templates. This is only for more advanced use where you may want to show the tax on shipping separately.

It may also be possible to avoid the UK VAT on shipping issue if you included shipping in your product price but you should discuss that with your accountant.

**IMPORTANT:** Tested using WordPress 3.5 and the same version of WP e-Commerce as this plugins version - if using another combination then please do your own test first

_This is not Tax advice! This plugin is not a replacement for understanding and testing you site VAT. As with any plugin associated with financial transactions please test the results match what you expect and ensure you comply with your VAT responsibilities_

== Installation ==

1. Upload all the files into your wp-plugins directory
1. Activate the plugin at the plugin administration page
1. Ensure that you have installed and configured the [WP e-Commerce Plugin](http://wordpress.org/extend/plugins/wp-e-commerce/)
1. Got to the WP-e-Commerce 'store' menu under Settings. 
1. Under the Shipping tab will be the option 'Weight from UK shipping'
1. Choose the weight unit and then enter weight and cost for each of the three regions
1. Choose the option for where to store the extra tax (in total or in PnP - *PnP option requires other changes on your part*)

== Frequently Asked Questions ==

= Why do you offer two places to store VAT =
Originally this plugin was developed for a customer with specific requirement to show the extra VAT on shipping separately but this requires a number of other changes in the WP e-commerce templates. I decided to make this an option so that more people could benefit from the correct tax calculation whether or not they need this additional capability. If you need to show separate amounts then you will need to make a number of code changes where VAT is displayed (PayPal, Account summary, payment summary etc). This is not for the faint hearted.

= VAT is not showing separately =
See the above FAQ. The default is to show the extra tax directly in the total so that you do not need to make other code changes to the plugin. If you need to show tax separately then you will need to make supporting changes to your account summary, payment gateways etc which is outside the scope of this plugin.

= How is VAT calculated for mixed shopping baskets =
Refer to the screenshot example of the checkout screen<br />
In the screenshot example the total of the basket is 74.99<br />
VAT is due on the CD but not the book so the proportion of VAT on shipping is 55.00/74.99<br />
Shipping in this case is to the UK and 8.95 for the combined weight of these items<br />
So total shipping cost including VAT is 8.95 + 8.95 * .2 * (55/74.99) = 10.26

= Why is VAT in PayPal wrong =
If you have used the option to store VAT in PnP then the plugin adds the additional VAT as an additional shipping amount. In this case you will need to allow for this when sending information to PayPal and make code adjustments as appropriate (outside the scope of my support)<br />
If possible a future version of the plugin will filter the calculated tax total to remove this problem.


== Screenshots ==

1. Entering the shipping costs by weight for different regions of the world
2. The checkout screen with shipping inclusive of VAT shown


== Changelog ==

= 3.8.9.5 =
* Tested with latest version of WP e-Commerce and WordPress 3.5 - no changes to code from 3.8.9.4

= 3.8.9.4 =
* Changed version numbers to match the released WP e-Commerce version numbers to make it easier to find the right version to use.
* Tested up to WP e-Commerce version 3.8.9.4 with WordPress 3.5
* Fixed bug where warning may be thrown in back end on activation if wpsc_delivery_country not set
* Converts options from previous customer plugins to new format.
* Updated to match new storage location of shipping country in 3.8.9.4


= 1.1.0 =
* Initial Release

== Upgrade Notice ==

= 1.1.0 =
No notice on initial release
