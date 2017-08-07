<div class="container">
  <h2>Confession Form</h2>
<div class="wpcb_messages"> </div>
<form>
<?php wp_nonce_field( 'validate_confession', 'verify_cf_submission' ); ?>
<div class="form-group">
<label for="wpcb_title"><?php _e('Confession Title',''); ?></label>
<input type="text" name="wpcb_title" id="wpcb_title" >
</div>

<div class="form-group">
<label for="wpcb_category"><?php _e('Confession Category',''); ?></label>
<select id="wpcb_category" name="wpcb_category">
<?php
$wpcb_category=apply_filters("wpcb_add_categories",array(
  __("Family",""),
  __("Friends",""),
  __("Relative",""),
  __("School / College",""),
  __("Office",""),
  __("Journey",""),
  __("Habbit",""),
  __("Other",""),
  ));

foreach($wpcb_category as $catID => $category){
echo '<option value="'.($catID+1).'">'.$category.'</option>';
}
?>
</select>
</div>

<div class="form-group">
<label for="wpcb_description"><?php _e('Confession Description',''); ?></label>
<i><?php _e('(Atleast 200 characters)',''); ?></i>
<textarea type="text" col="50" name="wpcb_desc" id="wpcb_desc" placeholder="<?php __('Add the description here');?>"></textarea>
</div>

<div class="form-group">
<label for="wpcb_author_name"><?php _e('Your Name',''); ?></label>
<input type="text"  name="wpcb_author_name" id="wpcb_author_name" >
</div>

<?php do_action('wpcb_after_wpcb_form_fields'); ?>

<div class="form-group">
<label></label>
<input type="button" id="wpcb_add_confession"  value="<?php _e('Add Confession',''); ?>" >
</div>

</form>
</div>