<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if (!class_exists('WPCB_Controller')) {
class WPCB_Controller {
	public $DB,$wpcb_manager,$wpcb_likes_manager;

function __construct(){
global $wpdb;	
$this->DB=$wpdb;
$this->wpcb_manager=$this->DB->prefix.'wpcb_manager';
$this->wpcb_likes_manager=$this->DB->prefix.'wpcb_likes_manager';
add_shortcode('wp-confession-form',array($this,'wpcb_form'));
add_shortcode('wp-confession-box',array($this,'wpcb_box'));
add_action('wp_ajax_cf_save_confession',array($this,'wpcb_manage_confession'));
add_action('wp_ajax_nopriv_cf_save_confession',array($this,'wpcb_manage_confession'));

add_action('wp_ajax_fetch_old_confession',array($this,'wpcb_fetch_confession'));
add_action('wp_ajax_nopriv_fetch_old_confession',array($this,'wpcb_fetch_confession'));

add_action('wp_ajax_wpcb_sync_confessions',array($this,'wpcb_fetch_confession'));
add_action('wp_ajax_nopriv_wpcb_sync_confessions',array($this,'wpcb_fetch_confession'));

add_action('wp_ajax_manage_confession_likes',array($this,'manage_confession_likes'));
add_action('wp_ajax_nopriv_manage_confession_likes',array($this,'manage_confession_likes'));

add_action('wp_ajax_manage_confession_actions',array($this,'manage_confession_actions'));
add_action('wp_ajax_nopriv_manage_confession_actions',array($this,'manage_confession_actions'));

} //construct

function manage_confession_actions(){
$log=array();
$action=sanitize_text_field($_POST['apply']);
$confession_id=intval($_POST['confession_id']);

if($confession_id==0){
$log['error'] = __('Invalid Confession ID','');
}
elseif(empty($action)){
$log['error'] = __('Invalid action','');
}else{

   if($action=='delete'){
      $ck1=$this->DB->delete( $this->wpcb_manager, array('id'=>$confession_id));
      if( $ck1!==false){
         $this->DB->delete( $this->wpcb_likes_manager, array('confession_id'=>$confession_id));
         $log['success'] = __("Confession has been deleted successfully!",'');
         $log['action'] = 'deleted';
      }else{
         $log['error']= __('Could not apply this action!');
      }
   }elseif($action=='block'){
      $exist=$this->DB->get_results("select id from $this->wpcb_manager where id='$confession_id' AND blocked='1'");

      if(!empty($exist)){
         $block_action=0;
      }else{
         $block_action=1;
      }
      $ck1=$this->DB->update($this->wpcb_manager,array('blocked'=>$block_action),array('id'=>$confession_id));

      if($ck1!==false){
         if($block_action==1){
         $log['success'] = __("This confession has been blocked successfully !","");
         $log['action'] = 'blocked';
         }else{
         $log['success'] = __("This confession has been unblocked successfully !","");
         $log['action'] = 'unblocked';
         }
         
      }

   }else{
      $log['error'] = __('Invalid Action !','');
   }

}

echo json_encode($log);
   wp_die();
}

function manage_confession_likes(){
global $wpdb;

$user_id=get_current_user_id();
$likes_manage_by=get_option("wpcb_likes_manage_by",'uid');
$ip=$_SERVER['REMOTE_ADDR'];
$conf_id=intval($_POST['confession_id']);
$apply=sanitize_text_field($_POST['apply']);
$log=array();
if($conf_id==0){
$log['error'] = __('Invalid Confession ID','');
}
elseif(empty($apply)){
$log['error'] = __('Invalid action','');
}else{

$applied=(int)(($apply=='like') ? '1' : '0');

if ($likes_manage_by=='uid') {

   if(is_user_logged_in() && $user_id!=0){
      $if_exist=$this->DB->get_results("select id from $this->wpcb_likes_manager where confession_id='$conf_id' AND user_id='$user_id' ");

      if(empty($if_exist)){
      $params=array(
      'confession_id' => $conf_id,
      'ip_address' => $ip,
      'user_id' => $user_id,
      'applied' => $applied,
         );
      $check=$this->DB->insert($this->wpcb_likes_manager,$params);
      }else{
      $check=$this->DB->update($this->wpcb_likes_manager,array('applied' => $applied),array('id'=>$if_exist[0]->id));
      }

      if($check!=false){
      $status=$this->wpcb_get_confession_popularity($conf_id);
      $log['success']= $status;
      //$log['action']=$applied;
      }else{
      $log['error']= __('Could not apply this action!');
      }

   }else{
      $log['error'] = __("Please sign-in to like this confession",'');
   }
   }
// elseif($likes_manage_by=='ip'){
//    $if_exist=$this->DB->get_results("select id from $this->wpcb_likes_manager where confession_id='$conf_id' AND ip_address='$ip' ");

//       if(empty($if_exist)){
//       $params=array(
//       'confession_id' => $conf_id,
//       'ip_address' => $ip,
//       'user_id' => $user_id,
//       'applied' => $applied,
//          );
//       $check=$this->DB->insert($this->wpcb_likes_manager,$params);
//       }else{
//       $check=$this->DB->update($this->wpcb_likes_manager,array('applied' => $applied),array('id'=>$if_exist[0]->id));
//       }

//       if($check!=false){
//       $status=$this->wpcb_get_confession_popularity($conf_id);
//       $log['success']= $status;
//       }else{
//       $log['error']= __('Could not apply this action!','');
//       }
// }

}
echo json_encode($log);
wp_die();
}

function wpcb_get_confession_popularity($conf_id){
$likes=0;
$dislikes=0;
$ip_arr=array();
$uid_arr=array();
$likes_manage_by=get_option('wpcb_likes_manage_by','uid');
$user_id=get_current_user_id();
$ip=$_SERVER['REMOTE_ADDR'];
$current_user_action='na';
$who_liked='';
$who_disliked='';
$popularity=$this->DB->get_results("select applied,ip_address,user_id from $this->wpcb_likes_manager where confession_id='$conf_id'");
if(!empty($popularity)){
   foreach ($popularity as $action) {
      if($likes_manage_by=='uid' && is_user_logged_in() && $user_id==$action->user_id){
      $current_user_action=(($action->applied==1) ? 'liked' : 'disliked');
      }
      // elseif($likes_manage_by=='ip' && $action->ip_address==$ip){
      // $current_user_action=(($action->applied==1) ? 'liked' : 'disliked');
      // }

      $applier=get_user_by('id',$action->user_id);
      $u='<em id="'.esc_attr($action->user_id).'">'.esc_html($applier->display_name).'</em>';
      if($action->applied==1){
         $likes++;
         $who_liked.=$u;
      }elseif ($action->applied==0) {
         $dislikes++;
         $who_disliked.=$u;
      }
   }
}


return array('likes' => $likes ,'dislikes' => $dislikes,'current_user_action'=>$current_user_action,'who_liked'=>$who_liked,'who_disliked'=>$who_disliked);

}


function wpcb_fetch_confession(){
global $current_user;

$last_con_id=intval($_POST[ 'last_con_id' ]);
if($last_con_id!==0){
$where ="id > $last_con_id";
$class="hide_new";
$new=true;
}else{
$where ="1";
$class="";
$new=false;
}

$all_old_confession=$this->DB->get_results("select * from $this->wpcb_manager WHERE $where order by id DESC");
$odd='';
$even='';

if(!empty($all_old_confession) && count($all_old_confession)>0 ){

foreach ($all_old_confession as $each_confession) {
   if($each_confession->blocked==1 && !in_array('administrator', $current_user->roles)){
      continue;
   }

   $popularity=$this->wpcb_get_confession_popularity($each_confession->id);
   
   if($popularity['current_user_action']=='liked'){
      $c1='liked';
   }else{
      $c1='';
   }
   
   if($popularity['current_user_action']=='disliked'){
      $c2='disliked';
   }else{
      $c2='';
   }

   $str='<div id="'.esc_attr($each_confession->id).'" class="'.esc_attr($class).'">
         <p id="'.esc_attr($each_confession->id).'">
      <strong>'.esc_html($each_confession->title).' -</strong><br>'.esc_html($each_confession->confession).'<br>
      <strong><em style="text-align:right">-'.esc_html($each_confession->author_name).'('.date_i18n( get_option( 'date_format' ), strtotime( $each_confession->created_at ) ).')'.'</em></strong>&nbsp;&nbsp;&nbsp;&nbsp;
      <span class="dashicons dashicons-thumbs-up like-cf '.$c1.'"></span>
      <span class="hide" id="cb_who_liked">'.$popularity['who_liked'].'</span>
      <span class="like_counts">('.$popularity['likes'].')</span> &nbsp;&nbsp;&nbsp;&nbsp;
      <span class="dashicons dashicons-thumbs-down like-cf '.$c2.'"></span> 
      <span class="hide" id="cb_who_disliked">'.$popularity['who_disliked'].'</span>
      <span class="dislike_counts">('.$popularity['dislikes'].')</span>&nbsp;&nbsp;&nbsp;&nbsp;';
      if(in_array('administrator', $current_user->roles)){
      $str.='<span class="dashicons dashicons-trash cb_delete_confession"></span>&nbsp;&nbsp;&nbsp;&nbsp;
      <span class="dashicons dashicons-hidden cb_block_confession '.(($each_confession->blocked==1) ? 'blocked' : '').'"></span>';
      }
      $str.='
      <br>
      <span style="display:none" class="conf_msg"></span>
      </p>
      </div>';

   if(($each_confession->id % 2)==1){
      $odd.=$str;
   }	
   else{
      $even.=$str;
   }
}

} // confession main if
echo json_encode(array('odd'=>$odd,'even'=>$even,'new'=>$new));
wp_die();
}

function wpcb_manage_confession(){


$log=array();
if (! isset( $_POST['verify_cf_submission'] ) || ! wp_verify_nonce( $_POST['verify_cf_submission'], 'validate_confession' ) ) {
$log['error'] = __("Non verified submission","");
}elseif (empty($_POST['cf_title']) || strlen($_POST['cf_title']) < 3) {
	$log['error'] = __("Title is required","");
}elseif (strlen($_POST['cf_desc'])<200) {
	$log['error'] = __("Description is required","");
}elseif (strlen($_POST['cf_author'])<3) {
	$log['error'] = __("Author is required","");
} else {

   $params=array(
   		'author_name' => sanitize_text_field($_POST['cf_author']),
   		'age' => 0,
   		'location' => '',
   		'title' => sanitize_text_field($_POST['cf_title']),
   		'confession' => sanitize_text_field($_POST['cf_desc']),
   		'category' => intval($_POST['cf_category']),
   		'approved' => get_option('wpcb_approval_before_publish',0),
   		'created_at'=> date('Y-m-d'),
   		'ip_address'=> $_SERVER['REMOTE_ADDR'],
   		'blocked' => 0
   	);

   $return=$this->DB->insert( $this->wpcb_manager, $params);

   if($return!=false){
   	if(get_option('wpcb_approval_before_publish')){
   		$log['success']=__('Confession submitted successfully !','');
   	}else{
   		$log['success']=__('Confession submitted successfully ! now waiting for Admin approval.','');
   	}
   	
   }
}

echo json_encode($log);
wp_die();
}

function wpcb_box(){
global $wp_confession_box;
	include($wp_confession_box->wpcb_dirpath.'view/confessions-box.php');
}

function wpcb_form(){
	global $wp_confession_box;

	//wp_enqueue_style( 'wpcb-bootstrap-css');
	//wp_enqueue_script( 'wpcb-tether-js');
	//wp_enqueue_script( 'wpcb-bootstrap-js');

	include($wp_confession_box->wpcb_dirpath.'view/confession-form.php');
}

} //class
new WPCB_Controller;
} //if
?>