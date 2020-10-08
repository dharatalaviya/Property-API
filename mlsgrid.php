<?php

define('ACCESS_TOKEN', '1fbb7e1a869720e3d4f124d25f4ff5e84e2988d9');
require_once( plugin_dir_path( __FILE__ )  . 'mls_functions.php' );

function mlsgrid($url, $p_type, $resource_name){
	ignore_user_abort(true);
    set_time_limit(0);
    global $wpdb;
	$properties_request = wp_remote_get( $url, array(
	    'headers' => array(
	        'Authorization' => 'Bearer ' . ACCESS_TOKEN
	    ),
	) );
	$body = wp_remote_retrieve_body( $properties_request );
	$properties_details = json_decode($body);
	$properties = $properties_details->value;
	$logs = [];
// 	echo '<pre>';
// 	print_r($properties_details);
// 	echo '</pre>';
	$AllPostalCode = array(60601, 60602, 60603, 60604, 60605, 60607, 60609, 60610, 60611, 60612, 60613, 60614, 60615,  60616, 60625, 60630, 60653, 60657, 60654, 60606, 60608, 60661, 60618, 60634, 60641, 60660, 60619, 60637,  60640, 60642, 60645, 60647,60649, 60659,  60622);

	$i =0 ;
	if(!empty($properties))
	foreach ($properties as $p) {
		if($p->UnitNumber != '') $unitnumber = "#".$p->UnitNumber;
		if($p->StreetDirPrefix != '') $StreetDirPrefix = " ".$p->StreetDirPrefix;
		$title = $p->StreetNumber.$StreetDirPrefix." ". $p->StreetName." ".$unitnumber;
		
		$results = $wpdb->get_row( "select post_id from $wpdb->postmeta where meta_value = '".$p->ListingId."' and meta_key='REAL_HOMES_listingid'", ARRAY_A );
		if( $p_type == 'sale' && $p->ListPrice >= '200000' ) $price_status = true;
		elseif($p_type == 'rent' && $p->ClosePrice >= '1400') $price_status = true;
		else $price_status = false;
		echo $price_status.'-----------'.in_array($p->PostalCode, $AllPostalCode).'------'.$p->StandardStatus .'<br/>'; 
		if(empty($results) && trim($title)!= '' && $p->StandardStatus == 'Active' && $price_status == true && in_array($p->PostalCode, $AllPostalCode)) {
			$my_post = array(
				'post_title'    => wp_strip_all_tags( $title ),
				'post_status'   => 'publish',
				'post_type'	    => 'property',
				'post_author'	=> 	2,	
				'post_content'  => wp_strip_all_tags($p->PublicRemarks)
			);
			$pid = wp_insert_post( $my_post );
			echo "insert----".$i."----".$p->ListingId."-----".$pid."<br/>" ;
			//add_post_meta($pid, 'inspiry_floor_plans', $floors);
			add_post_meta($pid, 'REAL_HOMES_property_id', $p->ListingId);
			//add_post_meta($pid, '_thumbnail_id', $p->Propertyid);
			$address = $p->StreetNumber.' '.$p->StreetName.' '.$p->StreetSuffix.', '.$p->City.', '.$p->StateOrProvince.", ".$p->PostalCode;
			$latlong =  get_lat_long($address);
			add_post_meta($pid, 'REAL_HOMES_property_address', $address);
			add_post_meta($pid, 'REAL_HOMES_property_bedrooms', $p->BedroomsTotal);
			add_post_meta($pid, 'REAL_HOMES_property_location',$latlong);
			$bathroom = $p->BathroomsTotalInteger;
			$bathroom_exp = explode('.', $bathroom);
			$bathroom_string = '';
			if($bathroom_exp[0] != '')
				$bathroom_string .= $bathroom_exp[0];
			if(!empty($bathroom_exp[1] > 0))
				$bathroom_string .= " and ". $bathroom_exp[1]. " Half";
			if( $p_type == 'sale') $price = $p->ListPrice;
			else $price = $p->ClosePrice;
			add_post_meta($pid, 'REAL_HOMES_property_bathrooms', $bathroom_string);
			add_post_meta($pid, 'REAL_HOMES_property_price', $price);	
			add_post_meta($pid, 'REAL_HOMES_property_size', $p->LivingArea);
			add_post_meta($pid, 'REAL_HOMES_featured', $p->FeaturedProperty);
			add_post_meta($pid, 'REAL_HOMES_property_size_postfix', 'Sq Ft');
			add_post_meta($pid, 'REAL_HOMES_add_in_slider', 'no');
			add_post_meta($pid, 'REAL_HOMES_agent_display_option', 'agent_info');
			add_post_meta($pid, 'REAL_HOMES_agents', '21179');	
			add_post_meta($pid, 'REAL_HOMES_property_garage', $p->GarageSpaces);
			add_post_meta($pid, 'REAL_HOMES_property_year_built', $p->YearBuilt);
			add_post_meta($pid,'REAL_HOMES_property_map', 0);
			add_post_meta($pid,'REAL_HOMES_mls_source', strtoupper($p->OriginatingSystemName));
			$uploads = wp_upload_dir();
			$mls_image =  $uploads['baseurl'] .'/2019/11/mred_logo.png';
			add_post_meta($pid,'REAL_HOMES_mls_source_image', $mls_image);	
			add_post_meta($pid,'REAL_HOMES_listofficename', $p->ListOfficeName );
			add_post_meta($pid,'REAL_HOMES_listingid', $p->ListingId);
			add_post_meta($pid,'REAL_HOMES_mlsstatus', $p->MlsStatus);
			add_post_meta($pid,'REAL_HOMES_license_agreement', 'Based on information submitted to the MRED as of '.date("m/d/Y").' at '.date("H:i A ") .'. All data is obtained from various sources and has not been, and will not be, verified by broker or MRED. MRED supplied Open House information is subject to change without notice. All information should be independently reviewed and verified for accuracy. Properties may or may not be listed by the office/agent presenting the information.');
			$additionaldetal = array();
			update_post_meta($pid, 'api_name', 'mls');	
			if($p->AssociationFeeIncludes!=""){
				$fee = explode(",", $p->AssociationFeeIncludes);
				$AssociationFeeIncludes = implode(", ", $fee);
				$additionaldetal['Association Fee Includes'] = $AssociationFeeIncludes;
			}
			if($p->AssociationFee!=""){
				$AssociationFee = '$'.$p->AssociationFee;
				$additionaldetal['HOA Fees'] = $AssociationFee;
			}
			if($p->TaxAnnualAmount!=""){
				$TaxAnnual = '$'.$p->TaxAnnualAmount;
				$additionaldetal['Tax Annual Amount'] = $TaxAnnual;
			}
			if($p->ElementarySchoolDistrict!=""){
				$additionaldetal['Elementary School District'] = $p->ElementarySchoolDistrict;
			}
			if($p->HighSchoolDistrict!=""){
				$additionaldetal['High School District'] = $p->HighSchoolDistrict;	
			}
			if($p->PetsAllowed!=""){
				$additionaldetal['Pet Policy'] = $p->PetsAllowed;
			}
			add_post_meta($pid, 'REAL_HOMES_additional_details', $additionaldetal);
			$term = $p->MRD_LACITY;
			$pterm = term_exists( $term, 'property-city');
			wp_insert_term(
				$term, // the term 
				'property-city', // the taxonomy
				array(		
				  	'slug' => $term
				));
			wp_set_post_terms( $pid, $pterm, 'property-city', true );

			if($p_type == 'sale'){
				$for_rent = array(46);		
				
			}elseif($p_type == 'rent'){
				$for_rent = array(45);	
				
			}
			wp_set_post_terms( $pid, $for_rent, 'property-status', true );
			if($property_type){
				wp_set_post_terms( $pid, $property_type, 'property-type', true );
			}
		
// 			$properties_media = mls_media_api_result($p->ListingId);
// 			echo $properties_media[0]->MediaURL."<br/>";
// 			Generate_Featured_Image($properties_media[0]->MediaURL, $pid); 
			
// 			if(!empty($properties_media)){
// 				$count = 0;
// 				foreach($properties_media as $media){
// 				    if($count == 0){  $count++; }
// 				    else{
// 				    	//echo $media->MediaURL. "<br/>";
// 				    	Generate_Featured_Gallery( $media->MediaURL, get_the_id());
// 				    }
// 				}	 
// 			}
		    $logs[$i]['status'] = 'insert'; 
			$logs[$i]['propertyid'] = $pid;
			$logs[$i]['ListingId']  = $p->ListingId;
		}
		elseif($results['post_id'] > 0 && $price_status == true){
			$pid = $results['post_id'];
		//	echo "update----".$i."-----".$p->ListingId."----".$pid."<br/>" ;
			wp_update_post(array(
		        'ID'    =>  $pid,
		        'post_title'   => $title,
		        'post_content'  => wp_strip_all_tags($p->PublicRemarks),
		        'post_status'   =>  'publish'
		    ) );
		    if( $p_type == 'sale') $price = $p->ListPrice;
			else $price = $p->ClosePrice;
		    update_post_meta($pid, 'REAL_HOMES_property_price', $price);	
		    update_post_meta($pid,'REAL_HOMES_license_agreement', 'Based on information submitted to the MRED as of '.date("m/d/Y").' at '.date("H:i A ") .'. All data is obtained from various sources and has not been, and will not be, verified by broker or MRED. MRED supplied Open House information is subject to change without notice. All information should be independently reviewed and verified for accuracy. Properties may or may not be listed by the office/agent presenting the information.');
		    $logs[$i]['status']  = 'update'; 
			$logs[$i]['propertyid'] = $pid;
			$logs[$i]['ListingId']  = $p->ListingId;
		}
		$i++;
		
	} //Foreach
	//	echo $i;
	echo "Total Zipcode include properties: ".$i;
    $d = json_decode($body, true);
	//echo $d['@odata.nextLink'];
	$logs = serialize($logs);
	
	$wpdb->query( "insert INTO lc8_mlslogs (type, logs) VALUES ('Insert/Update', '{$logs}')");
   
}
function mlsgrid_delete($url){
	$properties_request = wp_remote_get( $url, array(
	    'headers' => array(
	        'Authorization' => 'Bearer ' . ACCESS_TOKEN
	    ),
	) );
	$i = 0;
	$log = [];
	$body = wp_remote_retrieve_body( $properties_request );
	$properties_details = json_decode($body);
	$properties = $properties_details->value;
	echo '<pre>';
	print_r($properties_details);
	echo '</pre>';
	if(!empty($properties)){
		global $wpdb;
    	foreach ($properties as $p) {
    	    $results = $wpdb->get_row( "select post_id from $wpdb->postmeta where meta_value = '".$p->ListingId."' and meta_key='REAL_HOMES_listingid'", ARRAY_A );
	        if($results['post_id'] > 0 ){
    			echo "delete-----".$results['post_id']."-----".$i."-----" .$p->ListingId. '<br/>';
    			$gallery_slider_type = get_post_meta($results['post_id'] , 'REAL_HOMES_gallery_slider_type', true );
    			$properties_images   = rwmb_meta( 'REAL_HOMES_property_images', 'type=plupload_image' . $size, $results['post_id'] );
    			foreach ( $properties_images as $prop_image_id => $prop_image_meta ) {
    				 wp_delete_attachment( $prop_image_id, true );
    			}
    		$logs[$i]['status']  = 'delete'; 
			$logs[$i]['propertyid'] =  $results['post_id'];
			$logs[$i]['ListingId']  = $p->ListingId;
    			do_action('before_delete_post',$results['post_id']);
    			wp_delete_post($results['post_id']);
    			$i++;
    		}
    	}
    	if(!empty($logs)){
    		$logs = serialize($logs);
	   	 $wpdb->query( "insert INTO lc8_mlslogs (type, logs) VALUES ('Delete', '{$logs}')");
    	}	
	}
}
function mslgrid_cron(){
	$time = date('H:i:s', time() - 10800);
    $date = date('Y-m-d');
    $datetime = trim($date."T".$time.".99Z"); 	
	$url = get_option('_mslgridPropertyResi');
    $count = get_option('_mslgridPropertyResiTotalCount');
	$url = 'https://api.mlsgrid.com/PropertyResi/?$filter=MlgCanView%20eq%20true%20and%20ModificationTimestamp%20gt%20'.$datetime.'%20and%20StandardStatus+eq+Enums.StandardStatus%27Active%27&$top=3000&$skip=0';
	mlsgrid($url, 'sale', 'PropertyResi');
    $url_delete = 'https://api.mlsgrid.com/PropertyResi/?$filter=MlgCanView%20eq%20false%20and%20ModificationTimestamp%20gt%20'.$datetime.'&$top=1000&$skip=0';
    mlsgrid_delete($url_delete);
    
}

