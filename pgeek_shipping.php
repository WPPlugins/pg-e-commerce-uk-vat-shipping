<?php
/*
Plugin Name: WP e-Commerce UK VAT Shipping
Plugin URI: http://www.peoplesgeek.com/plugins/wp-ecommerce-uk-vat-shipping/
Description: Shipping Module For WP E-Commerce that is aware of UK VAT requirements and the complexities from HMRC on apportioning shipping costs on products that are VAT exempt such as books vs ebooks etc <br>Must be at least wp-ecommerce v3.8
Version: 3.8.9.5
Author: PeoplesGeek
Author URI: http://www.peoplesgeek.com

Many thanks leewillis77 who wrote [WP E-Commerce Weight & Destination Shipping Modules](http://wordpress.org/extend/plugins/wp-e-commerce-weightregion-shipping/), this helped me determine how to connect with the WP eCommerce internals

	Copyright 2012  PeoplesGeek (Brian Reddick info@peoplesgeek.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
    
    http://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses

*/

class pgeek_shipping {

	var $internal_name;
	var $name;
	var $is_external;
	var $region_list;
	var $country_to_region;
	var $shippable_cart_subtotal;// subtotal of cart used to determine proportion of VAT if applicable
	var $ex_vat_shipping; //Shipping without VAT on VATable items
	var $separate_tax_storage = false; // don't store the extra tax in PnP by default

