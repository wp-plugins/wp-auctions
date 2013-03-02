<?php

function wpa_date ( $value ) {
   return date_i18n(get_option('date_format') .' '. get_option('time_format'), strtotime( $value ));
}

function wpa_cleancurrency ( $value ) {

  $price_fl_point=(preg_replace("/,/",".",$value));
  $price_c=floatval(preg_replace("/^[^0-9\.]/","",$price_fl_point));

  return $price_c;
}

// helper function for multi-dimensional implode
function wpa_implode_r ($glue, $pieces) {
 $out = "";
 foreach ($pieces as $piece)
  if (is_array ($piece)) $out .= wpa_implode_r ($glue, $piece);
  else                   $out .= $glue.$piece;
 return $out;
}

// helper function to calculate increment based on amount
// Gold version has custom increment too

function wpa_get_increment ($value) {

  $out = 0.01;

  if ($value >= 1000) {
     $out = 10;
   } elseif ($value >= 250) {
     $out = 5;
   } elseif ($value >= 50) {
     $out = 2;
   } elseif ($value >= 25) {
     $out = 1;
   } elseif ($value >= 10) {
     $out = 0.50;
   } elseif ($value >= 5) {
     $out = 0.25;
   } elseif ($value >= 1) {
     $out = 0.1;
   } elseif ($value >= 0.5) {
     $out = 0.05;
   }

 
 return $out;
}

// helper function to validate email address
function wpa_valid_email($address)
{
// check an email address is possibly valid
return eregi('^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$', $address);
}

function wpa_import_photo( $auctionid, $url ) {

	if( !class_exists( 'WP_Http' ) )
	  include_once( ABSPATH . WPINC. '/class-http.php' );

	$photo = new WP_Http();
	$photo = $photo->request( $url );
	if( $photo['response']['code'] != 200 ) {
	  echo "ERROR:" . $photo['response']['code'];
		return false;
  }

  $filetype = wp_check_filetype( $url, null );

	$attachment = wp_upload_bits( 'Auction' . $auctionid . ".". $filetype['ext'], null, $photo['body'], date("Y-m", strtotime( $photo['headers']['last-modified'] ) ) );
	if( !empty( $attachment['error'] ) ) {
	  echo "ERROR:" . $attachment['error'];
		return false;
  }

	$postinfo = array(
		'post_mime_type'	=> $filetype['type'],
		'post_title'		=> 'Auction' . $auctionid,
		'post_content'		=> '',
		'post_status'		=> 'inherit',
	);
	$filename = $attachment['file'];
	$attach_id = wp_insert_attachment( $postinfo, $filename  );
	
  echo "## Attachment: ".$attach_id." created ##";

	if( !function_exists( 'wp_generate_attachment_data' ) )
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
	wp_update_attachment_metadata( $attach_id,  $attach_data );
	return $attach_id;
}

// new resize function .. using WP's built in resizer
function wpa_resize ( $image, $size, $height = 0 ) { 

   // resize now done on upload. All we need to do is produce correct image URL

   if (is_numeric($image)) {

     switch ( $size ) {
        case 250:
        case 300:
           $class = "WPA_popup";
           break;
        case 150:
           $class = "WPA_page";
           break;
        case 125:
           $class = "WPA_widget";
           break;
        default:
           $class = "WPA_thumbnail";
     }

     $thumbnail = wp_get_attachment_image_src ( $image , $class );
     
     if (empty($thumbnail[0])) {
        $thumb = WPA_PLUGIN_STYLE . "/default-$size.png"; 
     } else {
        $thumb = $thumbnail[0];
     }
   } else {
      $thumb = "ERROR: Image not in media library";
   }
   
   return $thumb;

}


function wpa_log($message) {
   if (WP_DEBUG == true) {
      if (is_array($message) || is_object($message)) {
         error_log(print_r($message, true));
      } else {
         error_log($message);
      }
   }
}


?>