function mslgrid_cron_rlse(){
// For Rent PropertyRlse
	$time = date('H:i:s', time() - 10800);
    $date = date('Y-m-d');
    $datetime = trim($date."T".$time.".99Z"); 	
	$url = 'https://api.mlsgrid.com/PropertyRlse/?$filter=MlgCanView%20eq%20true%20and%20ModificationTimestamp%20gt%20'.$datetime.'%20and%20StandardStatus+eq+Enums.StandardStatus%27Active%27&$top=3000&$skip=0';
	mlsgrid($url, 'rent', 'PropertyRlse');
	 $url_delete = 'https://api.mlsgrid.com/PropertyRlse/?$filter=MlgCanView%20eq%20false%20and%20ModificationTimestamp%20gt%20'.$datetime.'&$top=3000&$skip=0';
    mlsgrid_delete($url_delete);
	
}
function mslgrid_cron_rinc(){
// For Rent PropertyRlse
	$time = date('H:i:s', time() - 10800);
    $date = date('Y-m-d');
    $datetime = trim($date."T".$time.".99Z"); 	
	$url = 'https://api.mlsgrid.com/PropertyRinc/?$filter=MlgCanView%20eq%20true%20and%20ModificationTimestamp%20gt%20'.$datetime.'%20and%20StandardStatus+eq+Enums.StandardStatus%27Active%27&$top=3000&$skip=0';
		mlsgrid($url, 'sale','PropertyRinc');
	 $url_delete = 'https://api.mlsgrid.com/PropertyRinc/?$filter=MlgCanView%20eq%20false%20and%20ModificationTimestamp%20gt%20'.$datetime.'&$top=3000&$skip=0';
    mlsgrid_delete($url_delete);
    
}
register_activation_hook( __FILE__, 'properties_api_activation'  );


