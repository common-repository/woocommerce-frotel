<?php
/**
Plugin Name: WooCommerce Frotel
Plugin URI: http://frotel.ir/
Description: This plugin integrates <strong>Frotel</strong> service with WooCommerce.
Version: 1.1
Author: Domanjiri
Text Domain: frotel
Domain Path: /lang/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
**/

function activate_WC_FROTEL_plugin()
{
    wp_schedule_event(time(), 'daily', 'update_frotel_orders_state');
} 
//register_activation_hook(__FILE__, 'activate_WC_FROTEL_plugin');


function deactivate_WC_FROTEL_plugin()
{
    wp_clear_scheduled_hook('update_frotel_orders_state');
}
//register_deactivation_hook(__FILE__, 'deactivate_WC_FROTEL_plugin');


// Check if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
	function frotel_shipping_method_init() {
            if(!class_exists('nusoap_client')) {
                include_once(plugin_dir_path(__FILE__) . 'lib/nusoap/nusoap.php');
            }
		    
            // 
            date_default_timezone_set('Asia/Tehran');
            ini_set('default_socket_timeout', 160);
            
            // Define Pishtaz method
		    if ( ! class_exists( 'WC_Frotel_Pishtaz_Method' ) ) {
			     class WC_Frotel_Pishtaz_Method extends WC_Shipping_Method 
                 {
                        var $url            = "";
                        var $wsdl_url       = "http://www.froservice.ir/F-W-S-L/F_Gateway.php?wsdl";
                        var $username       = "";
                        var $password       = "";
                        var $debug          = 0;
                        var $w_unit         = "";
                        var $debug_file     = "";
                        var $client         = null;
				
				        public function __construct() 
                        {
					       $this->id                 = 'frotel_pishtaz'; 
					       $this->method_title       = __( 'پست پیشتاز' ); 
					       $this->method_description = __( 'ارسال توسط پست پیشتاز ' );
 
					       $this->init();
                           $this->account_data();
				        }
 
				        function init() 
                        {
					       $this->init_form_fields(); 
					       $this->init_settings(); 
                    
                           $this->enabled		= $this->get_option( 'enabled' );
		                   $this->title 		= $this->get_option( 'title' );
		                   $this->min_amount 	= $this->get_option( 'min_amount', 0 );
                           
                           $this->w_unit 	    = strtolower( get_option('woocommerce_weight_unit') );
                           
					       add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                    
				        }
                        
                        function account_data() 
                        {
                            $this->username     = $this->get_option( 'username', '' );
                            $this->password     = $this->get_option( 'password', '' );
                        }
                
                        function init_form_fields() 
                        {
   	                        global $woocommerce;

		                    if ( $this->min_amount )
		                     	$default_requires = 'min_amount';


                         	$this->form_fields =  array(
	                     		'enabled' => array(
	                     						'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
						                     	'type' 			=> 'checkbox',
			                     				'label' 		=> __( 'فعال کردن پست پیشتاز', 'woocommerce' ),
			                     				'default' 		=> 'yes'
	                     					),
	                     		'title' => array(
                     	                     						'title' 		=> __( 'Method Title', 'woocommerce' ),
					                     		'type' 			=> 'text',
                     							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					                     		'default'		=> __( 'پست پیشتاز', 'woocommerce' ),
		                     					'desc_tip'      => true,
	                     					),
	                     		'min_amount' => array(
                     							'title' 		=> __( 'Minimum Order Amount', 'woocommerce' ),
                     							'type' 			=> 'number',
		                     					'custom_attributes' => array(
	                     							'step'	=> 'any',
	                     							'min'	=> '0'
	                     						),
			                     				'description' 	=> __( 'کمترین میزان خرید برای فعال شدن این روش ارسال.', 'woocommerce' ),
				                     			'default' 		=> '0',
				                     			'desc_tip'      => true,
			                     				'placeholder'	=> '0.00'
			                     			),
                                 'pishtaz_default' => array(
                     	                     	'title' 		=> __( 'هزینه‌ی پیش‌فرض', 'woocommerce' ),
					                     		'type' 			=> 'text',
                     							//'description' 	=> __( 'هنگامی که به دلایلی امکان استعلام هزینه‌ی ارسال از سرویس پارس‌پیک ممکن نباشد، این مبلغ نمایش داده‌خواهد شد.', 'woocommerce' ),
					                     		'default'		=> 60000,
		                     					'desc_tip'      => true,
	                     					),
                                 'username' => array(
	                     						'title' 		=> __( 'نام‌کاربری', 'woocommerce' ),
	                     						'type' 			=> 'text',
	                     						'description' 	=> __( '', 'woocommerce' ),
	                     						'default'		=> __( '', 'woocommerce' ),
	                     						'desc_tip'      => true,
	                     					),
                                 'password' => array(
	                     						'title' 		=> __( 'رمز استفاده از وب سرویس', 'woocommerce' ),
	                     						'type' 			=> 'password',
	                     						'description' 	=> __( '', 'woocommerce' ),
	                     						'default'		=> __( '', 'woocommerce' ),
	                     						'desc_tip'      => true,
			                     			)
   		                     	);

                         }
    
    
                        public function admin_options() 
                        {
                            ?>
    	                     <h3><?php _e( 'پست پیشتاز' ); ?></h3>
                         	<table class="form-table">
                         	<?php
                         		// Generate the HTML For the settings form.
                         		$this->generate_settings_html();
                         	?>
            <table class="form-table">
       <body>
        <tr valign="top">
			<th scope="row" class="titledesc">
				<label for="woocommerce_frotel_pishtaz_id_ostan">استان مبدا</label>
							</th>
			<td class="forminp">
                <select name="woocommerce_frotel_pishtaz_id_ostan" class="select" onChange="ldMenu(this.selectedIndex);" dir="rtl" id="id_ostan">
          <option value="0">لطفا استان خود را انتخاب کنید</option>
          <option  value="41">آذربايجان شرقي</option>
          <option  value="44">آذربايجان غربي</option>
          <option  value="45">اردبيل</option>
          <option  value="31">اصفهان</option>
          <option  value="84">ايلام</option>
          
          <option  value="77">بوشهر</option>
          <option  value="26">البرز</option>
          <option  value="21">تهران</option>
          <option  value="38">چهارمحال بختياري</option>
          <option  value="56">خراسان جنوبي</option>
          <option  value="51">خراسان رضوي</option>
          <option  value="58">خراسان شمالي</option>
          
          <option  value="61">خوزستان</option>
          <option  value="24">زنجان</option>
          <option  value="23">سمنان</option>
          <option  value="54">سيستان و بلوچستان</option>
          <option  value="71">فارس</option>
          <option  value="28">قزوين</option>
          
          <option  value="25">قم</option>
          <option  value="87">كردستان</option>
          <option  value="34">كرمان</option>
          <option  value="83">كرمانشاه</option>
          <option  value="74">كهكيلويه و بويراحمد</option>
          <option  value="17">گلستان</option>
          
          <option  value="13">گيلان</option>
          <option  value="66">لرستان</option>
          <option  value="15">مازندران</option>
          <option  value="86">مركزي</option>
          <option  value="76">هرمزگان</option>
          <option  value="81">همدان</option>
          <option  value="35">يزد</option>
       </select>
       
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
				<label for="woocommerce_frotel_pishtaz_id_shahr">شهر مبدا</label>
							</th>
			<td class="forminp">
                <select name="woocommerce_frotel_pishtaz_id_shahr" id="id_shahr" size="1" dir="rtl" class="select">
					<option selected value="">لطفا استان خود را انتخاب کنید</option>
			    </select>
                
            </td>
        </tr>
       
       </body>
       </table>
       <script type="text/javascript">
       jQuery(document).ready(function($) {
    function select_list_sync_to_input(iid, iinput) {
        $("select#"+iid).change(function(){
            var val_now = $("select#"+iid+" option:selected").val();
            if(val_now != 0){
                $("input#"+iinput).val($("select#"+iid+" option:selected").val()+'-'+$("select#"+iid+" option:selected").text());  
            }else{
                $("input#"+iinput).val('');
            }
        
        });
    }
    

    
    function set_initial_val(iid, ival) {
        jQuery("select#"+iid).val(ival).trigger('onchange');
    }
    
    
    
    
    <?php 
    
    $my_state = get_option('woocommerce_frotel_pishtaz_mabda_state');
    //$my_state = explode('-', $my_state);
    if(isset($my_state) && intval($my_state) > 0 ){
        ?>
        set_initial_val('id_ostan', <?php echo $my_state; ?>);
        <?php
    }
    
    $my_city = get_option('woocommerce_frotel_pishtaz_mabda_city');
    //$my_city = explode('-', $my_city);
    if(isset($my_city) ){
        ?>
        set_initial_val('id_shahr', "<?php echo $my_city;?>");
        <?php
    }
    
    ?>

});
       </script>
      
	                     	</table>
                         	<?php
                       }
                       
                       public function process_admin_options()
                       {
                        $state  = sanitize_text_field(stripslashes( $_POST['woocommerce_frotel_pishtaz_id_ostan']));
                        $city   = sanitize_text_field(stripslashes( $_POST['woocommerce_frotel_pishtaz_id_shahr']));
                       
                        update_option('woocommerce_frotel_pishtaz_mabda_state', $state);
                        update_option('woocommerce_frotel_pishtaz_mabda_city', $city);
                        
                        parent::process_admin_options();
                        return;
                       }
    
                      function is_available( $package ) 
                      {
    	                   global $woocommerce;

                           if ( $this->enabled == "no" ) return false;
       
                           if ( ! in_array( get_woocommerce_currency(),  array( 'IRR', 'IRT' )  ) ) return false;
        
                           if( $this->w_unit != 'g' && $this->w_unit != 'kg' )
                               return false;
        
                           if ( $this->username =="" || $this->password=="")
                               return false;
            
		                   // Enabled logic
	                   	   $has_met_min_amount = false;

	                   	   if ( isset( $woocommerce->cart->cart_contents_total ) ) {
	                   	       
			                     if ( $woocommerce->cart->prices_include_tax )
			                         	$total = $woocommerce->cart->cart_contents_total + array_sum( $woocommerce->cart->taxes );
		                      	else
				                        $total = $woocommerce->cart->cart_contents_total;

			                    if ( $total >= $this->min_amount )
				                        $has_met_min_amount = true;
		                   }


		                   if ( $has_met_min_amount ) $is_available = true;
			

		                   return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available );
                      }

		              public function calculate_shipping( $package ) 
                      {
    	                   global $woocommerce;
		                   $customer = $woocommerce->customer;

                           if( empty($package['destination']['city'])) {
                               $rate = array(
			               		'id' 		=> $this->id,
			               		'label' 	=> $this->title,
			               		'cost' 		=> 0
			               	   );
                               $this->add_rate( $rate );
                           }
                          
			               $this->shipping_total = 0;
		              	   $weight = 0;
                           $unit = ($this->w_unit == 'g') ? 1 : 1000;
            
			               $data = array();
			               if (sizeof($woocommerce->cart->get_cart()) > 0 && ($customer->get_shipping_city())) {

				              foreach ($woocommerce->cart->get_cart() as $item_id => $values) {

					              $_product = $values['data'];

					              if ($_product->exists() && $values['quantity'] > 0) {

						              if (!$_product->is_virtual()) {

							              $weight += $_product->get_weight() * $unit * $values['quantity'];
					              	  }
					             }
				              } //end foreach
                              
				              $data['weight']         = $weight;
                              $data['service_type']   = 1;  // pishtaz
				              
                              if ($weight) {
					              $this->get_shipping_response($data, $package);
				              }
			              }
                         
                      }
        
                      function get_shipping_response($data = false, $package) 
                      {
    	                   global $woocommerce;

                           if($this->debug){
                               $this->debug_file = new WC_Frotel_Debug();
                           }
            
		               	$rates             = array();
		               	$customer          = $woocommerce->customer;
		               	$update_rates      = false;
		               	$debug_response    = array();

		               	$cart_items        = $woocommerce->cart->get_cart();
		               	foreach ($cart_items as $id => $cart_item) {
		               		$cart_temp[] = $id . $cart_item['quantity'];
		               	}
		               	$cart_hash         = hash('MD5', serialize($cart_temp));
            
                        $service           = $this->frotel_service();
                        $total_price       = (get_woocommerce_currency() == "IRT") ? $woocommerce->cart->subtotal * 10 + $service : $woocommerce->cart->subtotal + $service;
            
                        $customer_state    = $package['destination']['state'];
                        $customer_state    = explode('-', $customer_state);
                        $customer_state    = intval($customer_state[0]);
                        if( $customer_state && $customer_state >0){
                            // nothing!
                        }else{
                             if($this->debug){
                                ob_start();
                                var_dump($customer_state);
                                $text = ob_get_contents();
                                ob_end_clean();
                    
                                $this->debug_file->write('@get_shipping_response::state is not valid:'.$text);
                             }
                    
                            return false;
                        }
            
                        $customer_city      = $package['destination']['city'];
                        /*$customer_city      = explode('-', $customer_city);
                        $customer_city      = intval($customer_city[0]);
                        if( $customer_city && $customer_city >0){*/
                            // again nothing!
                        if(empty($customer_city)){
                            if($this->debug){
                                $this->debug_file->write('@get_shipping_response::city is not valid:'.$customer_city);
                            }
                    
                            return false;
                        }
            
                        $shipping_data = array(
			                             'TOstan'              => (string)$customer_state,
			                             'TShahr'              => (string)$customer_city,
			                             'totalWeight'           => (string)$data['weight'],
                                         'totalPrice'            => (string)$total_price,
                                         'send_type'              => (string)$data['service_type']
                                        );

                        $cache_data         = get_transient(get_class($this));

			            if ($cache_data) 
                            if ($cache_data['cart_hash'] == $cart_hash && $cache_data['shipping_data']['SMaghsad'] == $shipping_data['SMaghsad'] && $cache_data['shipping_data']['TotalWeight'] == $shipping_data['TotalWeight'] && $cache_data['shipping_data']['TotalPrice'] == $shipping_data['TotalPrice'] && $cache_data['shipping_data']['SendType'] == $shipping_data['SendType'] )  
					            $result = $cache_data['rates'];
				            else
					            $update_rates = true;

			            else
				            $update_rates = true;
			            


			             if ($update_rates) {
                            $data   = $this->frotel_prepare($shipping_data);
				            $result = $this->frotel_shipping($data, (int)$total_price); // intval
                
                            if ($this->debug) {
                                ob_start();
                                var_dump($result);
                                $text = ob_get_contents();
                                ob_end_clean();
					           $this->debug_file->write('@get_shipping_response::everything is Ok:'.$text);
				            }
                

				            $cache_data['shipping_data']        = $shipping_data;
				            $cache_data['cart_hash']            = $cart_hash;
				            $cache_data['rates']                = $result;
			             }
                         
                         
			             set_transient(get_class($this), $cache_data, 60*60*5);
                         
                         
                         $tax = intval($total_price + $result) * 0.08;
			             $rate       = (get_woocommerce_currency() == "IRT") ? (int)(intval(($result /*+ $tax*/) / 10)/100)*100+100  : (int)(((int)$result /*+ $tax*/)/1000)*1000+1000;
			
                         $my_rate = array(
					               'id' 	=> $this->id,
					               'label' => $this->title,
					               'cost' 	=> $rate,
				         );
                         
			             $this->add_rate($my_rate);
                         
                      }
        
                      function frotel_prepare($data = false) 
                      {
			              $data['SUserName']    = $this->username;
                          $data['SPassWord']    = $this->password;
                          $data['GOstan']       = (string)get_option('woocommerce_frotel_pishtaz_mabda_state');
                          $data['GShahr']       = (string)get_option('woocommerce_frotel_pishtaz_mabda_city');
                          $data['Buytype']      = '1';

			              return $data;
		              }

		              function frotel_shipping($data = false, $total_price=0, $cache = false) 
                      {
		                  global $woocommerce;
            
                          if ($this->debug) {
                              $this->debug_file->write('@frotel_shipping::here is top of function');
                          }
			
                          $this->client                      = new nusoap_client( $this->wsdl_url, true );
                          $this->client->soap_defencoding    = 'UTF-8';
                          $this->client->decode_utf8         = true;
                          
                          $response                          = $this->call("FCalcPPrice", $data);
            
                          if(is_array($response) && $response['error']){
                              if ($this->debug) {
                                    $this->debug_file->write('@frotel_service::'.$response['message']);
							        $woocommerce->clear_messages();
							        $woocommerce->add_message('<p>@frotel_shipping Frotel Error:</p> <p>'.$response['message'].'</p>');
				              }
                              
                              if ($data['service_type'] == 1){ //pishtaz
                                return  $this->get_option( 'pishtaz_default', 60000 );
                              } elseif($data['service_type'] == 0){
                                return  $this->get_option( 'sefareshi_default', 40000 );
                              }
                              
                          }
                          
                          if ($this->debug) {
                              ob_start();
                              var_dump($data);
                              $text = ob_get_contents();
                              ob_end_clean();
                              $this->debug_file->write('@frotel_shipping::Everything is Ok:'.$text);
                          }
                          
                          if(intval(urldecode($response)) <= 0) {
                            if ($this->debug) {
                              //ob_start();
                              var_dump($response);
                              //$text = ob_get_contents();
                              //ob_end_clean();
                              //$this->debug_file->write('@frotel_shipping::Everything is Ok:'.$text);
                            }
                          }

		              	  return intval(urldecode($response));
                      }
        
                    function frotel_service() 
                    {
                         global $woocommerce;
            
                         $cache_data = get_transient('frotel_service_price');
                         if ($cache_data) {
			             	if (time() - (int)$cache_data['date'] < 86400){
				                 if ($this->debug) {
                                     $this->debug_file->write('@frotel_service::Everything is Ok --> return from cache');
                                 }
                                 return (int)$cache_data['price'];
			             	}
					
			             }
         
                         $this->client                      = new nusoap_client( $this->wsdl_url, true );
                         $this->client->soap_defencoding    = 'UTF-8';
                         $this->client->decode_utf8         = true;
            
                         $response                          = $this->call("FKhadamat", array());
            
                         if(is_array($response) && $response['error']){
                             if ($this->debug) {
                                        $this->debug_file->write('@safir_service::'.$response['message']);
		             					$woocommerce->clear_messages();
		             					$woocommerce->add_message('<p>Safir Error:</p> <p>'.$response['message'].'</p>');
			             	}
                
                            return 7000; // estimated
                         }
            
                         $service = intval(urldecode($response));
            
                         $cache_data['date']        = time();
                         $cache_data['price']       = $service;
            
                         set_transient('frotel_service_price', $cache_data, 60*60*24);
            
                         if ($this->debug) {
                             $this->debug_file->write('@frotel_service::Everything is Ok');
                         }

			             return $service;
                         
		             }
        
                     public function call($method, $params)
	                 {
                         $result = $this->client->call($method, $params);

		             	if($this->client->fault || ((bool)$this->client->getError()))
		             	{
		             		return array('error' => true, 'fault' => true, 'message' => $this->client->getError());
		             	}
                        
                         return $result;
                     }
        
                     public function handleError($error,$status)
                     {
                         if($status =='sendprice')
                         switch ($error)
                         {
                             case 101:
                                 return 'Username or password is wrong';
                                 break;

                             case 601:
                                 return 'State and City are not match';
                                 break;

                             case 202:
                                 return 'weight or amount is invalid';
                                 break;

                              default:
                                 return $error;
                                 break;

                         }
                         if($status =='register')
                         switch ($error)
                         {
                             case 'Access Denied':
                                 return 'Username or password is wrong';
                                 break;
                 
                             case 202:
                                 return 'weight or amount is invalid or product name is empty';
                                 break;

                             case 201:
                                 return 'product array not set';
                                 break;
           
                              default:
                                 return $error;
                                 break;

                         }
    
                     }
            } // end class
        }
        
        if ( ! class_exists( 'WC_Frotel_Sefareshi_Method' ) ) {
			class WC_Frotel_Sefareshi_Method extends WC_Frotel_Pishtaz_Method {
				
                var $username = "";
                var $password = "";
                var $w_unit   = "";
                
				public function __construct() 
                {
				    
					$this->id                 = 'frotel_sefareshi'; 
					$this->method_title       = __( 'پست سفارشی' ); 
					$this->method_description = __( 'ارسال توسط پست سفارشی ' );
 
					$this->init();
                    $this->account_data();
				}
 
				function init() 
                {
					
					       $this->init_form_fields(); 
					       $this->init_settings(); 
                    
                           $this->enabled		= $this->get_option( 'enabled' );
		                   $this->title 		= $this->get_option( 'title' );
		                   $this->min_amount 	= $this->get_option( 'min_amount', 0 );
                           
                           $this->w_unit 	    = strtolower( get_option('woocommerce_weight_unit') );
					      
					       add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                    
                }
                
                function account_data() {
                    $ins = new WC_Frotel_Pishtaz_Method();

                    $this->username     = $ins->get_option( 'username', '' );
                    $this->password     = $ins->get_option( 'password', '' );
                    
                }
                
                function init_form_fields() 
                {
    	            global $woocommerce;

		              if ( $this->min_amount )
		          	$default_requires = 'min_amount';


    	           $this->form_fields = array(
			                    'enabled' => array(
				                     			'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
				                     			'type' 			=> 'checkbox',
				                     			'label' 		=> __( 'فعال کردن پست سفارشی', 'woocommerce' ),
				                     			'default' 		=> 'yes'
			                     			),
		                     	'title' => array(
                     				                     			'title' 		=> __( 'Method Title', 'woocommerce' ),
					                     		'type' 			=> 'text',
                     							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                     							'default'		=> __( 'پست سفارشی', 'woocommerce' ),
                     							'desc_tip'      => true,
                     						),
                     			'min_amount' => array(
	                     						'title' 		=> __( 'Minimum Order Amount', 'woocommerce' ),
	                     						'type' 			=> 'number',
	                     						'custom_attributes' => array(
		                     						'step'	=> 'any',
	                     							'min'	=> '0'
		                     					),
		                     					'description' 	=> __( 'کمترین میزان خرید برای فعال شدن این روش ارسال.', 'woocommerce' ),
			                     				'default' 		=> '0',
			                     				'desc_tip'      => true,
			                     				'placeholder'	=> '0.00'
					                     	),
                                'sefareshi_default' => array(
                     	                     	'title' 		=> __( 'هزینه‌ی پیش‌فرض', 'woocommerce' ),
					                     		'type' 			=> 'text',
                     							'description' 	=> __( 'هنگامی که به دلایلی امکان استعلام هزینه‌ی ارسال از سرویس پارس‌پیک ممکن نباشد، این مبلغ نمایش داده‌خواهد شد.', 'woocommerce' ),
					                     		'default'		=> 40000,
		                     					'desc_tip'      => true,
	                     					),
		                     	);

                }
                
                public function admin_options() 
                {
    	           ?>
    	           <h3><?php _e( 'پست سفارشی' ); ?></h3>
    	           <table class="form-table">
    	           <?php
    		          // Generate the HTML For the settings form.
    		          $this->generate_settings_html();
    	           ?>
		          </table>
                <?php
                }
 
                public function calculate_shipping( $package ) 
                {
                           global $woocommerce;
		                   $customer = $woocommerce->customer;

                           if( empty($package['destination']['city'])) {
                               $rate = array(
			               		'id' 		=> $this->id,
			               		'label' 	=> $this->title,
			               		'cost' 		=> 0
			               	   );
                               $this->add_rate( $rate );
                           }
                          
			               $this->shipping_total = 0;
		              	   $weight = 0;
                           $unit = ($this->w_unit == 'g') ? 1 : 1000;
            
			               $data = array();
			               if (sizeof($woocommerce->cart->get_cart()) > 0 && ($customer->get_shipping_city())) {

				              foreach ($woocommerce->cart->get_cart() as $item_id => $values) {

					              $_product = $values['data'];

					              if ($_product->exists() && $values['quantity'] > 0) {

						              if (!$_product->is_virtual()) {

							              $weight += $_product->get_weight() * $unit * $values['quantity'];
					              	  }
					             }
				              } //end foreach
                              
				              $data['weight']         = $weight;
                              $data['service_type']   = 2;  // sefareshi
				              if ($weight) {
					              $this->get_shipping_response($data, $package);
				              }
			              }
                         
                      }
			     } // end class
		}
	} // end function
	add_action( 'woocommerce_shipping_init', 'frotel_shipping_method_init' );
 
	function add_frotel_shipping_method( $methods ) {
		$methods[] = 'WC_Frotel_Pishtaz_Method';
        $methods[] = 'WC_Frotel_Sefareshi_Method';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_frotel_shipping_method' );


class WC_Frotel_Debug {
    var $handle = null;
    public function __construct() 
    {

    }
    
    private function open() 
    {
		if ( isset( $this->handle ) )
			return true;

		if ( $this->handle = @fopen( untrailingslashit( plugin_dir_path( __FILE__ ) ).'/log/log.txt', 'a' ) )
			return true;

		return false;
	}
    
    public function write($text) 
    {
        return ;
        if ( $this->open() && is_resource( $this->handle) ) {
			$time = date_i18n( 'm-d-Y @ H:i:s -' ); //Grab Time
			@fwrite( $this->handle, $time . " " . $text . "\n" );
		}
		@fclose($this->handle);
    }
    
    public function sep() 
    {
        $this->write('------------------------------------'."\n");
    }
}     

class WC_Frotel {
    var $frotel_carrier;
    var $debug_file = "";
    var $email_handle;
    private $client = null;
    
     public function __construct() 
     {
     
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_order'), 10, 2);
        
        add_action( 'woocommerce_before_checkout_form', array( $this, 'calc_shipping_after_login'));
        add_action( 'woocommerce_cart_collaterals', array( $this, 'remove_shipping_calculator'));
        add_action( 'woocommerce_calculated_shipping', array( $this, 'set_state_and_city_in_cart_page'));
        add_action( 'woocommerce_cart_collaterals', array( $this, 'add_new_calculator'), 20);
        add_action( 'woocommerce_before_cart', array( $this, 'remove_proceed_btn'));
        add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'add_proceed_btn'));
        
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'get_available_payment_gateways'), 10, 1);
        add_filter( 'woocommerce_locate_template', array( $this, 'new_template'), 50, 3); 
        add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'remove_free_text'), 10, 2);
        add_filter( 'woocommerce_default_address_fields', array( $this, 'remove_country_field'), 10, 1);
        add_action( 'woocommerce_admin_css', array( $this, 'add_css_file'));
        add_action( 'admin_enqueue_scripts', array( $this, 'overriade_js_file'), 11);
        
        add_action( 'update_frotel_orders_state', array( $this, 'update_frotel_orders_state'));
        
        add_action( 'woocommerce_before_checkout_form', array( $this, 'show_mobile_message'));
        
        add_filter( 'woocommerce_currencies', array( $this, 'check_currency'), 20 );
        add_filter( 'woocommerce_currency_symbol', array( $this, 'check_currency_symbol'), 20, 2);
        
        add_action( 'woocommerce_after_checkout_validation', array( $this, 'post_code_validation'));
        
        add_action( 'woocommerce_thankyou', array( $this, 'show_invoice'), 5 );

        add_filter('woocommerce_states', array( $this, 'woocommerce_states'));
        //if ( is_checkout() )
			wp_enqueue_script( 'frotel-list', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/js/city3.js', array(), 1.0 );

        if(!class_exists('WC_Frotel_Pishtaz_Method') && function_exists('frotel_shipping_method_init') && class_exists('WC_Shipping_Method'))
            frotel_shipping_method_init();
        
    }
    
    public function get_available_payment_gateways( $_available_gateways)
    {
        global $woocommerce;
        
        $shipping_method = $woocommerce->session->chosen_shipping_method;
        if(in_array( $shipping_method, array('frotel_pishtaz' ,'frotel_sefareshi' ))){   
            foreach ( $_available_gateways as $gateway ) :

			     if ($gateway->id == 'cod') $new_available_gateways[$gateway->id] = $gateway;

		    endforeach;
        
        return $new_available_gateways;
        }
        
        return $_available_gateways;
    }
    
    public function new_template( $template, $template_name, $template_path)
    {
        global $woocommerce;
        
        $shipping_method = $woocommerce->session->chosen_shipping_method;
        
        if( $template_name =='checkout/form-billing.php' OR $template_name =='checkout/form-shipping.php')
            return untrailingslashit( plugin_dir_path( __FILE__ ) ). '/'. $template_name;
        
        return $template;
    }
    
    public function save_order($id, $posted)
    {
        global $woocommerce;

        $this->email_handle =  $woocommerce->mailer();
      
        $order = new WC_Order($id);
        if(!is_object($order))
            return;
              
        $is_frotel = false; 
        if ( $order->shipping_method ) {
            if( in_array($order->shipping_method, array('frotel_pishtaz' ,'frotel_sefareshi' )) ) {
                $is_frotel = true;
                $shipping_methods = $order->shipping_method;
            }
            
		} else {
            $shipping_s = $order->get_shipping_methods();

			foreach ( $shipping_s as $shipping ) {
			    if( in_array($shipping['method_id'], array('frotel_pishtaz' ,'frotel_sefareshi' )) ) {
                    $is_frotel = true;
                    $shipping_methods = $shipping['method_id'];
                    break;
                }
			}
        }
        if( !$is_frotel || $order->payment_method != 'cod' )
            return;
           
        $this->frotel_carrier      = new WC_Frotel_Pishtaz_Method();
        $service_type             = ($shipping_methods == 'frotel_pishtaz') ? 1 : 2;
        if($this->frotel_carrier->debug){
           $this->debug_file = new WC_Frotel_Debug();
           $this->debug_file->sep();
         }
        
        $unit = ($this->frotel_carrier->w_unit == 'g') ? 1 : 1000;
        
        $orders = '';
        foreach ( $order->get_items() as $item ) {

				if ($item['product_id']>0) {
				    $_product = $order->get_product_from_item( $item );
				    //name
                    $productName = str_ireplace('^', '', $_product->get_title());
                    $productName = str_ireplace(';', '', $productName);
                    //price
                    $price  = $order->get_item_total( $item); 
                    $price  = (get_woocommerce_currency() == "IRT") ? (int)$price*10: (int)$price;
                    //id
                    $iid = $item['product_id'];
                    //w
                    $ww = intval($_product->weight * $unit);
				    
                    $orders .= $iid .'^'. $productName .'^'. $price .'^'. $ww .'^'. (int)$item['qty'] .'^0^0';
                    
                    $orders .= ';';
				}

			}
            
            $customer_st = $order->shipping_state;
            $customer_st = explode('-', $customer_st);
            $customer_st = intval($customer_st[0]);
            if( $customer_st && $customer_st >0){
                
            }else{
                if($this->frotel_carrier->debug){
                    $this->debug_file->write('@save_order::state is not valid');
                    die('state is not valid');
                }
                    
                return false;
            }
            
            $customer_city = $order->shipping_city;
            /*$customer_city = explode('-', $customer_city);
            $customer_city = intval($customer_city[0]);*/
            if( !empty($customer_city)){
                
            }else{
                if($this->frotel_carrier->debug){
                    $this->debug_file->write('@save_order::city is not valid');
                    die('city is not valid');
                }
                    
                return false;
            }
        
        $params = array(
         'name'             =>  $order->billing_first_name,
         'family'           =>  $order->billing_last_name,
         'gender'           =>  '1',
         'email'            =>  $order->billing_email,
         'id_ostan'         =>  (string)$customer_st,
         'id_shahr'         =>  $customer_city,
         'adress'           =>  $order->billing_address_1 . ' - '. $order->billing_address_2,
         'zipcode'          =>  $order->billing_postcode,
         'telphone'         =>  $order->billing_phone,
         'cellphone'        =>  $order->billing_phone,
         'message'          =>  $order->customer_note,
         'send_type'        =>  (string)$service_type,
         'requestList'      =>  $orders,
         'Buytype'          =>  'posti',
         'fish'             =>  '',
         'bank'             =>  '',
         'bazaryab'         =>  'fs',
         'BWsite'           =>  '',
         'SUserName'        =>  $this->frotel_carrier->username,
         'SPassWord'        =>  $this->frotel_carrier->password
         ); 
         
         list($res, $response) = $this->add_order( $params, $order );
        
         if ($res === false) {
                    if ($this->frotel_carrier->debug) {
                            ob_start();
                            var_dump($params);
                            $text = ob_get_contents();
                            ob_end_clean();
                            $this->debug_file->write('@save_order::error in registering by webservice:'.$response.'::'.$text);
					}
                    $order->update_status( 'pending', 'Frotel : '.$response );
                    $this->trigger($order->id, $order, '::سفارش در سیستم فروتل ثبت نشد::');

         } elseif($res === true) {
            
            if ($this->frotel_carrier->debug) {
                            $this->debug_file->write('@save_order::everything is Ok');
							$woocommerce->clear_messages();
							$woocommerce->add_message('<p>Frotel:</p> <p>Everthing is Ok!</p>');
			}
            $this->trigger($order->id, $order, true);
            update_post_meta($id, '_frotel_tracking_code', trim($response[0]));
            update_post_meta($id, '_frotel_invoice_code', trim($response[1]));
 
         } else {
            $order->update_status( 'pending', 'Frotel : error in webservice, Order not register!' );
            $this->trigger($order->id, $order, false);    
         }
        
    }
    
    public function add_order( $data, $order )
    {
        global $woocommerce;
        
        if ($this->frotel_carrier->debug) {
			$this->debug_file->write('@add_order::here is top of function');
        }
        
        $this->frotel_carrier->client = new nusoap_client( $this->frotel_carrier->wsdl_url, true );
        $this->frotel_carrier->client->soap_defencoding = 'UTF-8';
        $this->frotel_carrier->client->decode_utf8 = true;
            
        $response  = $this->frotel_carrier->call("FSetOrder", $data);
        
        if(is_array($response) && $response['error']){
            if ($this->frotel_carrier->debug) {
                            $this->debug_file->write('@frotel_service::'.$response['message']);
							$woocommerce->clear_messages();
							$woocommerce->add_message('<p>@add_order Frotel Error:</p> <p>'.$response['message'].'</p>');
				}
                
                return array(false, $response['message']);
        }
        
        $response = urldecode($response);
        $response = explode('^^', $response);
           
        if (count($response) != 3) {
            if ($this->frotel_carrier->debug) {
                ob_start();
                var_dump($response);
                $text = ob_get_contents();
                ob_end_clean();
                
			   $this->debug_file->write('@add_order::An error : '.$text);
            }
            
            return array(false, $response);
        }
        if ($this->frotel_carrier->debug) {
                ob_start();
                var_dump($response);
                $text = ob_get_contents();
                ob_end_clean();
                
			   $this->debug_file->write('@add_order::everything is Ok: '.$text);
        }

        return array(true, $response);
        
    }
    
    function trigger( $order_id, $order, $subject= false ) 
    {
		global $woocommerce;
        if(!$subject) {
            $message = $this->email_handle->wrap_message(
		            		'سفارش در سیستم فروتل ثبت نشد',
		            		sprintf( 'سفارش %s در سیستم فروتل ثبت نشد، لطفن بصورت دستی اقدام به ثبت سفارش در پنل شرکت فروتل نمایید.', $order->get_order_number() )
						);

		  $this->email_handle->send( get_option( 'admin_email' ), sprintf('سفارش  %s در سیستم فروتل ثبت نشد', $order->get_order_number() ), $message );
        }else{
            $message = $this->email_handle->wrap_message(
		            		'سفارش با موفقیت در سیستم فروتل ثبت گردید',
		            		sprintf( 'سفارش  %s با موفقیت در سیستم فروتل ثبت گردید.', $order->get_order_number() )
						);

		  $this->email_handle->send( get_option( 'admin_email' ), sprintf( 'سفارش %s در سیستم فروتل با موفقیت ثبت گردید', $order->get_order_number() ), $message );
        }
	}
    
    public function calc_shipping_after_login( $checkout ) 
    {
        global $woocommerce;
        
        $state 		= $woocommerce->customer->get_shipping_state() ;
		$city       = $woocommerce->customer->get_shipping_city() ;
        
        if( $state && $city ) {
            $woocommerce->customer->calculated_shipping( true );
        } else {
  
            wc_add_notice( 'پیش از وارد کردن مشخصات و آدرس، لازم است استان و شهر خود را مشخص کنید.');
            $cart_page_id 	= get_option('woocommerce_cart_page_id' );//wc_get_page_id( 'cart' );
			wp_redirect( get_permalink( $cart_page_id ) );
        }

    }
    
    public function getIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
        {
             $ip = $_SERVER['HTTP_CLIENT_IP'];
        } 
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } 
        else 
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    
    public function show_invoice( $order_id )
    {
        $factor = get_post_meta( $order_id, '_frotel_tracking_code', true);
        
        if( empty($factor))
            return;
        $html = '<p>';
        $html .= 'کد رهگیری سفارش شما.';
        $html .= '</br>';
        $html .= 'این کد را نزد خود نگه‌دارید و با مراجعه به سایت پست از وضعیت سفارش خود آگاه شوید. ';
        $html .= '</br>'. $factor .'</p><div class="clear"></div>';
        
        echo $html;
        return;
    }

    
    public function post_code_validation( $posted )
    {
        $postcode = $posted['billing_postcode'];
        
        if ( !preg_match("/([1]|[3-9]){10}/", $postcode) or strlen( trim( $postcode ) ) !=10 )
			wc_add_notice( 'کد پستی وارد شده معتبر نیست. کد پستی عددی است 10 رقمی فاقد رقم های 0 و 2 .', 'error' );
    }
    
    public function show_mobile_message()
    {
        $msg = 'لطفاً در صورت امکان در فیلد مربوط به تلفن، شماره‌ی تلفن همراه خود را وارد کنید';
        echo '<div class="woocommerce-info">'.$msg.'</div>';
    }
    
    
    public function remove_shipping_calculator()
    {
        if( get_option('woocommerce_enable_shipping_calc')!='no' )
            update_option('woocommerce_enable_shipping_calc', 'no');
    }
    
    public function remove_free_text( $full_label, $method)
    {
        global $woocommerce;
        
        $shipping_city = $woocommerce->customer->city;
        if(!in_array( $method->id, array('frotel_pishtaz' ,'frotel_sefareshi' )))
            return $full_label;

        if(empty($shipping_city))
            return $method->label;
        
        return $full_label;
        
    }
    
    public function remove_country_field( $fields )
    {
        unset( $fields['country'] );
        
        return $fields;
    }
    
    public function add_css_file()
    {
        global $typenow;
        
        if ( $typenow == '' || $typenow == "product" || $typenow == "service" || $typenow == "agent" ) {
             wp_enqueue_style( 'woocommerce_admin_override', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/css/override.css', array('woocommerce_admin_styles') );
        }
    }
    
    public function overriade_js_file()
    {
        global $woocommerce;
        
        wp_deregister_script( 'jquery-tiptip' );
        wp_register_script( 'jquery-tiptip', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/js/jquery.tipTip.min.js', array( 'jquery' ), $woocommerce->version, true );
    }
    
    public function set_state_and_city_in_cart_page()
    {
        global $woocommerce;
        
        $state 		= (woocommerce_clean( $_POST['calc_shipping_state'] )) ? woocommerce_clean( $_POST['calc_shipping_state'] ) : $woocommerce->customer->get_shipping_state() ;
		$city       = (woocommerce_clean( $_POST['calc_shipping_city'] )) ? woocommerce_clean( $_POST['calc_shipping_city'] ) : $woocommerce->customer->get_shipping_city() ;

        if ( $city && $state) {
				$woocommerce->customer->set_location( 'IR', $state, '', $city );
				$woocommerce->customer->set_shipping_location( 'IR', $state, '', $city );
			}else{
                $woocommerce->clear_messages();
                $woocommerce->add_error('استان و شهر را انتخاب کنید. انتخاب هر دو فیلد الزامی است.');
			}
    }
    
    public function add_new_calculator()
    {
        global $woocommerce;
        
        $have_city = true;
        if( ! $woocommerce->customer->get_shipping_city()){
            echo '<style> div.cart_totals{display:none!important;}
                          p.selectcitynotice {display:block;}
                    </style>';
            
            $have_city = false;
        }
    
        include('cart/shipping-calculator.php');
    }
    
    public function remove_proceed_btn()
    {
        echo '<style>input.checkout-button{ display:none!important;}
                    .woocommerce .cart-collaterals .cart_totals table, .woocommerce-page .cart-collaterals .cart_totals table { border:0px; }
              </style>';
    }
    
    public function add_proceed_btn()
    {
        
        echo '<tr style="border:0px;"><td colspan="2" style="padding:15px 0px;border:0px;">
              <input onclick="submitchform();" type="submit" style="padding:10px 15px;" class="button alt" id="temp_proceed" name="temp_proceed" value=" &rarr; اتمام خرید و وارد کردن آدرس و مشخصات" />
              </td></tr>';
    }
    
    public function update_frotel_orders_state()
    {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdab->prepare("SELECT meta.meta_value, posts.ID FROM {$wpdb->posts} AS posts

		LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	meta.meta_key 		= '_frotel_invoice_code'
        AND     meta.meta_value     != ''
		AND 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('processing', 'on-hold', 'pending')
	   "));
       
       if ( $results ) {
            $tracks = array();
            $k=0;
	        foreach( $results as $result ) {
	           $tracks['code'][] = $result->meta_value;
               $tracks['id'][]   = $result->ID;
                
               $k++;
               if($k>=50)
                    break;
		    }
	   }
       
       if( empty($tracks))
            return ;

        if(!is_object($this->frotel_carrier))
            $this->frotel_carrier      = new WC_Frotel_Pishtaz_Method();
        
        $this->frotel_carrier->client = new nusoap_client( $this->frotel_carrier->wsdl_url, true );
        $this->frotel_carrier->client->soap_defencoding = 'UTF-8';
        $this->frotel_carrier->client->decode_utf8 = true;
        
        //for($i = 0; $i < 5; $i++)
        //{  
            $data = array(
                'FaktorNo'               =>  trim(implode(';', $tracks['code'])),
                'SUserName'              =>  $this->frotel_carrier->username,
                'SPassWord'              =>  $this->frotel_carrier->password
                        ); 
            $response  = $this->frotel_carrier->call("FGetStatus", $data);
            
            if(is_array($response) && $response['error']){
                if ($this->frotel_carrier->debug) {
                            $this->debug_file->write('@update_frotel_orders_state::'.$response['message']);
				}
                return;
            }
            
            $response = urldecode($response);
            $response = explode(';', $response);
            
            if(count($response) ==1 && intval($response)<1) {
                if ($this->frotel_carrier->debug) {
                            $this->debug_file->write('@update_frotel_orders_state::'.$response);
				}
                return;
            }

            
            if ($this->frotel_carrier->debug) {
                ob_start();
                var_dump($response);
                $text = ob_get_contents();
                ob_end_clean();
                
			   $this->debug_file->write('@update_frotel_orders_state::everything is Ok: '.$text);
            }
            
            
            $j = 0;
            foreach( $response as $res) {
                
            
            $status = false;
            switch($res) {
                /*case '0': // سفارش جدید
                       $status = 'pending';
                       break; */
                case '2': // آماده به ارسال
                case '3': // ارسال شده
                case '4':  //توزیع شده
                       /*$status = 'processing';
                       break; */
                case '5': // وصول شده
                       $status = 'completed';
                       break; 
                //case '6': // برگشتی اولیه
                case '6': //برگشتی نهایی
                       $status = 'refunded';
                       break; 
                case '7': // انصرافی
                       $status = 'cancelled';
                       break; 
            }
            if ( $status )
            {
                $order = new WC_Order( $tracks['id'][$j] );
	            $order->update_status( $status, 'سیستم فروتل @ ' );
            }
            $j++;
            } //end foreach
            
         //}// end for   
            
    }
    
    // thanks to  woocommerce parsi
    public function check_currency( $currencies ) 
    {
        if(empty($currencies['IRR'])) 
            $currencies['IRR'] = __( 'ریال', 'woocommerce' );
        if(empty($currencies['IRT'])) 
            $currencies['IRT'] = __( 'تومان', 'woocommerce' );
        
        return $currencies;
    }
    
    public function check_currency_symbol( $currency_symbol, $currency ) 
    {

        switch( $currency ) {
            case 'IRR': $currency_symbol = 'ریال'; break;
            case 'IRT': $currency_symbol = 'تومان'; break;
        }
        
        return $currency_symbol;
          
    }
    
    public function woocommerce_states($st) 
    {
        return false;
    }
}
     
    $GLOBALS['Frotel'] = new WC_Frotel();

}