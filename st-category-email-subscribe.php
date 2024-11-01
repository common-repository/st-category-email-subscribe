<?php
/**
	Plugin Name: St Category Email Subscribe
	Plugin URI: http://www.sanskruti.net
	Description: Plugin that allows Users to Subscribe for Emails based on Category.They will receive an email when a post is published in the category they have subscribed to.
	Version: 1.4
	Author: Sanskruti Technologies
	Author URI: http://www.sanskruti.net
	Author Email: info@sanskruti.net
	License: GPL

	Copyright 2014 Sanskruti Technologies  (email : support@sanskruti.net)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	GNU General Public License: http://www.gnu.org/licenses/gpl.html
	
  TO Do :

  100 	Allow to edit subscriber
  110	Logs of Email Sent. Failures etc...  
  120	Import Subscribers
  130	Send Email when someone subscribes. According to setting
  140	Send Email when someone unsubscribes. According to setting
  150	Send Email of Log of Email sends
  160	How to Import Export Category
  170	Send Email to user for confirmation
  180	Allow to select multiple categories
  190	Allow user to update their subscription / unsubscribe
 */
 

/* If no Wordpress, go home */
if (!defined('ABSPATH')) { exit; }

/* Load Language */
add_action( 'plugins_loaded', 'st_email_load_textdomain' );
function st_email_load_textdomain() {
	load_plugin_textdomain('stemail', false,  dirname( plugin_basename( __FILE__ ) ) . "/language/");
}	

define('WP_ST_CATEGORY_EMAIL_FOLDER', dirname(plugin_basename(__FILE__)));
define('WP_ST_CATEGORY_EMAIL_URL', plugins_url('', __FILE__));

/**
 * 2. Global Parameters
 */
global $st_email_table_suffix;
global $st_category_email_db_ver;
$st_category_email_db_ver = "1.2";
$st_email_table_suffix = "st_category_email";

/**
 * 3. Activation / deactivation
 */
register_activation_hook(__FILE__, 'st_category_email_install');
register_deactivation_hook(__FILE__, 'st_category_email_uninstall');

function st_category_email_install() {
	global $wpdb;
	global $st_category_email_db_ver;
	global $st_email_table_suffix;
	
	$st_email_table = $wpdb->prefix . $st_email_table_suffix;
	
	$db_ver=get_option('st_category_email_db_ver',"0.5");
	$db_ver=(float) $db_ver;

	$st_category_email_db_ver = (float) $st_category_email_db_ver;
	
	//Create table for subscribers
	$sql = "CREATE TABLE IF NOT EXISTS $st_email_table  (
		st_id INT(9) NOT NULL AUTO_INCREMENT,
		st_name VARCHAR(200),
		st_email VARCHAR(200) NOT NULL,
		st_category bigint(20),
		UNIQUE KEY st_id (st_id)
	);";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	$sql = "ALTER TABLE $st_email_table CHANGE st_category st_category VARCHAR(250);";
	$wpdb->query($sql);
	
	$sql = "ALTER TABLE $st_email_table ADD st_unsubscribe INT NULL AFTER st_category;";
	$wpdb->query($sql);
	
	$sql = "ALTER TABLE $st_email_table ADD st_unsubscribe INT NULL AFTER st_category;";
	$wpdb->query($sql);
	
	$sql = "ALTER TABLE $st_email_table ADD st_status VARCHAR(10) NOT NULL DEFAULT 'pending';";
	$wpdb->query($sql);
	
	//Set DB Version
	update_option("st_category_email_db_ver", $st_category_email_db_ver);
	
    //Set Send Email
	update_option( 'st_category_email_send_email', get_option('admin_email') );
	
	//Set From Name
	update_option( 'st_category_email_from_name', get_option('blogname') );
}

function st_category_email_uninstall() {
	/** Do Nothing **/	
}

/** Short Code to display Subscription Form **/
add_shortcode("st_category_subscribe_form", "st_category_email_subscribe_shortcode");

/** Short Code to display My Subscription Form **/
add_shortcode("st_category_my_subscription", "st_category_email_my_subscription_form");