function properties_api_activation() {
	if (! wp_next_scheduled ( 'mslgrid_cron' )) {
		wp_schedule_event(time(), 'hourly', 'mslgrid_api_cron', array());
    } 
    if (! wp_next_scheduled ( 'mslgrid_cron_rlse' )) {
		wp_schedule_event(time(), 'hourly', 'mslgrid_cron_rlse', array());
    } 
     if (! wp_next_scheduled ( 'mslgrid_cron_rinc' )) {
		wp_schedule_event(time(), 'hourly', 'mslgrid_cron_rinc', array());
    } 
    if (! wp_next_scheduled ( 'rentcafe_properties' )) {
		wp_schedule_event(time(), 'hourly', 'rentcafe_api_cron', array());
    }  
    if (! wp_next_scheduled ( 'mls_images_gallery' )) {
		wp_schedule_event(time(), '30min', 'mls_images_gallery', array());
    }   
}
function properties_cron_schedules($schedules){
    if(!isset($schedules["30min"])){
        $schedules["30min"] = array(
            'interval' => 30*60,
            'display' => __('Once every 30 minutes'));
    }
    if(!isset($schedules["5min"])){
        $schedules["5min"] = array(
            'interval' => 5*60,
            'display' => __('Once every 5 minutes'));
    }
     if(!isset($schedules["3min"])){
        $schedules["3min"] = array(
            'interval' => 3*60,
            'display' => __('Once every 3 minutes'));
    }
    return $schedules;
}
add_filter('cron_schedules','properties_cron_schedules');
add_action( 'mslgrid_api_cron', 'mslgrid_cron', 10);
add_action( 'mslgrid_cron_rlse', 'mslgrid_cron_rlse', 10);
add_action( 'mslgrid_cron_rinc', 'mslgrid_cron_rinc', 10);
add_action( 'rentcafe_api_cron', 'rentcafe_properties', 10);
add_action( 'mls_images_gallery', 'mls_images_gallery', 10);

