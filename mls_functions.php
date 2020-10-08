<?php 

function mls_api_result($url){
	//$token = '1fbb7e1a869720e3d4f124d25f4ff5e84e2988d9';
	$properties_request = wp_remote_get( $url, array(
	    'headers' => array(
	        'Authorization' => 'Bearer ' . ACCESS_TOKEN
	    ),
	) );
	$body = wp_remote_retrieve_body( $properties_request );

	$properties_details = json_decode($body);
	$properties = $properties_details->value;
	return $properties;
} 
function mls_media_api_result($listing_id){
	//$token = '1fbb7e1a869720e3d4f124d25f4ff5e84e2988d9';

	$properties_request_media = wp_remote_get( 'https://api.mlsgrid.com/Media?$filter=ResourceRecordID%20eq%20\''.$listing_id.'\'', array(
				'headers' => array(
					'Authorization' => 'Bearer ' . ACCESS_TOKEN
				),
			) );
	$body_media = wp_remote_retrieve_body( $properties_request_media );
	$properties_details_media = json_decode($body_media);
	$properties_media = $properties_details_media->value;

	return $properties_media;
} 