/** Admin Page **/
if (is_admin()) {
    require_once dirname(__FILE__) . '/st_category_email_subscribe_admin.php';
	require_once dirname( __FILE__ ) . '/st_category_email_subscribe_export_csv.php';
    add_action('admin_print_scripts', 'st_category_email_subscribe_admin_scripts');
}
function st_category_email_subscribe_admin_scripts() {
	wp_register_style('st-category-email-style.css',WP_ST_CATEGORY_EMAIL_URL.'/css/style.css');
	wp_enqueue_style('st-category-email-style.css');
	
	wp_register_style('st-category-email-multiple-select.css',WP_ST_CATEGORY_EMAIL_URL.'/css/multiple-select.css');
	wp_enqueue_style('st-category-email-multiple-select.css');
	
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-datepicker');
	
	wp_enqueue_script( 'st-category-email-jquery.multiple.select.js', WP_ST_CATEGORY_EMAIL_URL . '/scripts/jquery.multiple.select.js', array(), '1.0.0', true );
	wp_enqueue_script( 'st-category-email-jquery.csv.js', WP_ST_CATEGORY_EMAIL_URL . '/scripts/jquery.csv.js', array(), '1.0.0', true );
	wp_enqueue_script( 'st-category-email-admin_scripts.js', WP_ST_CATEGORY_EMAIL_URL . '/scripts/admin_scripts.js', array(), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'st_category_email_subscribe_scripts' );

function st_category_email_subscribe_scripts() {
	wp_register_style('st-category-email-multiple-select.css',WP_ST_CATEGORY_EMAIL_URL.'/css/multiple-select.css');
	wp_enqueue_style('st-category-email-multiple-select.css');
	
	wp_enqueue_script('jquery');
	wp_enqueue_script( 'st-category-email-jquery.multiple.select.js', WP_ST_CATEGORY_EMAIL_URL . '/scripts/jquery.multiple.select.js', array(), '1.0.0', true );
	wp_enqueue_script( 'st-category-email-scripts.js', WP_ST_CATEGORY_EMAIL_URL . '/scripts/scripts.js', array(), '1.0.0', true );
}

function st_category_email_subscribe_form($atts){
	extract($atts);
	
	$enable_categories = get_option( 'st_category_email_enable_categories' );
	
	$return = '<form class="st_subscribe_form" method="post"><input class="st_hiddenfield" name="st_subscribe_form" type="hidden" value="1">';
	
	if ($prepend) $return .= '<p class="st_prepend">'.$prepend.'</p>';
	
	if (isset($_POST['st_subscribe_form']) && $thankyou) { 
		
		$email = $_POST['st_email'];
		$email = filter_var($email, FILTER_SANITIZE_EMAIL);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$return .= '<p class="st_error">' . _e('Please Check.Email Address is Invalid','stemail') . '</p>'; 
		}elseif ($thankyou){
			if ($jsthanks == 'true') {
				$return .= "<script>window.onload = function() { alert('".$thankyou."'); }</script>";
			} else {
				$return .= '<p class="st_thankyou">'.$thankyou.'</p>'; 
			}
		}	
	}
	
	if ($showname == 'true') $return .= '<p class="st_name"><label class="st_namelabel" for="st_name">'.$nametxt.'</label><input class="st_nameinput" placeholder="'.$nameholder.'" name="st_name" type="text" value=""></p>';
	
	$return .= '<p class="st_email"><label class="st_emaillabel" for="st_email">'.$emailtxt.'</label><input class="st_emailinput" name="st_email" placeholder="'.$emailholder.'" type="text" value=""></p>';
	
	$select_cats = wp_dropdown_categories("name=st_category[]&id=st_category&echo=0&hide_empty=0&hierarchical=1&class=multipleSelect");	
	$select_cats = str_replace( 'id=', 'multiple="multiple" id=', $select_cats );
	if ($showcategory == 'true' && $enable_categories == 1) $return .= '<p class="st_category"><label class="st_categorylabel" for="st_category">'.$categorytxt.'</label><br/>'  . $select_cats . '</p>';
	$return .= '<p class="st_submit"><input name="submit" class="btn st_submitbtn" type="submit" value="'.($submittxt?$submittxt:'Submit').'"></p>';
	
	$return .= '</form>';
	
 	return $return;
}