//add_shortcode('mlsgrid', 'mslgrid_cron');
add_shortcode('mlsgrid', 'mls_images_gallery');

function mls_images_gallery_test(){
	$url = 'https://api.mlsgrid.com/Media?$filter=MlgCanView%20eq%20true&$top=1000&$skip=6000';
    $properties_request = wp_remote_get( $url, array(
	    'headers' => array(
	        'Authorization' => 'Bearer ' . ACCESS_TOKEN
	    ),
	) );
	$body = wp_remote_retrieve_body( $properties_request );

	$properties_details = json_decode($body);
	$properties = $properties_details->value;
   
	if(!empty($properties)){
		foreach($properties as $media){
		    global $wpdb;
		   	$result = $wpdb->get_row( "select post_id from $wpdb->postmeta where meta_value = '".$media->ResourceRecordID."' and meta_key='REAL_HOMES_listingid'", ARRAY_A );
	        echo $result['post_id'].'----';
			if(isset($result) && $result['post_id'] > 0){
				echo $media->MediaURL."----";
				if(has_post_thumbnail()){
					echo $attach_id = Generate_Featured_Gallery( $media->MediaURL,  $result['post_id']);
					add_post_meta($attach_id, 'ResourceRecordKey', $media->ResourceRecordKey);
				}else{
					echo $attach_id =Generate_Featured_Image($media->MediaURL, $result['post_id']); 
					add_post_meta($attach_id, 'ResourceRecordKey', $media->ResourceRecordKey);
				}
				echo '<br/>';
			}
		}
	}
}
function mls_images_gallery(){
  echo $mlsimages =  get_option('mlsimages');
  $args = array( 
				'post_type' => 'property',
				'posts_per_page' => 2,
				'paged'	=> $mlsimages,
				'orderby' => 'date',
                'order'   => 'ASC',
	 			'meta_query' => array(
			        array(
			            'key'     => 'api_name',
			            'value'   => 'mls',
			            'compare' => '=',
			        ),
			    ),
	 		);
	$query = new WP_Query( $args);
		if($query -> have_posts()):
		$count = 0;
		while($query->have_posts()): $query-> the_post();
			echo "--".get_the_id().'----------';
			if(has_post_thumbnail($query)){
			  // echo  $thumbnail_id  = get_post_thumbnail_id();
			     continue;
			}else{
		
	    	echo $propertyid = get_post_meta(get_the_id(), 'REAL_HOMES_listingid', true);
	    	if($propertyid != ''){
	    		$properties_images   = rwmb_meta( 'REAL_HOMES_property_images', 'type=plupload_image&size=' . $size, get_the_id() );
					if(empty($properties_images) ){
					$properties_media = mls_media_api_result($propertyid);
				
					if(! empty($properties_media)){
					    $c = 0;
					  
					    foreach($properties_media as $media){
					    	if($c == 0)
					    	   	Generate_Featured_Image( $media->MediaURL, get_the_id()); 
					    	else
					    	   	Generate_Featured_Gallery( $media->MediaURL, get_the_id());
					    	$c++;   	
					    }
					    
					}
				}
			}
			    
			}
			//	echo '----'.$count++;
			    echo '<br/>';
		endwhile;
	endif;
	$mlsimages =  $mlsimages + 1;
	update_option('mlsimages', $mlsimages);
}

