<?php
/**
 * Shipping Calculator
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.8
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;


?>

<?php do_action( 'woocommerce_before_shipping_calculator' ); ?>

<form class="shipping_calculator" action="<?php echo esc_url( $woocommerce->cart->get_cart_url() ); ?>" method="post" style="width: 65%!important;">

	<p class="selectcitynotice" style="display:none;padding: 10px 5px; background: #F2EABB; font:12px tahoma;border-radius: 5px;">استان و شهر خود را انتخاب کنید تا روش های ارسال ، هزینه هر روش و  جمع کل سفارش شما محاسبه شود</p>

	<section class="shipping-calculator">

        <script type="text/javascript">

function submitchform(){
     jQuery('input.checkout-button').click();
    }
jQuery(document).ready(function($) {
    function select_list_sync_to_input(iid, iinput) {
        $("select#"+iid).change(function(){
            
            var val_now = $("select#"+iid+" option:selected").val();
            if(val_now != 0){
                if(iid == "id_shahr") {
                    $("input#"+iinput).val($("select#"+iid+" option:selected").val());  
                } else {
                    $("input#"+iinput).val($("select#"+iid+" option:selected").val()+'-'+$("select#"+iid+" option:selected").text());  
                }
                
            }else{
                $("input#"+iinput).val('');
            }
        
        });
    }
    
    select_list_sync_to_input('id_ostan', 'shipping_state');
    select_list_sync_to_input('id_shahr', 'shipping_city');
    
    function set_initial_val(iid, ival) {
        jQuery("select#"+iid).val(ival).trigger('onchange');
    }
    
    
    <?php 
    
    $my_state = $woocommerce->customer->get_shipping_state();
    $my_state = explode('-', $my_state);
    if(isset($my_state) && intval($my_state[0]) > 0 ){
        ?>
        set_initial_val('id_ostan', <?php echo $my_state[0]; ?>);
        <?php
    }
    
    $my_city = $woocommerce->customer->get_shipping_city();
    //$my_city = explode('-', $my_city);
    if(isset($my_city)){
        ?>
        set_initial_val('id_shahr', "<?php echo $my_city; ?>");
        <?php
    }
    
    ?>

});
    </script>
    <style>
    select{font:12px tahoma; padding: 2px 1px;}
    </style>
    <p class="form-row form-row-last" id="billing_state_field" data-o_class="form-row form-row-last address-field"><label for="billing_state" class="">استان<abbr class="required" title="ضروری">*</abbr></label>
        <select name="id_ostan" class="text" onChange="ldMenu(this.selectedIndex);" dir="rtl" id="id_ostan">
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
        <input type="hidden" name="calc_shipping_state" id="shipping_state" value="" />
    </p>
    <script type='text/javascript'>p24_load_province_list('id_province');</script>
    
    <p class="form-row form-row-first address-field  update_totals_on_change" id="billing_city_field" data-o_class="form-row form-row-first address-field"><label for="billing_city" class="">شهر <abbr class="required" title="ضروری">*</abbr></label>
        <select name="id_shahr" id="id_shahr" size="1" dir="rtl" class="text">
									<option selected value="">لطفا استان خود را انتخاب کنید</option>
		</select>
        <input type="hidden" name="calc_shipping_city" id="shipping_city" value="" />
	</p>

		<p><button type="submit" name="calc_shipping" value="1" class="button" style="width: 60%;"><?php echo $have_city ? 'محاسبه مجدد جمع کل' : 'محاسبه جمع کل'; ?></button></p>

		<?php $woocommerce->nonce_field('cart') ?>
	</section>
</form>

<?php do_action( 'woocommerce_after_shipping_calculator' ); ?>