function st_category_email_subscribe_shortcode($atts=array()){
	$atts = shortcode_atts(array(
		"prepend" => 'Like our posts? Subscribe to our newsletter',  
        "showname" => 'true',
		"nametxt" => 'Name:',
		"nameholder" => 'Name...',
		"emailtxt" => 'Email:',
		"emailholder" => 'Email Address...',
		"showcategory" => 'true',
		"categorytxt" => 'Category:',
		"submittxt" =>'Submit',
		"jsthanks" => 'false',
		"thankyou" => 'Thank you for subscribing to our mailing list'
    ), $atts);
	
	return st_category_email_subscribe_form($atts);
}
function st_category_email_my_subscription_set(){
	if (isset($_POST['st_subscribe_my_subscription_form'])) {
		global $wpdb;
		global $st_email_table_suffix;
		$subscribers_table = $wpdb->prefix . $st_email_table_suffix;
		
		$unsubscribe = $_POST['unsubscribe'];
		
		//Check if entry is there
		$current_user = wp_get_current_user();
		$email = $current_user->user_email;
		$name = $current_user->user_firstname . " " . $current_user->user_lastname;
		
		$st_category = "";
		if(isset($_POST['st_category'])){
			$selected_categories = $_POST['st_category'];
			$st_category =  implode(",",$selected_categories);
			
				}
		$results=$wpdb->get_results("SELECT * FROM ".$subscribers_table." where st_email='".esc_sql($email)."'");
		if($wpdb->num_rows == 0){
			//Insert
			$wpdb->insert($subscribers_table,array('st_name'=>esc_sql($name), 'st_email'=>esc_sql($email),'st_category'=>$category));
		}else{
			//Update
			$wpdb->update($subscribers_table,array('st_name'=>esc_sql($name), 'st_category'=>$st_category),array('st_email'=>esc_sql($email)));
			//echo $wpdb->last_query;
		}
	}	
}
add_action('init', 'st_category_email_my_subscription_set');

function st_category_email_my_subscription_form(){
	global $wpdb;
	global $st_email_table_suffix;
	$subscribers_table = $wpdb->prefix . $st_email_table_suffix;
		
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$email = $current_user->user_email;
		
		
		$subscriber = $wpdb->get_row("SELECT * FROM ".$subscribers_table." where st_email='".esc_sql($email)."'");
		$unsubscribe =  $subscriber->st_unsubscribe;
		$subscribed_category =  $subscriber->st_category;
		
		$form =  "<h2>Newsletter Subscription</h2>";
		$form .= "<form action='' method='post' enctype='multipart/form-data' class='form-horizontal'>";
		$categories = get_categories(array(
				'hide_empty'  => 0
		));
		foreach($categories as $category){
			$selected = "";
			if (strpos($subscribed_category, (string)$category->term_id) !== false) {
				$selected = "checked";
			}else{
				$selected = "";
			}
			$form .= "<input type='checkbox' name='st_category[]' value='".$category->term_id."' $selected>".$category->name."</br>";
			//
		}
		$form .= "</br>Check the categories for which you want to receive mails";
		$form .= "</br>";
		$form .= "	<div class='buttons clearfix'>";
		$form .= "		<div class='pull-right'>";
		$form .= "			<input type='submit' name='st_subscribe_my_subscription_form' value='Save' class='btn btn-primary'>";
		$form .= "		</div>";
		$form .= "	</div>";
		$form .= "</form>";
		return $form;
	} else {
		return 'Please login to Manage Your Subscription';
	}
}

add_action( 'init', 'st_register_subscriber' );

