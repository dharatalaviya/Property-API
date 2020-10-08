<?php 
/** 
 * Plugin Name: Peak Realty API 
 * Description: Sync Properties
 */

require_once( plugin_dir_path( __FILE__ )  . 'mlsgrid.php' );
require_once plugin_dir_path( __FILE__ ) . 'download-image.php';

function update_gallery_images( $post_id, $url, $attachment_data = array() ) {
  $download_remote_image = new KM_Download_Remote_Image( $url, $attachment_data );
  echo $attachment_id         = $download_remote_image->download();
  echo '<br/>';
  if ( ! $attachment_id ) {
    return false; 
  }
  add_post_meta($post_id, 'REAL_HOMES_property_images', $attachment_id);
}

function Generate_Featured_Gallery( $image_url, $post_id  ){
	//echo $old_image_url = get_the_post_thumbnail_url($post_id);
	$upload_dir = wp_upload_dir();
    $image_data = file_get_contents(escapefile_url($image_url));
   	$image_name = basename( escapefile_url($image_url) );
    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
	$filename         = basename( $unique_file_name ); // Create image file name
    if(wp_mkdir_p($upload_dir['path']))
  
       $file = $upload_dir['path'] . '/' . $filename;
    else
      $file = $upload_dir['basedir'] . '/' . $filename;
    file_put_contents($file, $image_data);

    $wp_filetype = wp_check_filetype($filename, null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    echo "a_id".$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
    add_post_meta($post_id, 'REAL_HOMES_property_images', $attach_id);   

    return $attach_id;
}
function Generate_Featured_Image( $image_url, $post_id  ){
	//echo $old_image_url = get_the_post_thumbnail_url($post_id);
	$upload_dir = wp_upload_dir();
    $image_data = file_get_contents(escapefile_url($image_url));
   	$image_name = basename( escapefile_url($image_url) );
    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
	$filename         = basename( $unique_file_name ); // Create image file name
    if(wp_mkdir_p($upload_dir['path']))
  
       $file = $upload_dir['path'] . '/' . $filename;
    else
      $file = $upload_dir['basedir'] . '/' . $filename;
    file_put_contents($file, $image_data);

    $wp_filetype = wp_check_filetype($filename, null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
    $res2= set_post_thumbnail( $post_id, $attach_id );
    update_post_meta( $post_id, '_thumbnail_id', $attach_id );
   

    return $attach_id;
}

function api_result( $url ){
	$token = 'ODQ3NjY%3d-2tBRv1NcYtM%3d';
	$properties_request = wp_remote_get( $url.'&apiToken='.$token);
	$body = wp_remote_retrieve_body( $properties_request );
	$properties = json_decode($body);
	return $properties;
}
function curl_url($url){
	$token = 'ODQ3NjY%3d-2tBRv1NcYtM%3d';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL,$url."&apiToken=".$token);
	$result=curl_exec($ch);
	curl_close($ch);
	return json_decode($result);
}
function rentcafe_properties(){

	$url = 'https://api.rentcafe.com/rentcafeapi.aspx?&requestType=searchJSON';
	$properties = api_result( $url );
// 	echo '<pre>';
// 	print_r($properties);
// 	echo '</pre>';
// 	echo count((array)$properties);
// 	die;
	$i = 0;
	if(!empty($properties)){
	    global $wpdb;
	    $args = array( 
				'post_type' => 'property',
				'posts_per_page' => -1,
	 			'post_status' => array('publish'),
	 			'meta_query' => array(
			        array(
			            'key'     => 'api_name',
			            'value'   => 'rentcafe',
			            'compare' => '=',
			        ),
			    ),
	 		);
		$query = new WP_Query( $args);
		$properties = $query->posts;
		echo '<pre>';
		print_r($properties);
		echo '</pre>';
		foreach ($properties as $p) {
		
		//	$check_title = get_page_by_title($p->PropertyName, OBJECT, 'property');
			 $property_id = $p->Propertyid;
			
			$results = $wpdb->get_row( "select post_id from $wpdb->postmeta where meta_value = '".$property_id."' and meta_key='REAL_HOMES_property_id'", ARRAY_A );
			
			if(!isset($results['post_id']) && $results['post_id'] == '') {
				$properties_details = api_result( 'https://api.rentcafe.com/rentcafeapi.aspx?requestType=property&type=propertyData&propertyId='.$property_id )[0];
				$amenities = api_result( 'https://api.rentcafe.com/rentcafeapi.aspx?requestType=property&type=Amenities&propertyId='.$property_id );
				$petpolicy = api_result('http://api.rentcafe.com/rentcafeapi.aspx?requestType=property&type=PetPolicy&propertyId='.$property_id );
				if ($p->maxrent > 0) 
						$post_status = 'publish';
				else
					$post_status = 'draft';	
				$my_post = array( 
					'post_title'    => wp_strip_all_tags( $p->PropertyName ),
				  	'post_status'   => $post_status,
				  	'post_type'	  => 'property',
				  	'post_author'	  => 	2,	
				  	'post_content'  => wp_strip_all_tags($properties_details->description)
				);
				$maxbeds = 0;
				$minbeds = 0;
				$minbath = 0;
				$maxbath = 0;
				$minsize = 0;
				$maxsize = 0;
				$pid = wp_insert_post( $my_post );
				echo $pid .'----insert<br/>';
				$term = $p->City;
				$pterm = term_exists( $term, 'property-city');
				wp_insert_term( $term, 'property-city', array('slug' => $term) );
				wp_set_post_terms( $pid, $pterm, 'property-city', true );
				$for_rent = array(45);	
				wp_set_post_terms( $pid, $for_rent, 'property-status', true );
				wp_set_object_terms( $pid, array(286), 'property-type', true );
				$url = parse_url($p->SiteUrl);
					$host = $url['scheme'].'://'.$url['host'].'/';
				if($url['scheme'] == '' && $url['host'] == ''){
					$host = 'https://cdngeneral.rentcafe.com/';
				}
			echo	$images = api_result('http://api.rentcafe.com/rentcafeapi.aspx?requestType=images&type=propertyImages&propertyId='.$property_id );
				if(!empty($images) ){
					foreach ($images as $image) {
						if (km_remote_image_file_exists($image->ImageURL)) {
			        		Generate_Featured_Image( $image->ImageURL, $pid);
			        		break;
			        	}
					}
		        }
				if($p->availability == 0){
					$apartments = curl_url('https://api.rentcafe.com/rentcafeapi.aspx?requestType=apartmentavailability&PropertyId='.$p->Propertyid);
		           	if(!empty($apartments)){
		           		$floors=array();
		           		foreach($apartments as $ap){
		           			$floor_images = $ap->UnitImageURLs;	
				            $floors[] = array(
				            	'inspiry_floor_plan_name' => "Unit ".$ap->ApartmentName,
						        'inspiry_floor_plan_price' => $ap->MaximumRent,
						        'inspiry_floor_plan_price_postfix' => '',
						        'inspiry_floor_plan_size' => $ap->SQFT,
						        'inspiry_floor_plan_size_postfix' => 'Sqft.',
						        'inspiry_floor_plan_bedrooms' => $ap->Beds,
						        'inspiry_floor_plan_bathrooms' => $ap->Baths,		
						        'inspiry_floor_availble_date' => $ap->AvailableDate,
						        'inspiry_floor_apply_online_url' => $ap->ApplyOnlineURL,
						        'inspiry_floor_plan_image' => $floor_images[0],
						    );
						    if($ap->MaximumRent > $maxprice)
						        $maxprice = $ap->MaximumRent;
						    if($ap->MaximumRent < $minprice || $minprice == 0)
						        $minprice = $ap->MaximumRent;	
						    if($ap->Beds > $maxbeds)
						        $maxbeds = $ap->Beds;
						    if($ap->Beds < $minbeds || $minbeds == 0 && ($minbeds >= 0))
						       	$minbeds = $ap->Beds;
						    if($ap->Baths > $maxbath)
						        $maxbath = $ap->Baths;
						    if($ap->Baths < $minbath || $minbath == 0 && ($minbath >= 0))
						       	$minbath = $ap->Baths;
						    if($ap->SQFT > $maxsize)
						        $maxsize = $ap->SQFT;
						    if($ap->SQFT < $minsize || $minsize == 0 && ($minsize >= 0))
						        $minsize = $ap->SQFT;    	
		           		}
		           	}
		           	update_post_meta($pid, 'inspiry_floor_plans', $floors);
		    	}
					if($minprice > 0)
						if($minprice == $maxprice)
							$price = $maxprice;
						else	 
							$price = $minprice . " - ".$maxprice ; 
					else
					   	$price = $p->maxrent;
					if($minprice > 0)
						if($minprice == $maxprice) $price = $maxprice;
						else $price = $minprice . " - ".$maxprice ; 
					else $price = $maxprice;
					if($minbeds > 0) 
						if($minbeds == $maxbeds) $beds = $maxbeds;
						else $beds = $minbeds . " - ".$maxbeds ; 
					else $beds = $p->maxbed;
					if($minbath > 0) 
						if($minbath == $maxbath) $bath = $maxbath;
						else $bath = $minbath . " - ".$maxbath ; 
					else $bath = $p->maxbath;  
					if($minsize > 0) 
						if($minsize == $maxsize) $size = $maxsize;
						else $size = $minsize." - ".$maxsize ; 
					else $size = $p->MaxArea;    
				    update_post_meta($pid, 'REAL_HOMES_property_price', $price);
		    		update_post_meta($pid, 'REAL_HOMES_property_bedrooms', $beds);
		    		update_post_meta($pid, 'REAL_HOMES_property_bathrooms', $bath);
					update_post_meta($pid, 'REAL_HOMES_property_size', $size);
					add_post_meta($pid, 'inspiry_floor_plans', $floors);
					add_post_meta($pid, 'REAL_HOMES_property_id', $p->Propertyid);
					add_post_meta($pid, 'REAL_HOMES_property_address', $p->Address);
					add_post_meta($pid, 'REAL_HOMES_property_location', $p->dLatitude.','.$p->dLongitude.'16');
					//add_post_meta($pid, 'REAL_HOMES_property_bedrooms', $p->maxbed);
					//add_post_meta($pid, 'REAL_HOMES_property_bathrooms', $p->maxbath);
					//add_post_meta($pid, 'REAL_HOMES_property_price', $p->maxrent);	
					//add_post_meta($pid, 'REAL_HOMES_property_size', $p->MaxArea);
					add_post_meta($pid, 'REAL_HOMES_featured', $p->FeaturedProperty);
					add_post_meta($pid, 'REAL_HOMES_property_size_postfix', 'Sq Ft');
					add_post_meta($pid, 'REAL_HOMES_add_in_slider', 'no');
					add_post_meta($pid, 'REAL_HOMES_agent_display_option', 'agent_info');
					add_post_meta($pid,'REAL_HOMES_property_map', 0);
					add_post_meta($pid, 'REAL_HOMES_agents', '21179');	
					$dd = array_filter( explode("~", $p->Amenity), 'strlen' );
					add_post_meta($pid,'REAL_HOMES_listofficename', 'Peak Realty' );
					$uploads = wp_upload_dir();
					add_post_meta($pid,'REAL_HOMES_mls_source', 'Peak Realty') ;
					add_post_meta($pid, 'api_name', 'rentcafe');
					$amenities = implode(", ", $dd);
					$pp = array('Amenities' => $amenities);
					$pets = array();
					foreach ($petpolicy as $pet) {
					if($pet->PetType > 0){
						if($pet->PetType == 2){
							$pets['Pet Types Allowed'] = 'Dogs OK';
						}
						elseif($pet->PetType == 1){
							$pets['Pet Types Allowed'] = 'Cats OK';
						}		
					}
					if($pet->dFee > 0){
						$pets['One Time Pet Fee'] = "$".floatval($pet->dFee);
					}
					if($pet->dRent > 0){
						$pets['Monthly Pet Fee'] = $pet->dRent;
					}
					if($pet->dWeight > 0){
						$pets['Max Pet Weight'] = floatval($pet->dWeight) . ' Pounds';
					}
					if($pet->dFeeMax > 0){
						$pets['Max Fee'] = $pet->dFeeMax;
					}
					if($pet->dFeeMin > 0){
						$pets['Minimum Fee'] = $pet->dFeeMin;
					}
					if($pet->bPetCare > 0){
						$pets['Pet Care'] = $pet->bPetCare;
					}
				}
				$additional_details = array_merge($pp, $pets);
				add_post_meta($pid, 'REAL_HOMES_additional_details', $additional_details);
			}elseif($results['post_id'] > 0){
				
				if($results['post_id'] > 0){
					$pid = $results['post_id'];
				}
				
				if ($p->maxrent > 0) 
					$post_status = 'publish';
				else
					$post_status = 'draft';	
				wp_update_post(array(
		        	'ID'    =>  $pid,
		        	'post_status'   =>  $post_status
		        ));
		    //    echo "update ----".$p->Propertyid."*******" .$pid."-----".$p->maxrent."-----". $post_status."<br/>";		           	
			 echo "update ----".$p->Propertyid."*******" .$pid."-----"."<br/>";
			 
		       	if($p->maxrent > 0){
					if($p->availability == 0){
						$apartments = curl_url('https://api.rentcafe.com/rentcafeapi.aspx?requestType=apartmentavailability&PropertyId='.$p->Propertyid);
						$maxprice = 0;
						$minprice = 0;
						$maxbeds = 0;
						$minbeds = 0;
						$minbath = 0;
						$maxbath = 0;
						$minsize = 0;
						$maxsize = 0;
		           		if(!empty($apartments)){
		           			$floors=array();
		           			foreach($apartments as $ap){
		           				$floor_images = $ap->UnitImageURLs;	
				            	$floors[] = array(
				            		'inspiry_floor_plan_name' => "Unit ".$ap->ApartmentName,
						            'inspiry_floor_plan_price' => $ap->MaximumRent,
						            'inspiry_floor_plan_price_postfix' => '',
						            'inspiry_floor_plan_size' => $ap->SQFT,
						            'inspiry_floor_plan_size_postfix' => 'Sqft.',
						            'inspiry_floor_plan_bedrooms' => $ap->Beds,
						            'inspiry_floor_plan_bathrooms' => $ap->Baths,		
						            'inspiry_floor_availble_date' => $ap->AvailableDate,
						            'inspiry_floor_apply_online_url' => $ap->ApplyOnlineURL,
						            'inspiry_floor_plan_image' => $floor_images[0],
						        );
						        if($ap->MaximumRent > $maxprice)
						        	$maxprice = $ap->MaximumRent;
						        if($ap->MaximumRent < $minprice || $minprice == 0 &&( $minprice >=0 ))
						        	$minprice = $ap->MaximumRent;	
						        if($ap->Beds > $maxbeds)
						        	$maxbeds = $ap->Beds;
						        if($ap->Beds < $minbeds || $minbeds == 0 && ($minbeds >= 0))
						        	$minbeds = $ap->Beds;
						        if($ap->Baths > $maxbath)
						        	$maxbath = $ap->Baths;
						        if($ap->Baths < $minbath || $minbath == 0 && ($minbath >= 0))
						        	$minbath = $ap->Baths;
						        if($ap->SQFT > $maxsize)
						        	$maxsize = $ap->SQFT;
						        if($ap->SQFT < $minsize || $minsize == 0 && ($minsize >= 0))
						        	$minsize = $ap->SQFT;
		           			}
		           		}
		           		update_post_meta($pid, 'inspiry_floor_plans', $floors);
		    		}
		    	
		        	if($minprice > 0)
						if($minprice == $maxprice) $price = $maxprice;
						else $price = $minprice . " - ".$maxprice ; 
					else $price = $maxprice;
					if($minbeds > 0) 
						if($minbeds == $maxbeds) $beds = $maxbeds;
						else $beds = $minbeds . " - ".$maxbeds ; 
					else $beds = $p->maxbed;
					if($minbath > 0) 
						if($minbath == $maxbath) $bath = $maxbath;
						else $bath = $minbath . " - ".$maxbath ; 
					else $bath = $p->maxbath;  
					if($minsize > 0) 
						if($minsize == $maxsize) $size = $maxsize;
						else $size = $minsize." - ".$maxsize ; 
					else $size = $p->MaxArea;    
				    update_post_meta($pid, 'REAL_HOMES_property_price', $price);
		    		update_post_meta($pid, 'REAL_HOMES_property_bedrooms', $beds);
		    		update_post_meta($pid, 'REAL_HOMES_property_bathrooms', $bath);
					update_post_meta($pid, 'REAL_HOMES_property_size', $size);		
	    			if(has_post_thumbnail($pid)){
	    			}else{
	    			 
	    				$images = api_result('http://api.rentcafe.com/rentcafeapi.aspx?requestType=images&type=propertyImages&propertyId='.$p->Propertyid );
						if(!empty($images) ){
						     Generate_Featured_Image( $image[0]->ImageURL, $pid);
						}
	    			}
	    			 echo '<br/>'; 
	    		}// if max rent
			}// Results post id else if
		} // foreach
	echo	$i++;
	die;
	
	} // if properties
	echo $i;
	die('Done');
}
function km_remote_image_file_exists( $url ) {
 $response = wp_remote_head( $url );

 return 200 === wp_remote_retrieve_response_code( $response );
}
//add_action("admin_init","rentcafe_properties");
add_shortcode('sync_properties', 'rentcafe_properties');
function rencafe_floorplan_update(){
	global $wpdb;
	$url = 'https://api.rentcafe.com/rentcafeapi.aspx?&requestType=searchJSON&maxRent>0';
	$properties = api_result( $url );
	$i = 0;
	if(!empty($properties)){
		foreach ($properties as $p) {
			if($p->maxrent > 0 ){
				$check_title = get_page_by_title($p->PropertyName, OBJECT, 'property');
				$property_id = $p->Propertyid;
				$results = $wpdb->get_row( "select post_id from $wpdb->postmeta where meta_value = '".$property_id."' and meta_key='REAL_HOMES_property_id'", ARRAY_A );
				if($results['post_id'] > 0){
					$pid =$results['post_id'];
		       		
					if($p->availability == 0){
						echo "update ----" .$pid."-----".$p->maxrent."-----". $post_status;
						$i++;
		           		$apartments = curl_url('https://api.rentcafe.com/rentcafeapi.aspx?requestType=apartmentavailability&PropertyId='.$p->Propertyid);
		           		if(!empty($apartments)){
		           			$floors=array();
		           			foreach($apartments as $ap){
		           				$floor_images = $ap->UnitImageURLs;	
				            	$floors[] = array(
				            		'inspiry_floor_plan_name' => "Unit ".$ap->ApartmentName,
						            'inspiry_floor_plan_price' => $ap->MaximumRent,
						            'inspiry_floor_plan_price_postfix' => '',
						            'inspiry_floor_plan_size' => $ap->SQFT,
						            'inspiry_floor_plan_size_postfix' => 'Sqft.',
						            'inspiry_floor_plan_bedrooms' => $ap->Beds,
						            'inspiry_floor_plan_bathrooms' => $ap->Baths,		
						            'inspiry_floor_availble_date' => $ap->AvailableDate,
						            'inspiry_floor_apply_online_url' => $ap->ApplyOnlineURL,
						            'inspiry_floor_plan_image' => $floor_images[0],
						        );	
		           			}
		           		}

         				update_post_meta($pid, 'inspiry_floor_plans', $floors);
		    		}		
				}/// Results post id else if
			}
		} // foreach
	} // if properties
	echo $i;
	die('Done');
}

function get_lat_long($address){
    
    $address = urlencode($address);
    $apikey = 'AIzaSyCvZiRYPZiKPjAL7LKwdRsULDw6z-fX6VY';
   
    $json = file_get_contents("https://maps.google.com/maps/api/geocode/json?address=$address&key=$apikey");
    $json = json_decode($json);
   
    $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
    $long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};

    return $lat.','.$long.",14";
}



function remove_default_img($sizes) {
     unset( $sizes['large']); 
    unset( $sizes['thumbnail']);
    return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'remove_default_img');
function escapefile_url($url){
  $parts = parse_url($url);
  $path_parts = array_map('rawurldecode', explode('/', $parts['path']));

  return
    $parts['scheme'] . '://' .
    $parts['host'] .
    implode('/', array_map('rawurlencode', $path_parts))
  ;
}