function mslgrid_test(){
 
    $time = date('H:i:s', time() - 3600)."<br/>";
    $date = date('Y-m-d'); echo '<br/>';
    echo $datetime = $date."T".$time."Z"; die;
	//$url = 'https://api.mlsgrid.com/PropertyRlse/?$filter=ListingId%20eq%20\'MRD10578521\'';
$url = 'https://api.mlsgrid.com/PropertyResi/?$filter=MlgCanView%20eq%20true%20and%20ModificationTimestamp%20gt%202020-07-30T23:59:59.99Z&$top=3000&$skip=0';
  //  $url = 'https://api.mlsgrid.com/PropertyResi?$filter=MlgCanView%20eq%20true%20and%20ModificationTimestamp%20gt%202020-07-29T23:59:59.99Z&$top=4000&$skip=0';
	$properties_request = wp_remote_get( $url, array(
	    'headers' => array(
	        'Authorization' => 'Bearer ' . ACCESS_TOKEN
	    ),
	) );
	$body = wp_remote_retrieve_body( $properties_request );

	$properties_details = json_decode($body);
	$properties = $properties_details->value;

	foreach ($properties as $p)
		$p->StandardStatus .'<br/>'; 
	die;	
	
	
}
function mls_images_trash_post(){
  
	$args = array( 
				'post_type' => 'property',
				'posts_per_page' => 1,
				'paged'	=> 1,
	 			'post_status' => array('trash'),
	 		);
	$query = new WP_Query( $args);
	
		if($query -> have_posts()):
		$count = 0;
		while($query->have_posts()): $query-> the_post();
			//echo get_the_id();
			echo get_the_id();
			if(has_post_thumbnail( $post_id ))
            {
              $attachment_id = get_post_thumbnail_id( $post_id );
              wp_delete_attachment($attachment_id, true);
            }
	    	$gallery_slider_type = get_post_meta( get_the_id() , 'REAL_HOMES_gallery_slider_type', true );
	    	$properties_images   = rwmb_meta( 'REAL_HOMES_property_images', 'type=plupload_image' . $size, get_the_id() );
			foreach ( $properties_images as $prop_image_id => $prop_image_meta ) {
				 wp_delete_attachment( $prop_image_id, true );
			}
		//	do_action('before_delete_post',get_the_id());
			wp_delete_post(get_the_id());
	    
				echo '----'.$count++;
			    echo '<br/>';
		endwhile;
	endif;

	echo $count;
}
add_shortcode('trashpost','mls_images_trash_post');

function mls_logs(){
	global $wpdb;
	$results = $wpdb->get_results( "select * from lc8_mlslogs ORDER BY ID DESC", ARRAY_A);
   
    if(!empty($results)){
		foreach ($results as $result) {
		  echo "<b>Id:".$result['id']."&nbsp; Date:".$result['date']."</b><br/>";
		  $logs = unserialize($result['logs']);
		  if(!empty($logs)){
    		      foreach($logs as $key => $value){
    		           echo "Status: ".$value['status']."&nbsp; &nbsp;   Propertyid: ".$value['propertyid']."&nbsp; &nbsp;  ListingId: ".$value['ListingId']."<br/>";
    		      }
		     }
		   echo '---------------------------------------------------------------<br/>';
		}
	}
}
add_shortcode('mls_logs','mls_logs');
//add_action('admin_init', 'mslgrid_cron');