function st_register_subscriber(){
// Handle form Post
if (isset($_POST['st_subscribe_form'])) {
	
	global $wpdb;
	global $st_email_table_suffix;
    $subscribers_table = $wpdb->prefix . $st_email_table_suffix;
	
	$name = "";
	if(isset($_POST['st_name'])){
		$name = filter_var($_POST['st_name'], FILTER_SANITIZE_STRING);
	}
	
	
				
	$category = "";
	if(isset($_POST['st_category'])){
		$category = $_POST['st_category'];
		$category = implode(",",$_POST['st_category']);
		$category = filter_var($category, FILTER_SANITIZE_STRING);
	}
	
	$email = $_POST['st_email'];
	if ($email = filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$exists=$wpdb->get_results("SELECT * FROM ".$subscribers_table." where st_email like '".esc_sql($email)."' limit 1");
		$exists=$wpdb->num_rows;
		if($wpdb->num_rows<1){
			$wpdb->insert($subscribers_table,array('st_name'=>esc_sql($name), 'st_email'=>esc_sql($email),'st_category'=>$category));
			send_verification_email($email);
		}
	}
}

	$args = array(
			'slug' => 'st_email_verified',
			'post_title' => 'Email Verified',
			'post_content' => 'Thank you for verifying your email. You will now be able receive post updates from us.'
	);
	new WP_EX_PAGE_ON_THE_FLY($args);
	
if (isset($_REQUEST['action'])){
	global $wpdb;
	global $st_email_table_suffix;
	
	$st_email_table = $wpdb->prefix . $st_email_table_suffix;
	
	
	if($_REQUEST['action'] == 'st_subscribe'){
		$email =  $_REQUEST['email_id'];
		$email = filter_var($email, FILTER_VALIDATE_EMAIL);
		$email = st_category_email_subscribe_encrypt_decrypt('decrypt', $email);
		
		echo site_url('manage_category_subscription');
		exit;
	}
	if($_REQUEST['action'] == 'st_verify_email'){
		$email =  $_REQUEST['email'];
		$email = filter_var($email, FILTER_VALIDATE_EMAIL);
		$email = st_category_email_subscribe_encrypt_decrypt('decrypt', $email);
		
		//verify user
		$data = array('st_status' => 'verified');
		$where = array('st_email' => $email);
		$wpdb->update( $st_email_table, $data, $where );
		
		wp_redirect(site_url('st_email_verified'));
		exit;
	}
}

}

function st_set_html_content_type() {
	return 'text/html';
}
function send_verification_email($email){

	$send_email = get_option( 'st_category_email_send_email' );
	$from_name = get_option( 'st_category_email_from_name' );
	
	//From Name <Email>
	$headers[] = 'From: '.$from_name.' <'.$send_email.'>';
	
	$encrypt_email_address = st_category_email_subscribe_encrypt_decrypt('encrypt', $email);
	$link = site_url("?action=st_verify_email&email=$encrypt_email_address");

	$blog_name = get_bloginfo('name');
	$subject = "$blog_name - Verify Subscription";
	
	$body = "<head>
			<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
			<meta name='viewport' content='width=device-width'>
			<title>$subject</title>
			</head>

			<body>
			<h1 style='box-sizing: border-box; font-size: 1.25rem; margin: 0; margin-bottom: 0.5em; padding: 0;'>Thanks for subscribing to $blog_name!</h1>
			<p style='box-sizing: border-box; margin: 0; margin-bottom: 0.5em; padding: 0;'>Please confirm that your email address is correct to continue. Click the link below to get started.</p>
			
			<p class='mt2 mb2 mt3--lg mb3--lg' style='box-sizing: border-box; margin: 0; margin-bottom: 20px; margin-top: 20px; padding: 0;'>
				<span class='button__shadow' style='border-bottom: 2px solid rgba(0,0,0,0.1); border-radius: 4px; box-sizing: border-box; display: block; width: 100%;'>
					<a class='button' href='$link' style='background: #204dd5; border: 1px solid #000; border-radius: 3px; box-sizing: border-box; color: white; display: block; font-size: 1rem; font-weight: 600; padding: 12px 20px; text-align: center; text-decoration: none; width: 100%;' target='_blank'>
						Confirm Email Address
					</a>
				</span>
			</p>

			</body>";
	
	add_filter('wp_mail_content_type', 'st_set_html_content_type');
	if(wp_mail($email, $subject, $body, $headers)){
		//mail sent!
	} else {
		//failure
	}
}

