<?php

global $st_email_table_suffix;
$st_email_table_suffix = "st_category_email";

function st_category_email_admin_menu() {
    add_menu_page('St Category Email Subscribe', 'St Category Email Subscribe', 'manage_options', 'st_category_email_subscribe', 'st_category_email_subscribe_settings_page', plugins_url('st-category-email-subscribe/images/icon.png'));
	add_submenu_page('st_category_email_subscribe', 'Settings', 'Settings', 'manage_options', 'st_category_email_subscribe', 'st_category_email_subscribe_settings_page');
	add_submenu_page('st_category_email_subscribe', 'Subscribers', 'Subscribers', 'manage_options', 'st_category_email_subscriber', 'st_category_email_subscribe_subscribers_page');
}

add_action('admin_menu', 'st_category_email_admin_menu');

function st_category_email_subscribe_check_input($data){
    $data = trim($data);
    $data = stripslashes($data);
    
    return $data;
}

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class Subscribers_Table extends WP_List_Table{
	/* Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items(){
		$enable_categories = get_option( 'st_category_email_enable_categories' );
		
		$st_email = "";
		if(isset($_REQUEST['st_email'])){
			$st_email = $_REQUEST['st_email'];
		}
		$columns = $this->get_columns();
        $hidden = $this->get_hidden_columns($enable_categories);
        $sortable = $this->get_sortable_columns();
        $data = $this->table_data($st_email);					
		usort( $data, array( &$this, 'sort_data' ) );

		$perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
		
		$this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );
		
		$data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
				
        $this->_column_headers = array($columns, $hidden, $sortable);				
        $this->items = $data;				
    }
	/**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'id'          	=> 'ID',
			'st_name'  		=> 'Name',
            'st_email'  	=> 'Email',
			'st_category'  	=> 'Categories',
            'actions'      	=> 'Action'
        );
		
		return $columns;
    }
	/**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns($enable_categories)
    {
		if($enable_categories==1){
        return array('id','st_category');
		}
		else{
			return array('id');
		}
    }
	/**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('st_name' 		=> array('st_name', true),
					 'st_email' 	=> array('st_email', true),
					);
    }
	/**
     * Get the table data
     *
     * @return Array
     */
    private function table_data($st_email){		if (isset($_REQUEST['filter'])) {		$st_email = $_REQUEST['email'];			}else{			$st_email = "";		}	
		$this_page = $_SERVER['PHP_SELF']."?page=st_category_email_subscriber";
		
		global $wpdb;	
		global $st_email_table_suffix;	
			$st_email_table_suffix="st_category_email";
			$st_email_table_category_suffix="terms";		
		$st_email_table = $wpdb->prefix . $st_email_table_suffix;
		$st_category_table = $wpdb->prefix . $st_email_table_category_suffix;

        $data = array();
		$where = "WHERE 1=1 ";		if($st_email != NULL){			$where .= " AND st_email = '$st_email'";		}
		$sql = "SELECT * FROM $st_email_table $where;";				
		//echo $sql;
		$st_subscribers = $wpdb->get_results($sql);
		
		foreach ( $st_subscribers as $subscriber ) {
			$st_list_category= "";
			if($subscriber->st_category == 0){
				$st_category = "All Categories";
			}else{
				
				$st_categories = explode(",",$subscriber->st_category);
				foreach($st_categories as $st_category){
					$st_list_category .= get_cat_name($st_category)." ,";
				}
				rtrim($st_list_category," ,");
				
			}
			$data[] = array(
                    'id'          => $subscriber->st_id,
					'st_name'	  => $subscriber->st_name,
                    'st_email'    => $subscriber->st_email,
					'st_category' => $st_list_category,
                    'actions' 	  => "<a href='#' st_id='".$subscriber->st_id."' st_name='".$subscriber->st_name."' st_email='".$subscriber->st_email."' st_category='".$subscriber->st_category."' class='edit_this_subscriber'>Edit</a> | <a href='".$this_page."&action=delete&delete_id=".$subscriber->st_id."'>Delete</a>"
               );
			}
        return $data;
    }
	// Used to display the value of the id column
	public function column_id($item){
		return $item['id'];
	}
	
	/**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name ){
        switch( $column_name ) {
            case 'id':
			case 'st_name':
            case 'st_email':
			case 'st_category':
            case 'actions':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }
	/**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b ){
        // Set defaults
        $orderby = 'st_email';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }

        $result = strnatcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}

function st_category_email_subscribe_settings_page() {

	$send_email = get_option( 'st_category_email_send_email' );
	$from_name = get_option( 'st_category_email_from_name' );
	$enable_categories = get_option( 'st_category_email_enable_categories' );
	
	if (isset($_POST['save_send_email'])) {
		$send_email = $_POST['send_email'];
		update_option( 'st_category_email_send_email', $send_email );
		$from_name = $_POST['from_name'];
		update_option( 'st_category_email_from_name', $from_name );
	}
	
	if(isset($_POST['save_categories_settings'])) {
		if(isset($_POST['enable_categories'])){
			update_option( 'st_category_email_enable_categories', 1 );
			$enable_categories = 1;
		}else{
			update_option( 'st_category_email_enable_categories', 0 );
			$enable_categories = 0;
		}
		
	}
	?>
		<div class="wrap">  
			<h2><?php _e('Category Email Subscribe Plugin', 'stemail')?></h2>
			<div class="postbox-container" style="width:70%;padding-right:25px;">
				<div class="metabox-holder">
					<div class="meta-box-sortables">
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Settings', 'stemail')?></span></h3>
							<div class="inside">
								<form id="st_settings" method="POST">
									<div>
										<label for="send_email"><?php _e('Send Email From (Email): ', 'stemail')?></label>
										<input class="regular-text" type="text" name="send_email" value="<?php echo $send_email; ?>"/>
									</div>
									<div>
										<label for="from_name"><?php _e('Send Email From (Name): ', 'stemail')?></label>
										<input class="regular-text" type="text" name="from_name" value="<?php echo $from_name; ?>"/>
									</div>	
									<input class="button-primary" type="submit" name="save_send_email" value="Save" />
								</form>
							</div>
						</div>
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Categories', 'stemail')?></span></h3>
							<div class="inside">
								<form id="st_categories_settings" method="POST">
									<div>
										<label for="enable_categories"><?php _e('Enable Categories Selection', 'stemail')?>
										<input class="regular-text" type="checkbox" name="enable_categories" <?php if($enable_categories == 1) {echo "checked";} ?> value="1"/></label>
									</div>
									<input class="button-primary" type="submit" name="save_categories_settings" value="Save" />
								</form>
							</div>
						</div>
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('How to Use', 'stemail')?></span></h3>
							<div class="inside">
							<strong><?php _e('1. Enter the Send Email from Email and Name', 'stemail')?></strong><br/>
							<?php _e('All emails will be sent from this name and email', 'stemail')?><br/>
							<strong><?php _e('2. Add Subscribers', 'stemail')?></strong><br/>
							<?php _e('Go to St Category Emai Subscribe > Subscribers to Manage Subscribers', 'stemail')?><br/>
							<?php _e('You can manually add a subscriber', 'stemail')?><br/>
							<?php _e('Or Import an entire list', 'stemail')?><br/>
							<?php _e('The emails will be sent as soon as a ', 'stemail')?><strong><?php _e('Post is Published', 'stemail')?></strong><br/>
							<?php _e('The email will be sent only to the subscribers registered for the category of Post', 'stemail')?><br/>
							<strong><?php _e('3. Add Subscribe Form', 'stemail')?></strong><br/>
							<?php _e('Place the subscribe form on your website using ', 'stemail')?><strong><?php _e('Widget : Category Email Subscribe Form', 'stemail')?></strong>
							<?php _e('Or Short code [st_category_subscribe_form]', 'stemail')?>
							</div>
						</div>
					</div>
				</div>
            </div>		
			<div class="postbox-container side" style="width:20%;">
				<div class="metabox-holder">
					<div class="meta-box-sortables">
						
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Show your Support', 'stemail')?></span></h3>
							<div class="inside">
								<p>
								<strong><?php _e('Want to help make this plugin even better? All donations are used to improve this plugin, so donate now!', 'stemail')?></strong>
								</p>
								<a href="http://sanskruti.net/wordpress-plugins/st-category-email-subscribe/"><?php _e('Donate', 'stemail')?></a>
							</div>
						</div>
						<div id="toc" class="postbox">
						<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Connect With Us ', 'stemail')?></span></h3>
							<div class="inside">
								<a class="facebook" href="https://www.facebook.com/sanskrutitech"></a>
								<a class="twitter" href="https://twitter.com/#!/sanskrutitech"></a>
								<a class="googleplus" href="https://plus.google.com/107541175744077337034/posts"></a>
								<a class="website" href="http://sanskruti.net/"></a>
								<a class="email" href="mailto:info@sanskruti.net"></a>
							</div>
						</div>
					</div>
				</div>
			</div>			
		</div>
	<?php
}
function st_email_get_abs_path_from_src_file($src_file){
	if(preg_match("/http/",$src_file))
	{
		$path = parse_url($src_file, PHP_URL_PATH);
		$abs_path = $_SERVER['DOCUMENT_ROOT'].$path;
		$abs_path = realpath($abs_path);
		if(empty($abs_path)){
			$wpurl = get_bloginfo('wpurl');
			$abs_path = str_replace($wpurl,ABSPATH,$src_file);
			$abs_path = realpath($abs_path);			
		}
	}
	else
	{
		$relative_path = $src_file;
		$abs_path = realpath($relative_path);
	}
	return $abs_path;
}
function st_email_read_dump($src_file,$st_email_table,$column_string="",$start_row=2){
	ini_set('auto_detect_line_endings', true);
	global $wpdb;
	$errorMsg = "";
	if(empty($src_file))
	{
            $errorMsg .= "<br />" . _e('Input file is not specified', 'stemail');
            return $errorMsg;
    }
	
	$file_path = st_email_get_abs_path_from_src_file($src_file);	
	
	$file_handle = fopen($file_path, "r");
	if ($file_handle === FALSE) {
		// File could not be opened...
		$errorMsg .= _e('Source file could not be opened!', 'stemail') . '<br />';
		$errorMsg .= _e('Error on fopen', 'stemail') .  "('$file_path')";	// Catch any fopen() problems.
		return $errorMsg;
	}
	
	$row = 1;
	while (!feof($file_handle) ) 
	{
		$line_of_text = fgetcsv($file_handle, 1024);
		if ($row < $start_row)
		{
			// Skip until we hit the row that we want to read from.
			$row++;
			continue;
		}
		$columns = count($line_of_text);
		
		if ($columns>1)
		{
	        	$query_vals = "'".esc_sql($line_of_text[0])."'";
	        	for($c=1;$c<$columns;$c++)
	        	{
					$line_of_text[$c] = utf8_encode($line_of_text[$c]);
					$line_of_text[$c] = addslashes($line_of_text[$c]);
	                $query_vals .= ",'".esc_sql($line_of_text[$c])."'";
					
	        	}
	        	        $query = "INSERT INTO $st_email_table ($column_string) VALUES ($query_vals)";
						
                        $results = $wpdb->query($query);
                        if(empty($results))
                        {
                            $errorMsg .= "<br />" . _e('Insert into the Database failed for the following Query: ', 'stemail') . " <br />";
                            $errorMsg .= $query;
                        }
	    }
		$row++;
	}
	fclose($file_handle);
	
	return $errorMsg;
}

function st_category_email_subscribe_subscribers_page() {
	global $wpdb;
	global $st_email_table_suffix;	
	$enable_categories = get_option( 'st_category_email_enable_categories' );
	
	$st_email_table = $wpdb->prefix . $st_email_table_suffix;
	$column_string = "st_id,st_name,st_email,st_category";
		
	if(isset($_REQUEST['UploadFile']))
	{
		
		$upload_dir = wp_upload_dir();
		$target_path =  $upload_dir['path'];
		 
		$tmp_name = $_FILES["csvfile"]["tmp_name"];
		$name = $_FILES["csvfile"]["name"];
				
		if(move_uploaded_file($tmp_name,"$target_path/$name"))
		{
			$file_name = $target_path . "/" . $name;
			
			$errorMsg = st_email_read_dump($file_name,$st_email_table,$column_string);
	
		
			if(empty($errorMsg))
			{
				echo '<div id="message" class="updated fade"><p><strong>';
				echo _e('File content has been successfully imported into the database!', 'stemail');
				echo '</strong></p></div>';
			}
			else
			{
				echo '<div id="message" class="error"><p><strong>';
				echo _e('Error occured while trying to import!', 'stemail') . "<br />";
				echo $errorMsg;
				echo '</strong></p></div>';
			}
		} 
		else
		{
			echo '<div id="message" class="error"><p><strong>';
			echo _e('There was an error uploading the file, please try again!', 'stemail') ;
			echo '</strong></p></div>';
		}		
	}
	global $wpdb;
	global $st_email_table_suffix;

	$st_email_table = $wpdb->prefix . $st_email_table_suffix;
	
	//Store the Data input if data is submitted
	if (isset($_REQUEST['Subscribe'])) { 
		
		$sub_name = st_category_email_subscribe_check_input($_REQUEST["sub_name"]);
		$sub_email = st_category_email_subscribe_check_input($_REQUEST["sub_email"]); 
		
		$st_category = st_category_email_subscribe_check_input($_REQUEST["st_category"]); 
		//Insert
		$rows_affected = $wpdb->insert( $st_email_table, array( 'st_name' => $sub_name, 'st_email' => $sub_email,'st_category' => $st_category));
		echo "<div id=\"message\" class=\"updated fade\"><p><strong>Subscriber Added Successfully!</strong></p></div>";
	}
	if (isset($_REQUEST['action'])) {
		if(isset($_REQUEST['delete_id']))
		{
			$st_id = $_REQUEST['delete_id'];
			
			$row_deleted = $wpdb->delete($st_email_table,array( 'st_id' => $st_id ));
			echo "<div id=\"message\" class=\"updated fade\"><p><strong>$row_deleted Emails(s) Deleted Successfully!</strong></p></div>";
		}
	}
	if (isset($_REQUEST['Save'])) {
		$edit_st_id =  $_REQUEST['edit_st_id'];
		$edit_st_name =  $_REQUEST['edit_st_name'];
		$edit_st_email =  $_REQUEST['edit_st_email'];
		$edit_st_category = "";
		if(isset($_REQUEST['edit_st_category'])){
			$edit_st_category =  $_REQUEST['edit_st_category'];
			$edit_st_category = implode(",",$edit_st_category);
		}

		$row_updated = $wpdb->update($st_email_table,array('st_name'=>$edit_st_name,'st_email'=>$edit_st_email,'st_category'=>$edit_st_category),array( 'st_id' => $edit_st_id ));
		if ($row_updated >= 1)
		{
			echo "<div id=\"message\" class=\"updated fade\"><p><strong>$row_updated Subscribers(s) Updated Successfully!</strong></p></div>";
		}else{
			echo "<div id=\"message\" class=\"error\"><p><strong>Could not Update Subscriber due to some error</strong></p></div>";
		}		
	}
	?>
		<div class="wrap">  
			<h2><?php _e('Category Email Subscribe Plugin','stemail')?></h2>
			<div class="postbox-container" style="width:70%;padding-right:25px;">
				<div class="metabox-holder">
					<div class="meta-box-sortables">
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Subscribers','stemail')?></span></h3>
							<div class="inside">							
							<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=st_category_email_subscriber"; ?>">								
							     Email: <input type="text" name="email" id="email" class="code" value="">
								 <input type="submit" class="button-primary" name="filter" value="Filter" />							</form>
								<a href="<?=$_SERVER['PHP_SELF']."?page=st_category_email_subscriber&export_csv=yes" ;?>" class="button-primary alignright">Export CSV</a>
								<?php
								    $enable_categories = get_option( 'st_category_email_enable_categories' );
									$Subscribers = new Subscribers_Table();
									$Subscribers->prepare_items($enable_categories);									$Subscribers->display();
								?>
							</div>
						</div>
					</div>
					<div class="meta-box-sortables" id="edit_subscriber">
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Edit Subscriber','stemail')?></span></h3>
							<div class="inside">
								<a name="edit_a_subscriber"></a>
								<form id="edit_a_subscriber" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']."?page=st_category_email_subscriber"; ?>" method="post">
									<input name="edit_st_id" id="edit_st_id" type="hidden" value=""/>
									<div>
										<label><?php _e('Name','stemail')?></label>
										<input name="edit_st_name" id="edit_st_name" class="regular-text" value=""/>
									</div>
									<div>
										<label><?php _e('Email','stemail')?></label>
										<input name="edit_st_email" id="edit_st_email" class="regular-text" value=""/>
									</div>
									<?php $enable_categories = get_option( 'st_category_email_enable_categories' );?>
									<?php if($enable_categories == 1) { ?>
									<div>
										<label for="edit_st_category"><?php _e('Category','stemail')?></label>
										<?php $select_cats = wp_dropdown_categories("name=edit_st_category[]&id=edit_st_category&echo=0&hide_empty=0&hierarchical=1")?>
										<?php $select_cats = str_replace( 'id=', 'multiple="multiple" id=', $select_cats ); ?>
										<?php echo $select_cats; ?>
									</div>
									<?php } ?>
									<div>
										<input type="submit" class="button-primary" name="Save"  value="Save" />
									</div>
								</form>
							</div>
						</div>
					</div>
					<div class="meta-box-sortables">
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Import Subscribers','stemail')?></span></h3>
							<div class="inside">
								<a name="import_csv"></a>
								<form id="upload" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']."?page=st_category_email_subscriber"; ?>" method="POST">
									<input type="hidden" name="file_upload" id="file_upload" value="true" />
									<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
									<strong><?php _e('Choose a CSV file to upload: ','stemail')?></strong><input name="csvfile" id="csvfile" type="file" size="25" />
									<input type="submit" class="button-primary" name="UploadFile" value="Upload File" />
								</form>
								<br/>
								<h4>Note : </h4>
								<span class="description"><strong><?php _e('The Format of CSV File must be as below :','stemail') ?></strong><br/>
									&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('The First line must be headers as it is ignored while uploading on database','stemail') ?><br/>
									&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('From the second line, the data should begin in following order :','stemail') ?><br/>
									&nbsp;&nbsp;&nbsp;&nbsp;<strong><?php _e('Name, Email, Category.','stemail') ?></strong><br/>
									&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Name : Name of Subscriber.','stemail') ?><br/>
									&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Email : Email ID .','stemail') ?><br/>
									&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Category : List of categories.','stemail') ?><br/>
									&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><?php _e('To add more than one category, separate the categories with comma (,) and keep them between two inverted commas ". e.g. "Category 1, Category 2" ','stemail') ?></strong><br/>
							</div>
						</div>
					</div>
					<div class="meta-box-sortables">
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Add a Subscriber','stemail')?></span></h3>
							<div class="inside">
								<a name="add_subscriber"></a>
								<form id="add_subscriber" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']."?page=st_category_email_subscriber"; ?>" method="post">
									<div>
										<label><?php _e('Name','stemail')?></label>
										<input name="sub_name" class="regular-text code" value=""/>
									</div>
									<div>
										<label><?php _e('Email','stemail')?></label>
										<input name="sub_email" class="regular-text code" value=""/>
									</div>
									<?php if ($enable_categories == 1){?>
										
									<div>
										<label for="st_category"><?php _e('Category','stemail')?></label>
										<?php $select_cats =  wp_dropdown_categories("name=st_category&id=st_category&show_option_all=All Categories&echo=0&hide_empty=0&hierarchical=1")?>
										<?php $select_cats = str_replace( 'id=', 'multiple="multiple" id=', $select_cats ); ?>
										<?php echo $select_cats; ?>
									</div>
									<?php } ?>
									
									<div>
										<input type="submit" class="button-primary" name="Subscribe"  value="Subscribe" />
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
            </div>				
			<div class="postbox-container side" style="width:20%;">
				<div class="metabox-holder">
					<div class="meta-box-sortables">
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Manage Subscribers', 'stemail')?></span></h3>
							<div class="inside">
								<a href="#import_csv"><?php _e('Import Subscribers from CSV', 'stemail')?></a><br/>
								<a href="#add_subscriber"><?php _e('Add a Subscriber', 'stemail')?></a>
							</div>
						</div>
						<div id="toc" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Show your Support', 'stemail')?></span></h3>
							<div class="inside">
								<p>
								<strong><?php _e('Want to help make this plugin even better? All donations are used to improve this plugin, so donate $20, $50 or $100 now!', 'stemail')?></strong>
								</p>
								<a target="_blank" href="http://sanskruti.net/wordpress-plugins/st-category-email-subscribe/"><?php _e('Donate', 'stemail')?></a>
							</div>
						</div>
						<div id="toc" class="postbox">
						<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle"><span><?php _e('Connect With Us ', 'stemail')?></span></h3>
							<div class="inside">
								<a class="facebook" href="https://www.facebook.com/sanskrutitech"></a>
								<a class="twitter" href="https://twitter.com/#!/sanskrutitech"></a>
								<a class="googleplus" href="https://plus.google.com/107541175744077337034/posts"></a>
								<a class="website" href="http://sanskruti.net/"></a>
								<a class="email" href="mailto:info@sanskruti.net"></a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php
}
?>