	function pgeek_shipping () {

		// An internal reference to the method - must be unique!
		$this->internal_name = "pgeek_uk_shipping";
		
		// $this->name is how the method will appear to end users
		$this->name = "Weight from UK shipping";

		// Set to FALSE - doesn't really do anything :)
		$this->is_external = FALSE;
		
		define("PGEEK_VAT_SHIPPING_OPTIONS", "wpec_pg_uk_vat_shipping_options");
		
		//description is used in Admin and also visible to customers as "Shipping from UK ...
		$this->region_list = Array('uk' => 'locally in UK (VAT on shipping included if applicable)',
		                            'eu' => 'to EU (VAT on shipping included if applicable)',
       		                        'rest' => 'Internationally - outside EU');
		$this->country_to_region = Array('uk'=> Array('GB', 'UK'),
										'eu'=> Array('AT', 'BE', 'BG', 'CY', 'CZ' , 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'),
										'rest'=> Array(''));
		$this->ex_vat_shipping = 0.00;
		$this->shippable_cart_subtotal = 0.00;
		
		$options = get_option(PGEEK_VAT_SHIPPING_OPTIONS);
		$this->separate_tax_storage = (isset($options['separate_tax']) && 'pnp' == $options['separate_tax']);
		
		if ( ! $this->separate_tax_storage )
			add_filter('wpsc_calculate_total_tax', array( $this , 'pg_adjust_total_tax' ) );		
		
		return true;
	}
	
	/* You must always supply this */
	function getName() {
		return $this->name;
	}
	
	/* You must always supply this */
	function getInternalName() {
		return $this->internal_name;
	}
	
	
	/**
	 * The form that is displayed for collecting information on the Shipping Panel
	 */
	function getForm() {

		$options = get_option(PGEEK_VAT_SHIPPING_OPTIONS);
		if (isset($options['weight_unit']) && $options['weight_unit'] != "")  {
			$weight_unit = $options['weight_unit'];
		}else{
			$weight_unit = 'gram';
		}
		$separate_tax_storage = (isset($options['separate_tax']))? $options['separate_tax']:'total';

		$output  = '<tr>';
		$output  = '<input type="hidden" name="'.PGEEK_VAT_SHIPPING_OPTIONS.'_check" value="formsubmitted">';
		$output .= '</tr>';
		foreach ($this->region_list as $region_id => $region_desc) {
			$output .= '<tr><td><br/><strong>'.$region_desc.'</strong></td><td><table>';
			$output .= '<tr><td>Weight equal or over:</td><td>Shipping cost:</td></tr>';
			$weights = Array();
			if (isset($options[$region_id]) && count($options[$region_id])) { 
				$weights = array_keys($options[$region_id]);
			}
			foreach ($weights as $weight){
				$output .= '<tr><td><input type="text" name="'.PGEEK_VAT_SHIPPING_OPTIONS.'_'.$region_id.'_weights[]" value="'.htmlentities($weight).'"></td>';
				$output .= '<td><input type="text" name="'.PGEEK_VAT_SHIPPING_OPTIONS.'_'.$region_id.'_costs[]" value="'.htmlentities($options[$region_id][$weight]).'"></td></tr>'; 
			}
			$output .= '<tr><td><input type="text" name="'.PGEEK_VAT_SHIPPING_OPTIONS.'_'.$region_id.'_weights[]" value=""></td>'; //always one blank line for entry
			$output .= '<td><input type="text" name="'.PGEEK_VAT_SHIPPING_OPTIONS.'_'.$region_id.'_costs[]" value=""></td></tr>'; //always one blank line for entry
			$output .= '</table></td></tr>'; 
		}	
		
		$output .= '<tr><td><strong>Weight Unit</strong></td><td colspan="2">';
		$output .= '<select name="'. PGEEK_VAT_SHIPPING_OPTIONS.'_weight_unit">';
		$output .= '<option value="pound" '.   ( ( $weight_unit == 'pound'    ) ? 'selected="selected"' : '' ) . ' >Pounds</option>';
		$output .= '<option value="ounce" '.   ( ( $weight_unit == 'ounce'    ) ? 'selected="selected"' : '' ) . ' >Ounces</option>';
		$output .= '<option value="gram" '.    ( ( $weight_unit == 'gram'     ) ? 'selected="selected"' : '' ) . ' >Grams</option>';
		$output .= '<option value="kilogram" '.( ( $weight_unit == 'kilogram' ) ? 'selected="selected"' : '' ) . ' >Kilograms</option>';
		$output .= '</select></td></tr>';
		$output .= '<tr><td><strong>Shipping VAT</strong></td><td colspan="2">';
		$output .= '<select name="'. PGEEK_VAT_SHIPPING_OPTIONS.'_separate_tax">';
		$output .= '<option value="total" '.   ( ( $separate_tax_storage == 'total'    ) ? 'selected="selected"' : '' ) . ' >shown as part of total tax</option>';
		$output .= '<option value="pnp" '.     ( ( $separate_tax_storage == 'pnp'      ) ? 'selected="selected"' : '' ) . ' >shown in shipping as additional PnP*</option>';
		$output .= '</select></td></tr>';
		if ($separate_tax_storage == 'pnp')
			$output .= '<tr><td colspan="3"><br/><strong>*Warning:</strong> if VAT is stored as PnP then total tax will not automatically include this without other theme support. Use with care.</td></tr>';
		$output .= '<tr><td colspan="3"><br/>Press update to get a new line (all entries will be sorted descending by weight each time you update)</td></tr>';
		
		return $output;
		
	}
	

	/**
	 * 
	 * Save the data returned by the getForm() function and option screen
	 */
	function submit_form() {

		if (!isset($_POST[PGEEK_VAT_SHIPPING_OPTIONS.'_check']) || $_POST[PGEEK_VAT_SHIPPING_OPTIONS.'_check'] == "") {
			return FALSE; 
		}

		foreach ($this->region_list as $region_id => $region_desc) {
			$newweights=(array)$_POST[PGEEK_VAT_SHIPPING_OPTIONS.'_'.$region_id.'_weights'];
			$newcosts=(array)$_POST[PGEEK_VAT_SHIPPING_OPTIONS.'_'.$region_id.'_costs'];
			// Put the weights and rates together with the region for storage
			for ($i = 0; $i < count($newweights); $i++) {
				// Don't set rates if they're blank
				if (isset($newcosts[$i]) && $newcosts[$i] != "") {
					$new_shipping[$region_id][$newweights[$i]] = $newcosts[$i];
				}
			}
			// To make the get_quote routine more efficient - sort the array before saving
			if (count($new_shipping[$region_id])) {
				krsort($new_shipping[$region_id],SORT_NUMERIC);
			}
		}
		$new_shipping['weight_unit'] = $_POST[PGEEK_VAT_SHIPPING_OPTIONS.'_weight_unit'];
		$new_shipping['separate_tax'] = $_POST[PGEEK_VAT_SHIPPING_OPTIONS.'_separate_tax'];
		
		update_option(PGEEK_VAT_SHIPPING_OPTIONS, $new_shipping);
		return true;
	}
	
	
	/**
	 * Calculate the VAT on shipping that would be applied to each individual item. This routine is used internally
	 * to either return the additional tax or to be summed up and added to the total tax.
	 */
	function get_item_shipping_vat(&$cart_item) {
		// Make sure the quote has been called to set the ex_vat_shipping and regions
		// It is more efficient to only calculate this once but sometimes Quote is not called first
      	if ($this->ex_vat_shipping == 0){
      		$dummy=$this->getQuote();
      	}
		
		$product_id = $cart_item->product_id;
		$quantity = $cart_item->quantity;
		$weight = $cart_item->weight;
		$unit_price = $cart_item->unit_price;
		$description = $cart_item->product_name;
		
    	if ( is_numeric($product_id) && (get_option('do_not_use_shipping') != 1) ) {

			$region = $_SESSION['pgeek_wpsc_delivery_country'];	
				
			if (in_array( $region, Array('uk','eu')) ){
				// TODO: for now VAT rate is hard coded - change to pick up from configuration
				$vat_rate = 0.20;
			}else{
				$vat_rate = 0.00;
			}
				
			// Get product information - the location changed in version of wp-ecommerce >3.8, check is already done by now
			$product_list = get_post_meta ( $product_id, '_wpsc_product_metadata', TRUE );

			$no_shipping = $product_list['no_shipping'];
			$local_shipping = $product_list['shipping']['local'];
			$international_shipping = $product_list['shipping']['international'];
			$tax_free_on = (isset($product_list['wpec_taxes_taxable']) ? $product_list['wpec_taxes_taxable'] : '');
					
       		// If the item has shipping enabled
      		if($product_list['no_shipping'] == 0 && $tax_free_on != "on") {
      				
      			// Additional VAT on shipping is the proportion of this items cost to the shippable cart cost
      			$gross_item_cost = (float)$quantity * (float)$unit_price;
      			if ((float)$this->shippable_cart_subtotal > 0){
	      			$vat_proportion = (float)$gross_item_cost / (float)$this->shippable_cart_subtotal ;
					$shipping = round((float)$vat_proportion * (float)$this->ex_vat_shipping * $vat_rate, 2 );
      			} else {
      				$shipping = 0;
      			}

			} else {

	       			//if the item does not have shipping
	       			$shipping = 0;
			}

		} else {

      			//if the item is invalid or store is set not to use shipping
				$shipping = 0;
		}
		
		return $shipping;
		
	}
	

	/**
	 * Calculate the per-item shipping charge (called by WP e-Commerce at multiple points)
	 * This amount will be stored in PnP field and added to the total by WP e-Commerce but it won't be added to the total tax automatically so 
	 * if this is needed the theme / WP e-Commerce will need changes to make this consistent - how this is done depends on the developer / cusomer
	 */
	function get_item_shipping(&$cart_item) {
		
      	$shipping = 0.00;
      	
		// If we're calculating a price based on a product, and that the store has shipping enabled and VAT is stored in PnP
		if ( $this->separate_tax_storage ){
			$shipping = $this->get_item_shipping_vat($cart_item);
		}
		
    	return $shipping;	
	}
	
	/**
	 * Helper function to total up the additional tax on all items in the cart
	 */
	function get_item_shipping_vat_total(){
		global $wpsc_cart;
		$total =0;
	      foreach((array)$wpsc_cart->cart_items as $cart_item) {
	         $total += $this->get_item_shipping_vat($cart_item);
	      }
	      return $total;	
	}

	/**
	 * Return the shipping option base price 
	 * This is for the cart overall (WP e-Commerce will add on additional shipping per item from get_item_shipping() in addition)
	 * This also saves some variables that are needed to work out apportionment later 
	 * (as it turns out this may not have been the most efficient as you can't guarantee the order the functions are called in by WP e-Commerce)
	 */
	function getQuote() {

		global $wpdb, $wpsc_cart;
			
		// Get the delivery info
		if ( isset( $_POST['country'] ) )
			$_SESSION['wpsc_delivery_country'] = $_POST['country'];
		
		if ( isset($_SESSION['wpsc_delivery_country']) ) {
			$country = $_SESSION['wpsc_delivery_country'];
		} else {
			$country = (string) wpsc_get_customer_meta( 'shipping_country' );
			if ( empty( $country ) )
				$country = esc_attr( get_option( 'base_country' ) );
				
			
			$_SESSION['wpsc_delivery_country'] = $country;
		}
		
		$country_isocode = $wpdb->get_var("SELECT `isocode`
							 FROM `".WPSC_TABLE_CURRENCY_LIST."`
							WHERE `isocode` IN('{$country}')
							LIMIT 1");
		
		//Determine which region this country iso code belongs to and default to the rest
		//Also set the description to show on the quotation page
		$region='rest';
		$region_description='Shipping from UK '.$this->region_list['rest'];
		foreach ($this->region_list as $region_id => $region_desc) {
			if (in_array( $country_isocode, $this->country_to_region[$region_id]) ) {
				$region= $region_id;
				$region_description='Shipping from UK '.$this->region_list[$region_id];
			}
		}
		
		// Retrieve the options set by submit_form() above
		$my_shipping_rates = get_option(PGEEK_VAT_SHIPPING_OPTIONS);
		
		if (isset($my_shipping_rates[$region]) && count($my_shipping_rates[$region])) {
			$rates = $my_shipping_rates[$region]; 
		} else {
			// No shipping layers configured for this region
			return Array();
		}
		
		// Get the cart weight in the weight unit that is saved
		$weight_unit = $my_shipping_rates['weight_unit'];
		$weight = wpsc_convert_weight(wpsc_cart_weight_total() ,'pound', $weight_unit) ;
		
		//Save the cost of all items in the cart that are shippable for later use in the get_item_shipping function		
		$this->shippable_cart_subtotal = $wpsc_cart->calculate_subtotal(true);
		
		//Save the region for later use in the get_item_shipping function
		$_SESSION['pgeek_wpsc_delivery_country'] = $region;
		
		// Note the weight layers are sorted before being saved into the options
		// Here we assume that they're in (descending) order
		foreach ($rates as $key => $shipping) {
			if ($weight >= (float)$key) {
				$this->ex_vat_shipping = $shipping;
				return array($region_description => (float)$shipping);
			}
		}
		
		//we have a problem and could not find a matching weight/cost
		
		$this->ex_vat_shipping = 0;
			
		return array();
		
	}
	/**
	 * Filter function attached to 'wpsc_calculate_total_tax' if we are storing the tax as part of the total
	 * The filter is not activated in the initiate if we are not putting tax directly into the total.
	 */
	function pg_adjust_total_tax($wpsc_total_tax = 0){
		
		$wpsc_total_tax += $this->get_item_shipping_vat_total() ;
		return $wpsc_total_tax;
		
	}
	
} // end of class

/**
 * This was a plugin for internal customers only - convert any existing customer shipping rates to the new format and remove the old option entry
 */
$update = get_option('wpec_pgeek_uk_shipping_options');
if ( $update  ){
	$options['uk'] = isset($update['uk'] )? $update['uk'] : array();
	$options['eu'] = isset($update['eu'] )? $update['eu'] : array();
	$options['rest'] = isset($update['rest'] )? $update['rest'] : array();
	$options['weight_unit'] = isset($update['weight_unit'] )? $update['weight_unit'] : 'gram';
	$options['separate_tax'] = isset($update['separate_tax'] )? $update['separate_tax'] : 'pnp';
	update_option('wpec_pg_uk_vat_shipping_options', $options);
	delete_option('wpec_pgeek_uk_shipping_options');
}


/**
 * Use the standard WP e-Commerce hook to add in the shipping module.
 */
function pgeek_shipping_add($wpsc_shipping_modules) {

	global $pgeek_uk_shipping;
	$pgeek_uk_shipping = new pgeek_shipping();

	$wpsc_shipping_modules[$pgeek_uk_shipping->getInternalName()] = $pgeek_uk_shipping;

	return $wpsc_shipping_modules;
}

add_filter('wpsc_shipping_modules', 'pgeek_shipping_add');

?>