function st_apply_template($post_detail,$template){
	include( $template );
	
	foreach($post_detail as $key => $value){
		$st_category_email_template = str_replace("%$key%",$value,$st_category_email_template);
	}
	//Blog Name
	$st_category_email_template = str_replace('%blog_name%',$post_detail['blog_name'],$st_category_email_template);
	//Post Title
	$st_category_email_template = str_replace('%post_title%',$post_detail['post_title'],$st_category_email_template);
	//Post Link
	$st_category_email_template = str_replace('%post_link%',$post_detail['post_link'],$st_category_email_template);
	//Author Link
	$st_category_email_template = str_replace('%author_link%',$post_detail['author_link'],$st_category_email_template);
	//Author Name
	$st_category_email_template = str_replace('%author_name%',$post_detail['author_name'],$st_category_email_template);
	//Post Content
	$st_category_email_template = str_replace('%post_content%',$post_detail['post_content'],$st_category_email_template);
	//Post Date
	$st_category_email_template = str_replace('%post_date%',date("M d,Y",strtotime($post_detail['post_date'])),$st_category_email_template);
	
	
	//March 7, 2014 at 5:08 pm
	return $st_category_email_template;
}
//Send Email on Publish Post
add_action('publish_post','st_send_email');


//send notification e-mail on story publish
function st_send_email($post_ID){
	global $wpdb;
	global $st_email_table_suffix;

	$table_name = $wpdb->prefix . $st_email_table_suffix;
	
	$send_email = get_option( 'st_category_email_send_email' );
	$from_name = get_option( 'st_category_email_from_name' );
	
	//From Name <Email>
	$headers[] = 'From: '.$from_name.' <'.$send_email.'>';
	
	
	$post = get_post($post_ID); 
	// Post Title
	$subject = $post->post_title;
	$post_detail['post_title'] = $post->post_title;
	$post_detail['post_date'] = $post->post_date;
	//Post Link
	$post_detail['post_link'] = get_permalink( $post_ID );
	//Author
	$post_detail['author_name'] = get_the_author_meta( 'display_name', $post->post_author );
	$post_detail['author_link'] = get_the_author_meta( 'display_name', $post->post_author );
	
	//Blog Name
	$post_detail['blog_name']  = get_bloginfo('name');
	
	//Template
	
	// Post Content
	$post_detail['post_content']=$post->post_content;
	$body = st_apply_template($post_detail,'templates/template1.php');
	// Get the Categories of the Post
	$categories = get_the_category($post_ID);
	//Get all the email address who have subscribed to this categories	
	if($categories){
		foreach($categories as $category) {
			$table_result = $wpdb->get_results("SELECT * FROM ".$table_name." where (st_category = ".esc_sql($category->term_id) ." OR st_category = 0) AND IFNULL(st_unsubscribe,0) != 1 AND st_status='verified'");
			foreach ( $table_result as $table_row ) 
			{
				$email_address = $table_row->st_email;
				$encrypt_email_address = st_category_email_subscribe_encrypt_decrypt('encrypt', $email_address);
				//Unsubscribe
				$link = site_url( '?action=st_subscribe&email_id='.$encrypt_email_address );
				$unsubscribe = "<a href='$link'><small>Unsubscribe</small></a>";
				$post_detail['unsubscribe']=$unsubscribe;
				//Template
				$body = st_apply_template($post_detail,'templates/template1.php');
				add_filter('wp_mail_content_type', 'st_set_html_content_type');
				if(wp_mail($email_address, $subject, $body, $headers)){
					//mail sent!
				} else {
					//failure
				}
			}
		}
	}

	//get e-mail address from post meta field
	$email_address = get_option( 'st_category_email_send_email' );
	add_filter('wp_mail_content_type', 'st_set_html_content_type');
	
	
}
function st_category_email_subscribe_encrypt_decrypt($action, $string) {
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = 'SanskrutiTechnologies';
    $secret_iv = 'EmailCategorySubscribe';
    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if( $action == 'decrypt' ) {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}

/**
 * Add function to widgets_init that'll load our widget.
 */
 add_action('widgets_init','st_category_email_subscribe_load_widget');

class st_category_email_subscribe_widget extends WP_Widget {
	/**
	 * Widget setup.
	 */
	 function __construct() {
		parent::__construct(
		// Base ID of your widget
		'st_category_email_subscribe_widget', 
		// Widget name will appear in UI
		__('Category Email Subscribe Form', 'stemail'), 
		// Widget description
		array( 'description' => __( 'An Widget that display Subscriber Form', 'stemail' ), ) 
		);
	}

	
	/**
	 * How to display the widget on the screen.
	 */
	function widget($args,$instance)
	{
		extract($args);
		
		$title=apply_filters('widget_title',$instance['title']);
		echo $args['before_widget'];

		if ( $title )
		{
			echo $before_title . $title . $after_title;
		}
		
		$title = apply_filters( 'widget_title', $instance['title'] );
		
		
		echo st_category_email_subscribe_form($instance);
		echo $args['after_widget'];

	}
	 
	 function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['prepend'] = strip_tags( $new_instance['prepend'] );
		$instance['showname'] = strip_tags( $new_instance['showname'] );
		$instance['nametxt'] = strip_tags($new_instance['nametxt']);
		$instance['nameholder'] = strip_tags($new_instance['nameholder']);
		$instance['emailtxt'] = strip_tags($new_instance['emailtxt']);
		$instance['emailholder'] = strip_tags($new_instance['emailholder']);
		$instance['showcategory'] = strip_tags($new_instance['showcategory']);
		$instance['categorytxt'] = strip_tags($new_instance['categorytxt']);
		$instance['submittxt'] = strip_tags($new_instance['submittxt']);
		$instance['jsthanks'] = strip_tags($new_instance['jsthanks']);
		$instance['thankyou'] = strip_tags($new_instance['thankyou']);
		return $instance;
	}
	
	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	 
	function form( $instance ) 
	{
		/* Set up some default widget settings. */
		$defaults = array( 	'prepend' => 'Subscribe to receive updates in email',
							'showname' => 'true',
							'nametxt' => 'Name:',
							'nameholder' => 'Name...',
							'emailtxt' => 'Email:',
							'emailholder' => 'Email Address...',
							'showcategory' => 'true',
							'categorytxt' => 'Category:',
							'submittxt' => 'Submit',
							'jsthanks' => 'false',
							'thankyou' => 'Thank you for subscribing to our mailing list');
		$instance = wp_parse_args( $instance, $defaults );
		
	?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'prepend' ); ?>"><?php _e('Prepend:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'prepend' ); ?>" name="<?php echo $this->get_field_name( 'prepend' ); ?>" value="<?php echo $instance['prepend']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'showname' ); ?>"><?php _e('Show Name Field:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id('showname'); ?>" name="<?php echo $this->get_field_name('showname'); ?>" type="checkbox" value="true" <?php if ($instance['showname']=="1") {echo "checked='checked'";} ?> />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'nametxt' ); ?>"><?php _e('Name Field Label:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'nametxt' ); ?>" name="<?php echo $this->get_field_name( 'nametxt' ); ?>" value="<?php echo $instance['nametxt']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'nameholder' ); ?>"><?php _e('Name Field Default Value:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'nameholder' ); ?>" name="<?php echo $this->get_field_name( 'nameholder' ); ?>" value="<?php echo $instance['nameholder']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'emailtxt' ); ?>"><?php _e('Email Field Label:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'emailtxt' ); ?>" name="<?php echo $this->get_field_name( 'emailtxt' ); ?>" value="<?php echo $instance['emailtxt']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'emailholder' ); ?>"><?php _e('Email Field Default Value:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'emailholder' ); ?>" name="<?php echo $this->get_field_name( 'emailholder' ); ?>" value="<?php echo $instance['emailholder']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'showcategory' ); ?>"><?php _e('Show Category Field:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id('showcategory'); ?>" name="<?php echo $this->get_field_name('showcategory'); ?>" type="checkbox" value="true" <?php if ($instance['showcategory']=="1") {echo "checked='checked'";} ?> />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'categorytxt' ); ?>"><?php _e('Category Field Label:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'categorytxt' ); ?>" name="<?php echo $this->get_field_name( 'categorytxt' ); ?>" value="<?php echo $instance['categorytxt']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'submittxt' ); ?>"><?php _e('Submit Button Label:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'submittxt' ); ?>" name="<?php echo $this->get_field_name( 'submittxt' ); ?>" value="<?php echo $instance['submittxt']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'jsthanks' ); ?>"><?php _e('Show JavaScript Thanks:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id('jsthanks'); ?>" name="<?php echo $this->get_field_name('jsthanks'); ?>" type="checkbox" value="true" <?php if ($instance['jsthanks ']=="1") {echo "checked='checked'";} ?> />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'thankyou' ); ?>"><?php _e('Thank You Text', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'thankyou' ); ?>" name="<?php echo $this->get_field_name( 'thankyou' ); ?>" value="<?php echo $instance['thankyou']; ?>" style="width:100%;" />
		</p>

	<?php
	}
 }
 
 
 /**
 * Register our widget.
 * 'st_category_email_subscribe_load_widget' is the widget class used below.
 */
