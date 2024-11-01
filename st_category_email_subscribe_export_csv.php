<?php
// This includes gives us all the WordPress functionality

//$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
//require_once( $parse_uri[0] . 'wp-load.php' );
	if (isset($_REQUEST['export_csv'])) {
        global $wpdb;
        global $table_suffix;
		
		$table_suffix = "st_category_email";
		$table_name = $wpdb->prefix . $table_suffix;
        
        // Use the WordPress database object to run the query and get
        // the results as an associative array
        $qry = "SELECT st_id AS Id,st_name AS Name,st_email AS Email,st_category AS Category FROM $table_name";
        
        // Check if any records were returned from the database
        $result = $wpdb->get_results($qry, ARRAY_A);
        
        if ($wpdb->num_rows > 0) 
		{
            // Make a DateTime object and get a time stamp for the filename
            $date = new DateTime();
            $ts = $date->format("Y-m-d-G-i-s");
            
            // A name with a time stamp, to avoid duplicate filenames
            $filename = "subscribers-$ts.csv";
            
            // Tells the browser to expect a CSV file and bring up the
            // save dialog in the browser
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename='.$filename);
            
            // This opens up the output buffer as a "file"
            $fp = fopen('php://output', 'w');
            
            // Get the first record
            $hrow = $result[0];

            // Extracts the keys of the first record and writes them
            // to the output buffer in CSV format
            fputcsv($fp, array_keys($hrow));
            
            // Then, write every record to the output buffer in CSV format            
            foreach ($result as $data) {
				$st_list_category = "";
				$st_categories = explode(",",$data['Category']);
				foreach($st_categories as $st_category){
					$st_list_category .= get_cat_name($st_category).",";
				}
				rtrim($st_list_category,",");				
				$data['Category'] = $st_list_category;
				fputcsv($fp, $data);
				
            }
            
            // Close the output buffer (Like you would a file)
            fclose($fp);
			die();
        }
		else
		{
			header('Location: ' . $_SERVER['HTTP_REFERER']);
		}
	}
?>