function st_category_email_subscribe_load_widget(){
	register_widget('st_category_email_subscribe_widget'); 
}

if (!class_exists('WP_EX_PAGE_ON_THE_FLY')){
    /**
    * WP_EX_PAGE_ON_THE_FLY
    * @author Ohad Raz
    * @since 0.1
    * Class to create pages "On the FLY"
    * Usage: 
    *   $args = array(
    *       'slug' => 'fake_slug',
    *       'post_title' => 'Fake Page Title',
    *       'post content' => 'This is the fake page content'
    *   );
    *   new WP_EX_PAGE_ON_THE_FLY($args);
    */
    class WP_EX_PAGE_ON_THE_FLY
    {

        public $slug ='';
        public $args = array();
        /**
         * __construct
         * @param array $arg post to create on the fly
         * @author Ohad Raz 
         * 
         */
        function __construct($args){
            add_filter('the_posts',array($this,'fly_page'));
            $this->args = $args;
            $this->slug = $args['slug'];
        }

        /**
         * fly_page 
         * the Money function that catches the request and returns the page as if it was retrieved from the database
         * @param  array $posts 
         * @return array 
         * @author Ohad Raz
         */
        public function fly_page($posts){
            global $wp,$wp_query;
            $page_slug = $this->slug;

            //check if user is requesting our fake page
            if(count($posts) == 0 && (strtolower($wp->request) == $page_slug || $wp->query_vars['page_id'] == $page_slug)){

                //create a fake post
                $post = new stdClass;
                $post->post_author = 1;
                $post->post_name = $page_slug;
                $post->guid = get_bloginfo('wpurl' . '/' . $page_slug);
                $post->post_title = 'page title';
                //put your custom content here
                $post->post_content = "Fake Content";
                //just needs to be a number - negatives are fine
                $post->ID = -42;
                $post->post_status = 'static';
                $post->comment_status = 'closed';
                $post->ping_status = 'closed';
                $post->comment_count = 0;
                //dates may need to be overwritten if you have a "recent posts" widget or similar - set to whatever you want
                $post->post_date = current_time('mysql');
                $post->post_date_gmt = current_time('mysql',1);

                $post = (object) array_merge((array) $post, (array) $this->args);
                $posts = NULL;
                $posts[] = $post;

                $wp_query->is_page = true;
                $wp_query->is_singular = true;
                $wp_query->is_home = false;
                $wp_query->is_archive = false;
                $wp_query->is_category = false;
                unset($wp_query->query["error"]);
                $wp_query->query_vars["error"]="";
                $wp_query->is_404 = false;
            }

            return $posts;
        }
    }//end class
}//end if

?>