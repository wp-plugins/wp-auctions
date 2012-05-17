<?php
/*
Plugin Name: WP_Auctions
Plugin URI: http://www.wpauctions.com/downloads
Description: WP Auctions allows you to host auctions on your own blog or website.
Version: 1.9
Author: Owen Cutajar & Hyder Jaffari
Author URI: http://www.wpauctions.com
*/

/* History:
   v 1.5   - New version of free plugin
   v1.6 - Added check/mailing address option
   v1.7 - Added "no auction" alternative
   v1.8 - Added custom currency option
	 v1.9 - Brought in line with WordPress 3.3
	     .1 - Bug fixes
*/

//error_reporting (E_ALL ^ E_NOTICE);

// cater for stand-alone calls
if (!function_exists('get_option'))
	require_once('../../../wp-config.php');
 
$wpa_version = "1.9 Lite";

// Consts
define('PLUGIN_EXTERNAL_PATH', '/wp-content/plugins/wp-auctions/');
define('PLUGIN_STYLE_PATH', 'wp-content/plugins/wp-auctions/styles/');
define('PLUGIN_NAME', 'wp_auctions.php');
define('JSCRIPT_NAME', 'wp_auctionsjs.php');
define('PLUGIN_PATH', 'wp-auctions/wp_auctions.php');

// ensure localisation support
if (function_exists('load_plugin_textdomain')) {
    $localedir = dirname(plugin_basename(__FILE__)).'/locales';
		load_plugin_textdomain('WPAuctions', '', $localedir );
}

define('BID_WIN', __('Congratulations, you are the highest bidder on this item.','WPAuctions') );
define('BID_LOSE', __("I'm sorry, but your Maximum Bid is below the current bid.",'WPAuctions') );

define('POPUP_SIZE', "&height=579&width=755&modal=true");

//---------------------------------------------------
//--------------AJAX CALLPOINTS----------------------
//---------------------------------------------------

if (strstr($_SERVER['PHP_SELF'],PLUGIN_EXTERNAL_PATH.PLUGIN_NAME) && isset($_GET['debug'])):
   echo "<h1>WP Auctions Remote Debug Screen</h1>";
   echo "Version Number: ".$wpa_version;
   echo "<p>";

   $options = get_option('wp_auctions');
   if ($options['remotedebug'] != "" ) {   
      phpinfo();
   } else {
      echo "Remote Debug disabled - you can turn this on in your Administration console";
   }
endif;


if (strstr($_SERVER['PHP_SELF'],PLUGIN_EXTERNAL_PATH.PLUGIN_NAME) && isset($_GET['postauction'])):

  // check security
  check_ajax_referer( "WPA-nonce" );

	// process posted values here
	$auction_id = $_POST['auction_id'];
	$bidder_name = htmlspecialchars(strip_tags(stripslashes($_POST['bidder_name'])), ENT_QUOTES);
	$bidder_email = strip_tags(stripslashes($_POST['bidder_email']));
	$bidder_url = htmlspecialchars(strip_tags(stripslashes($_POST['bidder_url'])), ENT_QUOTES);
	$max_bid = $_POST['max_bid'];

  $result = wpa_process_bid( $auction_id, $bidder_name, $bidder_email, $bidder_url, $max_bid );

    echo $result;
	exit;
endif;

if (strstr($_SERVER['PHP_SELF'],PLUGIN_EXTERNAL_PATH.PLUGIN_NAME) && isset($_GET['queryauction'])):

	global $wpdb;

	function fail($s) { header('HTTP/1.0 406 Not Acceptable'); die($s);}

  // check security
  check_ajax_referer( "WPA-nonce" );

	// process query string here
	$auction_id = $_POST['auction_ID'];

	// validate input
	if (!is_numeric($auction_id)) // ID not numeric
		fail(__('Invalid Auction ID specified','WPAuctions'));
		
    // confirm if auction has ended or not
    check_auction_end($auction_id);

  	// prepare result
  	$table_name = $wpdb->prefix . "wpa_auctions";
  	$strSQL = "SELECT id, name,description,current_price,date_create,date_end,start_price,image_url, '".current_time('mysql',"1")."' < date_end, winner, winning_price, 0 as x , extraimage1, '' as y,'' as z , 0.00 as 'next_bid' FROM $table_name WHERE id=".$auction_id;
  	$rows = $wpdb->get_row ($strSQL, ARRAY_N);

  	// send back result
    if (!($rows)) // no records found
       fail(__('Cannot locate auction','WPAuctions'));

    // pass image through resizer
    
    // first image should always exist 
    if ($rows[7] == "") $rows[7] = get_bloginfo('wpurl').PLUGIN_EXTERNAL_PATH."requisites/wp-popup-def.gif";
    $rows[7] = wpa_resize ($rows[7],250);
    
    // other images could be blank .. in which case, don't resize
    if ($rows[12] != "") $rows[12] = wpa_resize ($rows[12],250);

    
    // normalise dates
    $rows[4] = date('dS M Y h:i A',strtotime(get_date_from_gmt($rows[4])));
    $rows[5] = date('dS M Y h:i A',strtotime(get_date_from_gmt($rows[5])));

    // insert next increment if not starting price
    if ($rows[3] >= $rows[6]) {
       $rows[15] = number_format($rows[3] + wpa_get_increment($rows[3]), 2, '.', ',');
    } else {
       $rows[15] = $rows[6];
    }

	// prepare results   	
  //  $result_set = implode("|",$rows);
  $result_set = implode("|", $rows);  
        	
    echo $result_set;
	exit;
endif;

if (strstr($_SERVER['PHP_SELF'],PLUGIN_EXTERNAL_PATH.PLUGIN_NAME) && isset($_GET['querybids'])):

	global $wpdb;

	function fail($s) { header('HTTP/1.0 406 Not Acceptable'); die($s);}

  // check security
  check_ajax_referer( "WPA-nonce" );

	// process query string here
	$auction_id = $_POST['auction_ID'];

	// validate input
	if (!is_numeric($auction_id)) // ID not numeric
		fail(__('Invalid Auction ID specified','WPAuctions'));
		
	// prepare result
	$table_name = $wpdb->prefix . "wpa_bids";
	$strSQL = "SELECT bidder_name, bidder_url ,date, current_bid_price FROM $table_name WHERE auction_id=".$auction_id." ORDER BY current_bid_price DESC";
	$rows = $wpdb->get_results ($strSQL, ARRAY_N);

	// send back result
    if (!($rows)) // no records found
       $result_set="";
    else {
         foreach($rows as $i=>$row){
            $row[2] = date('dS M Y h:i A',strtotime(get_date_from_gmt($row[2]))); // convert dates to WP timezone
            
            // replace the row in the table
            $rows[$i]=$row;            
         }
       $result_set = wpa_implode_r("|",$rows);
    }
      	
    echo $result_set;
	exit;
endif;


if (strstr($_SERVER['PHP_SELF'],PLUGIN_EXTERNAL_PATH.PLUGIN_NAME) && isset($_GET['queryother'])):

	global $wpdb;

	function fail($s) { header('HTTP/1.0 406 Not Acceptable'); die($s);}

  // check security
  check_ajax_referer( "WPA-nonce" );

	// process query string here
	$auction_id = $_POST['auction_ID'];

	// validate input
	if (!is_numeric($auction_id)) // ID not numeric
		fail(__('Invalid Auction ID specified','WPAuctions'));
		
	// prepare result
	$table_name = $wpdb->prefix . "wpa_auctions";
	$strSQL = "SELECT id,name,image_url,current_price,start_price,0.00 as 'next_bid' FROM $table_name WHERE id <> ".$auction_id." AND '".current_time('mysql',"1")."' < date_end ORDER BY RAND() LIMIT 4";
	$rows = $wpdb->get_results ($strSQL, ARRAY_N);

      foreach($rows as $i=>$row){
        if ($row[2] == "") $row[2] = get_bloginfo('wpurl').PLUGIN_EXTERNAL_PATH."requisites/default.png";
        $row[2] = wpa_resize($row[2],50);

            // insert current price
           if ($row[3] >= $row[4]) {
              $row[5] = $row[3];
           } else {
              $row[5] = $row[4];
           }            

         // replace the row in the table
         $rows[$i]=$row;
      }


	// send back result
    if (!($rows)) // no records found
       $result_set="";
    else
       $result_set = wpa_implode_r("|",$rows);
      	
    echo $result_set;
	exit;
endif;

//---------------------------------------------------
//--------------RSS FEED-----------------------------
//---------------------------------------------------
if (strstr($_SERVER['PHP_SELF'],PLUGIN_EXTERNAL_PATH.PLUGIN_NAME) && isset($_GET['rss'])):
header("Content-Type:application/rss+xml");

	global $wpdb;
	global $wpa_version;

  $options = get_option('wp_auctions');
  $currencycode = $options['currencycode'];

	// prepare result
	$table_name = $wpdb->prefix . "wpa_auctions";
	$strSQL = "SELECT * FROM $table_name WHERE '".current_time('mysql',"1")."' < date_end ORDER BY ID desc LIMIT 15";
	$rows = $wpdb->get_results ($strSQL);

$now = date("D, d M Y H:i:s T");

$output = "<?xml version=\"1.0\"?>
            <rss version=\"2.0\">
                <channel>
                    <title>".get_option('blogname')." Auctions</title>
                    <link>".get_bloginfo('wpurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME."?rss.</link>
                    <description>Auction feed generated by wp_auctions (http://www.wpauctions.com) version ".$wpa_version."</description>
                    <language>en-us</language>
                    <pubDate>$now</pubDate>
                    <lastBuildDate>$now</lastBuildDate>
                    <docs>http://someurl.com</docs>
                    <managingEditor>".get_option('admin_email')."</managingEditor>
                    <webMaster>".get_option('admin_email')."</webMaster>
            ";
            
foreach ($rows as $line)
{
    $output .= "<item><title>".htmlentities($line->name)."</title>
                    <link>".get_bloginfo('wpurl')."?auction_to_show=".$line->id."</link>
                    <description><![CDATA[<img src='".wpa_resize($line->image_url,50)."' align='left'>".htmlentities(strip_tags($line->description))." - Closing: ".date('dS M Y',strtotime($line->date_end))." - Current Bid: ".$currencycode.number_format($line->current_price, 2, '.', ',')." -]]></description>
                </item>";
}
$output .= "</channel></rss>";

    echo $output;
	exit;
endif;

//---------------------------------------------------
//--------------HELPER FUNCTIONS---------------------
//---------------------------------------------------

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

if(!function_exists('file_put_contents')) {
    function file_put_contents($filename, $data, $file_append = false) {

      $fp = fopen($filename, (!$file_append ? 'w+' : 'a+'));
        if(!$fp) {
          trigger_error('file_put_contents cannot write in file.', E_USER_ERROR);
          return;
        }
      fputs($fp, $data);
      fclose($fp);
    }
  }
  
function wpa_resize ( $image, $size ) {
   $resizer = get_bloginfo('wpurl').PLUGIN_EXTERNAL_PATH.'wpa_resizer.php';
   
   $currentServer = get_bloginfo('wpurl');
      
   // make sure we have a local file
   if(ereg($currentServer,$image) != true) {
        // get us a local copy
        $finfo = pathinfo($image);
        list($filename) = explode('?',$finfo['basename']);
        $local_filepath = get_bloginfo('wpurl').PLUGIN_EXTERNAL_PATH.'files/'.$filename;

        // don't download a fresh copy if we got this less than 20 mins ago
        $download_image = true;
        if(file_exists($local_filepath)){
           if(filemtime($local_filepath) < strtotime('+20 minutes')) {
              $download_image = false;
           }
        }

       if($download_image == true) {
          $img = file_get_contents($image);
          
          // get physical path to file
          $realfile = dirname(__file__).'/files/'.$filename;
          
          file_put_contents($realfile,$img);
       }
       
      $image = $local_filepath;  
   }
     
   // following line works on PHP 5 only 
   //$relPath = parse_url($image, PHP_URL_PATH);
   
   $aPath = parse_url($image);
   $relPath = $aPath['path'];
   
   $final = $resizer.'?width='.$size.'&amp;height='.$size.'&amp;cropratio=1:1&amp;image='.$relPath;

   return $final;
}

//---------------------------------------------------
//--------------INTERNAL CODE------------------------
//---------------------------------------------------


function wpa_process_bid( $auction_id, $bidder_name, $bidder_email, $bidder_url, $max_bid ) {

	global $wpdb;

  //echo "<!-- in Process_Bid code -->";
  
  $result = "";
  $options = get_option('wp_auctions');
  $notify = $options['notify'];
  $title = $options['title'];
  $currencysymbol = $options['currencysymbol'];

	// validate input
	if (!is_numeric($auction_id)): // ID not numeric
		$result = __('Invalid Auction ID specified','WPAuctions');
    elseif (trim($bidder_name == '')):  // Bidder name not specified
        $result = __('Bidder name not supplied','WPAuctions');
    elseif (trim($bidder_email == '')):  // Bidder email not specified
        $result = __('Bidder email not supplied','WPAuctions');
    elseif (!wpa_valid_email($bidder_email)):  // Bidder email not specified
        $result = __('Please supply a valid email address','WPAuctions');
    elseif (!is_numeric($max_bid)):  // Bidder email not specified
        $result = __('Your bid value is invalid','WPAuctions');
    endif;
		
    if ($result == '') {
       // If we get this far it means that the input data is completely valid, so sanity check the data

       // Before we start .. confirm if auction has ended or not
       check_auction_end($auction_id);
	
       $table_name = $wpdb->prefix . "wpa_auctions";
	     $strSQL = "SELECT winner FROM $table_name WHERE id=".$auction_id;
	     $winner = $wpdb->get_var ($strSQL);          

       if ($winner != "") $result=__("Sorry, this auction is now closed",'WPAuctions');

       // Let's also check that the bid is in the right range for the (piggyback staticpage)
  		 $table_name = $wpdb->prefix . "wpa_auctions";
			 $strSQL = "SELECT current_price,start_price,staticpage FROM $table_name WHERE id=".$auction_id;
			 $rows = $wpdb->get_row ($strSQL);

       if ($rows->start_price > $max_bid) $result=__("Sorry, your bid must exceed the auction start price",'WPAuctions');
       if ($rows->current_price >= $max_bid) $result=__("Sorry, your bid must exceed the current bid price",'WPAuctions');
       if ($rows->current_price + wpa_get_increment($rows->current_price) > $max_bid) $result=__("Sorry, your bid must exceed",'WPAuctions')." ".$currencysymbol.number_format($rows->current_price + wpa_get_increment($rows->current_price), 2, '.', ',');;

       if ($result=='') {
		   // Step 1 - Retrieve current maximum bid on item
		   $table_name = $wpdb->prefix . "wpa_bids";
		   $strSQL = "SELECT * FROM $table_name WHERE auction_id=".$auction_id." ORDER BY current_bid_price DESC LIMIT 1";
		   $current = $wpdb->get_row ($strSQL);
	
		   $result = BID_WIN;
	
		   if (!($current)) {
			  $winner = "new";
	
			  // bid is the starting bid on the auction	
			 $table_name = $wpdb->prefix . "wpa_auctions";
			 $strSQL = "SELECT start_price FROM $table_name WHERE id=".$auction_id;
			 $thisbid = $wpdb->get_var ($strSQL);          
	
		   } else {
			  // let's compare maximum bids first
			  if ($max_bid > $current->max_bid_price) {
				 $winner = "new";
			   
				 // bid is next available one above current bidder's maximum bid
				 $thisbid = $current->max_bid_price + wpa_get_increment($current->max_bid_price);
	
				 // check we haven't exceeded the new bidder's maximum
				 if ($thisbid > ($max_bid + 0)) { $thisbid = $max_bid; }
	
				 //pull in auction details
				 $table_name = $wpdb->prefix . "wpa_auctions";
				 $strSQL = "SELECT id, name,description,current_price,date_create,date_end,start_price,image_url FROM $table_name WHERE id=".$auction_id;
				 $rows = $wpdb->get_row ($strSQL);
	
				 // Setup email fields.
				 //$headers = "From: " . get_option('blogname') . " <" . get_option('admin_email') . ">\r\n";  --> Windows fix
				 $headers = "From: " . get_option('admin_email') . "\r\n";
				 $to      = $current->bidder_email;
				 $subject = "[".$title."] You have been outbid on ".$rows->name;
				 $body   = "You have just been outbid on an auction on " . get_option('blogname') . "\n\n";
				 $body  .= "Unfortunately someone else is currently winning ".$rows->name." after placing a bid for ".$currencysymbol.$thisbid.". ";
				 $body  .= "You're still in time to win the auction, so click the link below and bid again.";

				 $body 	.= "\n\nLink: " . get_bloginfo('wpurl') ."?auction_to_show=".$auction_id;

				 $body 	.= "\n\n--------------------------------------------\n";
				
				 // Send the email.
				 mail($to, $subject, $body, $headers);
	
			  } else {
				 $winner = "old";
	
				 // increase bid to take it above new bid
				 $thisbid = $max_bid + wpa_get_increment($max_bid);
	
				 // check we haven't exceeded the old bidder's maximum
				 if ($thisbid > ($current->max_bid_price + 0)) { $thisbid = $current->max_bid_price; }
	
				 // if the old bidder wins, update the write variables with old bidder's details
				$bidder_name = $current->bidder_name;
				$bidder_email = $current->bidder_email;
				$bidder_url = $current->bidder_url;
				$max_bid = $current->max_bid_price;
	
				$result = BID_LOSE;
			  }
		   
           }
       }

		   if ($result == BID_WIN || $result == BID_LOSE ) {
			  // Update bid table with details on bid
			  $table_name = $wpdb->prefix . "wpa_bids";
			  $sql = "INSERT INTO ".$table_name." (id, auction_id, date, bidder_name ,bidder_email, bidder_url, current_bid_price, max_bid_price) VALUES (NULL, ".$auction_id.", '".current_time('mysql',"1")."', '".$bidder_name."', '".$bidder_email."', '".$bidder_url."', ".$thisbid.", ".$max_bid.");";
			  $wpdb->query($sql);
	
			  //Update auction table
			  $table_name = $wpdb->prefix . "wpa_auctions";
			  $sql = "UPDATE ".$table_name." SET current_price = ".$thisbid." WHERE id=".$auction_id;
			  $wpdb->query($sql);

         // notify site owner if notification requested
         if ($notify != '') {
				    // Setup email fields.
				    //$headers = "From: " . get_option('blogname') . " <" . get_option('admin_email') . ">\r\n"; --> Windows fix
				    $headers = "From: " . get_option('admin_email') . "\r\n";
				    $to      = $notify;
				    $subject = "[".$title."] New bid on ".$auction_id;
				    $body   = "New bid on your auction.";

   			    $body 	.= "\n\nLink: " . get_bloginfo('wpurl')."?auction_to_show=".$auction_id;
				    
				    $body 	.= "\n\n--------------------------------------------\n";
				
				    // Send the email.
				    mail($to, $subject, $body, $headers);
         }
		   }
        
    }
		   

   return $result;
}


function wp_auctions_uninstall () {

   // Cleanup routine. - Deactivated cleanup after to many complaints

   global $wpdb;

//   $table_name = $wpdb->prefix . "wpa_auctions";
//   $wpdb->query("DROP TABLE {$table_name}");

//   $table_name = $wpdb->prefix . "wpa_bids";
//   $wpdb->query("DROP TABLE {$table_name}");   

   wp_clear_scheduled_hook('wpa_daily_check');

}



function wp_auctions_install () {
   global $wpdb;

   $wpa_db_version = "1.3Lite";
   
   $installed_ver = get_option("wpa_db_version");
      
   if ($installed_ver != $wpa_db_version) {
      require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

      $table_name = $wpdb->prefix . "wpa_auctions";
     
      // Create Auctions Table
      
      $sql = "CREATE TABLE " . $table_name . " (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
	  date_create datetime NOT NULL,
	  date_end datetime NOT NULL,
	  name tinytext NOT NULL,
	  description text NOT NULL,
	  image_url tinytext,
	  start_price decimal(10,2) NOT NULL,
	  reserve_price decimal(10,2),
	  current_price decimal(10,2),
	  shipping_price decimal(10,2),
    shipping_to tinytext,
    shipping_from tinytext,
	  duration tinyint,
	  BIN_price decimal(10,2),
    winner tinytext,
    winning_price decimal(10,2),
    extraimage1 tinytext,
    extraimage2 tinytext,
    extraimage3 tinytext,
    staticpage tinytext,
    paymentmethod tinytext,
	  UNIQUE KEY id (id)
	);";

      dbDelta($sql);
     
      // Create Bids Table
   
	  $table_name = $wpdb->prefix . "wpa_bids";   
      
      $sql = "CREATE TABLE " . $table_name . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  auction_id mediumint NOT NULL,
	  date datetime NOT NULL,
	  bidder_name tinytext,
	  bidder_email tinytext,
	  bidder_url tinytext,
	  bidder_IP tinytext,
	  current_bid_price decimal(10,2) NOT NULL,
	  max_bid_price decimal(10,2),
	  UNIQUE KEY id (id)
	);";

      dbDelta($sql);
  
      update_option("wpa_db_version", $wpa_db_version);
      
      //set initial values if none exist
      $options = get_option('wp_auctions');
      if ( !is_array($options) ) {
         $options = array( 'title'=>'WP Auctions', 'currency'=>'2', 'style'=>'default', 'notify'=>'', 'paypal'=>'', 'currencysymbol'=>'$', 'currencycode'=>'USD');
         update_option('wp_auctions', $options);
      }
       
   }
   
   wp_schedule_event(time(), 'twicedaily', 'wpa_daily_check');
}

function close_expired_auctions() {
	// scheduled event to ensure auctions close properly
	
  global $wpdb;
 $table_name = $wpdb->prefix . "wpa_auctions";
 $strSQL = "SELECT id FROM $table_name WHERE winner IS NULL";
 $rows = $wpdb->get_results ($strSQL);
 
 foreach ($rows as $row) { 
    check_auction_end ($row->id);
 }
}


function check_auction_end($auction_id) {

   // make sure we have a numeric auction number
   $auction_id = $auction_id + 0;

   $options = get_option('wp_auctions');
   $paypal = $options['paypal'];
   $mailingaddress = $options['mailingaddress'];
   $bankdetails = $options['bankdetails'];
   $currencysymbol = $options['currencysymbol'];
   $currencycode = $options['currencycode'];
   $title = $options['title'];

   global $wpdb;

   // prepare result
   $table_name = $wpdb->prefix . "wpa_auctions";
   $strSQL = "SELECT id, '".current_time('mysql',"1")."' <= date_end, winner, 0, paymentmethod FROM $table_name WHERE id=".$auction_id;
   $rows = $wpdb->get_row ($strSQL, ARRAY_N);

   // pull out payment details
   $payment_method = $rows[3];  // in Lite -> 0 above returns NO COLUMN!!

   if ($rows[0] == $auction_id && $rows[1] == 0 && $rows[2] == '') {
      // auction has closed - update winner and price

      // prepare result
      $table_name = $wpdb->prefix . "wpa_bids";
	    $strSQL = "SELECT bidder_name, bidder_email, date, current_bid_price FROM $table_name WHERE auction_id=".$auction_id." ORDER BY current_bid_price DESC LIMIT 1";
	    $bidrows = $wpdb->get_row ($strSQL);

      if ($bidrows != '') {  // there is a bid
         //update database
         $table_name = $wpdb->prefix . "wpa_auctions";
         $strSQL = "UPDATE $table_name SET winner='$bidrows->bidder_name', winning_price = '$bidrows->current_bid_price' WHERE id=" . $auction_id;
         $wpdb->query($strSQL);
      
         // get details for mail
         $strSQL = "SELECT * FROM $table_name WHERE id=".$auction_id;
         $rows = $wpdb->get_row ($strSQL);

   	    // Setup email fields.
	       //$headers = "From: " . get_option('blogname') . " <" . get_option('admin_email') . ">\r\n";  -> Windows fix
	        $headers = "From: " . get_option('admin_email') . "\r\n";
	       $to      = $bidrows->bidder_email;
	       $subject = "[".$title."] Auction Closed: ".$auction_id;
	       $body   = "Congratulations! You have just won the following auction.";
	       $body 	.= "\n\nAuction: " . $rows->name . " for " . $currencysymbol . $rows->winning_price;
	       
         $body 	.= "\n\nLink: " . get_bloginfo('wpurl')."?auction_to_show=".$auction_id;
				  
	       switch ($payment_method) {
	          case "":
     	         $body 	.= "\n\nUndefined payment method";	          
     	         break;
	          case "paypal":
     	         $body 	.= "\n\nYou can pay for the auction by clicking on the link below:";
	             $body 	.= "\n\nhttps://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=".urlencode($paypal)."&item_name=".urlencode($rows->name)."&amount=".urlencode($rows->winning_price)."&shipping=0&no_shipping=0&no_note=1&currency_code=".$currencycode."&lc=GB&bn=PP%2dBuyNowBF&charset=UTF%2d8";
	             break;
	          case "bankdetails":
     	         $body 	.= "\n\nMy banking details are as follows:\n\n";
     	         $body  .= $bankdetails;
	             $body 	.= "\n\nPlease submit your payment for ".$currencysymbol.($rows->winning_price)." using the auction number (".$auction_id.") as a reference";
	             break;
	          case "mailingaddress":
     	         $body 	.= "\n\nMy postal address is as follows:\n\n";
     	         $body  .= $mailingaddress;
	             $body 	.= "\n\nPlease send me a cheque or postal order for ".$currencysymbol.($rows->winning_price)." quoting the auction number (".$auction_id.") as a reference";
	             break;	       
	       }
	  
         $body 	.= "\n\nShould you require any further assistance, please contact me at ".get_option('admin_email').".";
   
	       $body 	.= "\n\n--------------------------------------------\n";
		
	       // Send the email.
	       mail($to, $subject, $body, $headers);
     }

      // notify site owner if notification requested
	  if ($notify != '') {
		 // Setup email fields.
		 //$headers = "From: " . get_option('blogname') . " <" . get_option('admin_email') . ">\r\n";  -> Windows fix
		 $headers = "From: " . get_option('admin_email') . "\r\n";
		 $to      = $notify;
		 $subject = "[".$title."] Auction Closed: ".$auction_id;
		 $body   = "Your auction has closed.";

		 $body 	.= "\n\nLink: " . get_bloginfo('wpurl')."?auction_to_show=".$auction_id;

	       switch ($payment_method) {
	          case "paypal":
     	         $body 	.= "\n\nThe winner has been sent an email with a PayPal link to complete the transaction";
	             break;
	          case "bankdetails":
     	         $body 	.= "\n\nThe winner has been sent an email with your bank details and will be remitting payment shortly (reference: ".$auction_id.")";
	             break;
	          case "mailingaddress":
     	         $body 	.= "\n\nThe winner has been sent an email with your mailing address and requested to quote reference: ".$auction_id;
	             break;	       
	       }
		 $body 	.= "\n\n--------------------------------------------\n";
		
		 // Send the email.
		 mail($to, $subject, $body, $headers);
      }   
   }
}

function widget_wp_auctions_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_wp_auctions($args) {

		extract($args);

		echo $before_widget;
		docommon_wp_auctions();
		echo $after_widget;
	}
	
	function widget_wp_auctions_control() {
				
		echo 'Please configure the widget from the Auctions Configuration Screen';
	}

	register_sidebar_widget(array('WP Auctions', 'widgets'), 'widget_wp_auctions');
	register_widget_control(array('WP Auctions', 'widgets'), 'widget_wp_auctions_control', 300, 130);
;
}

function get_price($current_price,$start_price,$BIN_price,$currencysymbol,$sep) {

   $printstring = "undefined";
   if (($start_price<0.01) && ($BIN_price>0.01)) {
      $printstring = 'Buy It Now'.$sep.$currencysymbol.number_format($BIN_price, 2, '.', ',');
   } else {   
       if ($current_price>0.01) { // then show the current price
          $printstring = 'Going for'.$sep.$currencysymbol.number_format($current_price, 2, '.', ',');      
       } else { // then show the start price
             $printstring = 'Starting at'.$sep.$currencysymbol.number_format($start_price, 2, '.', ',');
       }
   }
   return $printstring;
}

function wp_auctions(){

   docommon_wp_auctions();
}

// Sidebar code goes here
function docommon_wp_auctions() {

   global $wpdb;

   $options = get_option('wp_auctions');
   $style = $options['style'];
   $currencysymbol = $options['currencysymbol'];
   $title = $options['title'];
   $feedback = $options['feedback'];
   $noauction = $options['noauction'];
   $otherauctions = $options['otherauctions'];
   $showrss = $options['showrss'];
   
   $chunks = explode('<!--more-->', $noauction);
   $chunkno = mt_rand(0, sizeof($chunks) - 1);
   $noauctiontext = $chunks[$chunkno];

   // select a random record
   $table_name = $wpdb->prefix . "wpa_auctions";

   $auction_id = $_GET["auction_to_show"];

   if(!is_numeric($auction_id)) {
      $cond = "'".current_time('mysql',"1")."' < date_end order by rand() limit 1";
   } else {
      $cond = "id=".$auction_id;
   }
   $strSQL = "SELECT id, image_url, name, description, date_end, duration, BIN_price, start_price, current_price, staticpage FROM ".$table_name." WHERE ".$cond;
   $row = $wpdb->get_row ($strSQL);

   // grab values we need
   $image_url = $row->image_url;
   $name = $row->name;
   $description = substr($row->description,0,75)."...";
   $end_date = get_date_from_gmt($row->date_end);
   $current_price = $row->current_price;
   $BIN_price = $row->BIN_price;
   $start_price = $row->start_price;
   $id = $row->id;

   // show default image if no image is specified
   if ($image_url == "") $image_url = get_bloginfo('wpurl').PLUGIN_EXTERNAL_PATH."requisites/default.png";

if ($list == "Yes") {

    echo "Something went wrong in display";

} else {

   // cater for no records returned
   if ($id == '') {
?>

<!--WP-Auction - Sidebar Presentation Section -->     
<div id="wp-container">
   
  <?php if ($noauctiontext != '') { ?>
  <div style="border: 1px solid #ccc; padding: 5px 2px; margin: 0px !important; background: none !important;">
      <?php echo $noauctiontext ?>
  </div>

  <?php } else { //noauctiontext is blank ?>
    <div id="wp-head"><?php echo $title ?></div>

    <div id="wp-body">
      <div id="wp-image"><img src="<?php echo wpa_resize($image_url,125) ?>" width="125" height="125" /></div>
      <div class="wp-heading"><?php _e("No auctions found",'WPAuctions'); ?></div>
      <div id="wp-desc"><?php _e("Sorry, we seem to have sold out of everything we had!",'WPAuctions'); ?></div>
    <div id="wp-other"></div>
    </div>
    <div id="wp-bidcontainer"></div>
  <!-- Main WP Container Ends -->  
  <?php } ?>
</div>
<!--WP-Auction - End -->     
<?php  
} else {

   // select "other" auctions
   $table_name = $wpdb->prefix . "wpa_auctions";

   $thelimit = "";
   if ($otherauctions != 'all' && $otherauctions > 0) {
      $thelimit = " limit ".$otherauctions;
   }

   $strSQL = "SELECT id, name, staticpage  FROM ".$table_name." WHERE '".current_time('mysql',"1")."' < date_end and id<>".$id." order by rand()".$thelimit;
   $rows = $wpdb->get_results ($strSQL);

   // prepare auction link
   $auctionlink = '<a href="'.get_bloginfo('wpurl').PLUGIN_EXTERNAL_PATH . 'auction.php?ID=' . $id .POPUP_SIZE.'" class="thickbox" title="Bid Now">';

?>
<!--WP-Auction - Sidebar Presentation Section -->     
  <!-- Main WP Container Starts -->
  <div id="wp-container">
    <div id="wp-head"><?php echo $title ?></div>

    <div id="wp-body">
      <div id="wp-image"><?php echo $auctionlink; ?><img src="<?php echo wpa_resize($image_url,125) ?>" width="125" height="125" /></a></div>
      <div class="wp-heading"><?php echo $name ?></div>

      <div id="wp-desc"><?php echo $description; ?><span class="wp-more"> - <?php echo $auctionlink; ?>more...</a></span> </div>

      <div id="wp-date"><?php _e('Ending','WPAuctions'); ?>: <?php echo date('dS M Y H:i:s',strtotime($end_date)) ?></div>

      <?php if ($feedback!=''): ?>      
         <div id="wp-date"><a href="<?php echo $feedback ?>" target="_blank"><?php _e("My eBay feedback",'WPAuctions'); ?></a></div>
      <?php endif ?>

      <div id="wp-other">

	<?php if (!empty($rows)): ?>      
        <div class="wp-heading"><?php _e("Other Auctions",'WPAuctions'); ?></div>
        <ul>
      <?php foreach ($rows as $row) {  
         echo "<li>";
         echo "- <a href='".get_bloginfo('wpurl')."?auction_to_show=".$row->id."'>";
         echo $row->name;
         echo "</a></li>";
      } ?>
        </ul>
   <?php endif; ?>

   <?php if ($showrss != "No") { ?>

        <div class="wp-rss"><a href="<?php echo get_bloginfo('wpurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME?>?rss"><img src="<?php echo get_bloginfo('wpurl').'/'.PLUGIN_STYLE_PATH.$style?>/rss.png" alt="Auctions RSS Feed" border="0" title="Grab My Auctions RSS Feed"/></a> <a href="<?php echo get_bloginfo('wpurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME?>?rss" title="Grab My Auctions RSS Feed" >Auctions RSS Feed</a></div>

   <?php } ?>

      </div>
    </div>
    <div id="wp-bidcontainer">
      <div id="wp-bidcontainerleft"><?php echo get_price($current_price,$start_price,$BIN_price,$currencysymbol,"<br>") ?></div>

      <div id="wp-bidcontainerright"><?php echo $auctionlink; ?><img src="<?php echo get_bloginfo('wpurl').'/'.PLUGIN_STYLE_PATH.$style?>/bidnow.png" alt="Bid Now" width="75" height="32" border="0" /></a> </div>

    </div>
    
  </div>
  <!-- Main WP Container Ends -->
<!--WP-Auction - End -->     
<?php

}

// hook to terminate auction if needed (not strictly correct, but more efficient if it's here)
check_auction_end($id);
  
}     

}


function wp_auctions_options() {

   // Note: Options for this plugin include a "Title" setting which is only used by the widget
   $options = get_option('wp_auctions');
	
   //set initial values if none exist
   if ( !is_array($options) ) {
      $options = array( 'title'=>'WP Auctions', 'otherauctions'=>'3', 'currency'=>'1', 'style'=>'default', 'notify'=>'', 'paypal'=>'', 'mailingaddress'=>'', 'bankdetails'=>'', 'currencysymbol'=>'$', 'currencycode'=>'USD','noauction'=>'','customcontact'=>'','customincrement'=>'');
   }

   if ( $_POST['wp_auctions-submit'] ) {

      // security check
      check_admin_referer( 'WPA-nonce');

      $options['currency'] = strip_tags(stripslashes($_POST['wpa-currency']));
      $options['title'] = strip_tags(stripslashes($_POST['wpa-title']));
      $options['notify'] = strip_tags(stripslashes($_POST['wpa-notify']));
      $options['paypal'] = strip_tags(stripslashes($_POST['wpa-paypal']));
      $options['mailingaddress'] = strip_tags(stripslashes($_POST['wpa-mailingaddress']));
      $options['bankdetails'] = strip_tags(stripslashes($_POST['wpa-bankdetails']));
      $options['feedback'] = strip_tags(stripslashes($_POST['wpa-feedback']));
      $options['otherauctions'] = strip_tags(stripslashes($_POST['wpa-otherauctions']));
      $options['noauction'] = stripslashes($_POST['wpa-noauction']); // don't strip tags
      $options['style'] = strip_tags(stripslashes($_POST['wpa-style']));
      $options['remotedebug'] = strip_tags(stripslashes($_POST['wpa-remotedebug']));
      $options['showrss'] = strip_tags(stripslashes($_POST['wpa-showrss']));
      
      // Currencies handled here
      if ($options['currency']==1) {
         $options['currencysymbol']="&pound;";
         $options['currencycode']="GBP";
      }

      if ($options['currency']==2) {
         $options['currencysymbol']="$";
         $options['currencycode']="USD";
      }
      
      if ($options['currency']==3) {
         $options['currencysymbol']="&#128;";
         $options['currencycode']="EUR";
      }

      if ($options['currency']==4) {
         $options['currencysymbol']="&yen;";
         $options['currencycode']="JPY";
      }

      if ($options['currency']==5) {
         $options['currencysymbol']="A$";
         $options['currencycode']="AUD";
      }

      if ($options['currency']==6) {
         $options['currencysymbol']="C$";
         $options['currencycode']="CAD";
      }

      if ($options['currency']==7) {
         $options['currencysymbol']="NZ$";
         $options['currencycode']="NZD";
      }

      if ($options['currency']==8) {
         $options['currencysymbol']="Fr";
         $options['currencycode']="CHF";
      }

      if ($options['currency']==9) {
         $options['currencysymbol']="S$";
         $options['currencycode']="SGD";
      }

      if ($options['currency']==99) {
         $options['currencysymbol']=strip_tags(stripslashes($_POST['wpa-currencysymbol']));;
         $options['currencycode']=strip_tags(stripslashes($_POST['wpa-currencycode']));;
      }

      update_option('wp_auctions', $options);
   }

   $currencysymbol = htmlspecialchars($options['currencysymbol'], ENT_QUOTES);
   $currencycode = htmlspecialchars($options['currencycode'], ENT_QUOTES);

   $currency = htmlspecialchars($options['currency'], ENT_QUOTES);
   $title = htmlspecialchars($options['title'], ENT_QUOTES);
   $notify = htmlspecialchars($options['notify'], ENT_QUOTES);
   $paypal = htmlspecialchars($options['paypal'], ENT_QUOTES);
   $mailingaddress = htmlspecialchars($options['mailingaddress'], ENT_QUOTES);
   $bankdetails = htmlspecialchars($options['bankdetails'], ENT_QUOTES);
   $feedback = htmlspecialchars($options['feedback'], ENT_QUOTES);
   $noauction = htmlspecialchars($options['noauction'], ENT_QUOTES);
   $otherauctions = htmlspecialchars($options['otherauctions'], ENT_QUOTES);
   $style = htmlspecialchars($options['style'], ENT_QUOTES);
   $remotedebug = htmlspecialchars($options['remotedebug'], ENT_QUOTES);
   $showrss = htmlspecialchars($options['showrss'], ENT_QUOTES);

  // Prepare style list based on styles in style folder
	$folder_array=array();
	$folder_count = 1;

	$path=ABSPATH.PLUGIN_STYLE_PATH;
	
	if ($handle = opendir($path)) { 
		while (false !== ($file = readdir($handle))) { 
			if ( !($file == "." || $file == "..") ) { 
				$folder_array[$folder_count]=$file;
				$folder_count++;
				}
		   } 
		} else {
		  echo "Cannot open: ".$path;
		}
	sort($folder_array); 
	
?>

<script type="text/javascript">
function CheckCurrencyOptions() {

   var chosen=document.getElementById("wpa-currency").value;
   var WPA_activetab=document.getElementById("wpa_activetab");

   if (chosen=="99") {
      WPA_activetab.style.display = "";
   } else {
      WPA_activetab.style.display = "none";   
   }

}
</script>

<div class="wrap"> 
  <form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF'].'?page='.PLUGIN_PATH; ?>">
  
  <?php wp_nonce_field('WPA-nonce'); ?>
  
  <h2 class="settings"><em><?php _e('General Settings') ?></em></h2> 

    <table width="100%" cellspacing="2" cellpadding="5" class="widefat"> 
      <tr valign="top" class="alternate"> 
        <th scope="row" class='row-title'><?php _e('Auction Title:') ?></th> 
        <td class='desc'><input name="wpa-title" type="text" id="wpa-title" value="<?php echo $title; ?>" size="40" />
        <br />
        <p><?php _e('Enter the header title for your auctions.') ?></p></td> 
      </tr> 
      <tr valign="top"> 
        <th scope="row" class='row-title'><?php _e('Currency:') ?></th> 
        <td class='desc'>
        <select id="wpa-currency" name="wpa-currency" onclick="CheckCurrencyOptions()">
                <option value="1" <?php if ($currency=='1') echo 'selected'; ?>>GBP</option>
                <option value="2" <?php if ($currency=='2') echo 'selected'; ?>>USD</option>
                <option value="3" <?php if ($currency=='3') echo 'selected'; ?>>EUR</option>
                <option value="4" <?php if ($currency=='4') echo 'selected'; ?>>JPY</option>
                <option value="5" <?php if ($currency=='5') echo 'selected'; ?>>AUD</option>
                <option value="6" <?php if ($currency=='6') echo 'selected'; ?>>CAD</option>
                <option value="7" <?php if ($currency=='7') echo 'selected'; ?>>NZD</option>
                <option value="8" <?php if ($currency=='8') echo 'selected'; ?>>CHF</option>
                <option value="9" <?php if ($currency=='9') echo 'selected'; ?>>SGD</option>
                <option value="99" <?php if ($currency=='99') echo 'selected'; ?>>Custom</option>
         </select>
        <br />
        <div id="wpa_activetab" style="display:<?php if ($currency!='99'){ echo "none"; }?>;">
          <div style="float:right; border: 2px solid red; color: #000; width: 300px;margin: -5px 10px 15px 0; padding: 5px;"><strong><u><p>Warning!</u></strong> If you use a custom currency, please remember that PayPal only supports a <a href="https://www.paypal.com/us/cgi-bin/webscr?cmd=p/sell/mc/mc_intro-outside">small subset of currencies</a>. If you use a currency outside this set, any PayPal payments will fail.</p> <p>You can still use Bank Payments and send your Address for cheques/money orders etc...</p></div>
          <div>Currency Code: <input name="wpa-currencycode" type="text" id="wpa-currencycode" value="<?php echo $currencycode; ?>" size="5" /><br/>
          Currency Symbol: <input name="wpa-currencysymbol" type="text" id="wpa-currencysymbol" value="<?php echo $currencysymbol; ?>" size="5" /></div>
        </div>
 
        <p><?php _e('Choose the currency you would like to run your auctions in.</p><!-- <p><a href="http://en.wikipedia.org/wiki/List_of_circulating_currencies" target="_blank">Click here for custom Currency Codes and Symbols</a>. -->') ?></p></td> 
      </tr> 
      <tr valign="top"> 
        <th scope="row" class='row-title'><?php _e('Bid Notification:') ?></th> 
        <td class='desc'><input name="wpa-notify" type="text" id="wpa-notify" value="<?php echo $notify; ?>" size="40" />
        <br />
        <p><?php _e('Enter your email address if you would like to be notified whenever a new bid is placed.') ?></p></td> 
      </tr> 
      <tr valign="top" class="alternate"> 
        <th scope="row" class='row-title'><?php _e('eBay Feedback:') ?></th> 
        <td class='desc'><input name="wpa-feedback" type="text" id="wpa-feedback" value="<?php echo $feedback; ?>" size="40" />
        <br />
        <p><?php _e('If you have lots of eBay feedback, we can add a link to show users your eBay history.') ?></p></td> 
      </tr> 
      <tr valign="top"> 
        <th scope="row" class='row-title' style="border-bottom: 0;"><?php _e('Other Auctions:') ?></th> 
        <td class='desc' style="border-bottom: 0;">
        <select id="wpa-otherauctions" name="wpa-otherauctions">
                <option value="1" <?php if ($otherauctions=='1') echo 'selected'; ?>>1</option>
                <option value="2" <?php if ($otherauctions=='2') echo 'selected'; ?>>2</option>
                <option value="3" <?php if ($otherauctions=='3') echo 'selected'; ?>>3</option>
                <option value="4" <?php if ($otherauctions=='4') echo 'selected'; ?>>4</option>
                <option value="5" <?php if ($otherauctions=='5') echo 'selected'; ?>>5</option>
                <option value="6" <?php if ($otherauctions=='6') echo 'selected'; ?>>6</option>
                <option value="7" <?php if ($otherauctions=='7') echo 'selected'; ?>>7</option>
                <option value="8" <?php if ($otherauctions=='8') echo 'selected'; ?>>8</option>
                <option value="9" <?php if ($otherauctions=='9') echo 'selected'; ?>>9</option>
                <option value="all" <?php if ($otherauctions=='all') echo 'selected'; ?>>All</option>
         </select>
        <br />
        <p><?php _e('How many other auctions would you like to display in the widget?') ?></p></td> 
      </tr> 
    </table>

  <h2 class="payment"><em><?php _e('Payment Settings <span>- Please supply at least one of the following</span>') ?></em></h2>

    <table width="100%" cellspacing="2" cellpadding="5" class="widefat"> 
      <tr valign="top" class="alternate"> 
        <th scope="row" class='row-title'><?php _e('PayPal account:') ?></th> 
        <td class='desc'><input name="wpa-paypal" type="text" id="wpa-paypal" value="<?php echo $paypal; ?>" size="40" />
        <br />
        <p><?php _e('Enter your PayPal email address (where you want auction winners to pay for their items)') ?></p></td> 
      </tr> 
      <tr valign="top"> 
        <th scope="row" class='row-title'><?php _e('Bank Details:') ?></th> 
        <td class='desc'>
        <textarea rows="5" cols="100" id="wpa-bankdetails" name="wpa-bankdetails"><?php echo $bankdetails; ?></textarea>
        <br />
        <p><?php _e('Enter your bank details (where you want auction winners to wire tranfers to you)') ?></p></td> 
      </tr> 
      <tr valign="top" class="alternate"> 
        <th scope="row" class='row-title' style="border-bottom: none;"><?php _e('Mailing Address:') ?></th> 
        <td class='desc' style="border-bottom: none;">
        <textarea rows="5" cols="100" id="wpa-mailingaddress" name="wpa-mailingaddress"><?php echo $mailingaddress; ?></textarea>
        <br />
        <p><?php _e('Enter your mailing address address (where you want auction winners to mail you cheques and money orders)') ?></p></td> 
      </tr> 

    </table>

  <h2 class="other-settings"><em><?php _e('Other Settings') ?></em></h2> 

    <table width="100%" cellspacing="2" cellpadding="5" class="widefat"> 
      <tr valign="top" class="alternate"> 
        <th scope="row" class='row-title'><?php _e('Style:') ?></th> 
        <td class='desc'>
           <select id="wpa-style" name="wpa-style">
            <?php                           
               foreach ($folder_array as $thisstyle) {
			      echo '<option value="'.$thisstyle.'"';
			      if ($thisstyle == $style) 
				     echo ' selected ';
			      echo '>'.$thisstyle;
			      echo '</option>';
		       } ?>
            </select>
        <br />
        <p><?php _e('Choose a graphical style for your widget.') ?></p></td> 
      </tr> 
      <tr valign="top"> 
        <th scope="row" class='row-title'><?php _e('"No Auction" Alternative:') ?></th> 
        <td class='desc'>
        <textarea rows="5" cols="100" id="wpa-noauction" name="wpa-noauction"><?php echo $noauction; ?></textarea>
        <br />
        <p><?php _e('Specify the HTML you would like to display if there are no active auctions. Leave blank for standard "No Auctions" display<br>To rotate ads, separate with &lt;!--more--&gt;') ?></p></td> 
      </tr>  
      <tr valign="top"> 
        <th scope="row" class='row-title'><?php _e('RSS Feed link:') ?></th> 
        <td class='desc'>
        <select id="wpa-showrss" name="wpa-showrss">
                <option value="No" <?php if ($showrss=='No') echo 'selected'; ?>>Hide RSS link</option>
                <option value="" <?php if ($showrss=='') echo 'selected'; ?>>Show RSS link</option>
         </select>
        <br />
        <p><?php _e('Do you want to publish a link to your auction RSS feed. This can let people know when you publish new auctions') ?></p></td> 
      </tr> 
      <tr valign="top"> 
        <th scope="row" class='row-title' style="border-bottom: none;"><?php _e('Allow Remote Debug:') ?></th> 
        <td class='desc' style="border-bottom: none;">
        <select id="wpa-remotedebug" name="wpa-remotedebug">
                <option value="" <?php if ($remotedebug=='') echo 'selected'; ?>>Support not required</option>
                <option value="Yes" <?php if ($remotedebug=='Yes') echo 'selected'; ?>>Allow the WP Auctions Support team access to your <a href="http://php.net/manual/en/function.phpinfo.php">PHP Config Information</a></option>
         </select>
        <br />
        <p><?php _e('Select whether you want to divulge your server information to assist remote debugging. Your information will be visible <a href="'.get_bloginfo('wpurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME.'?debug">here</a>') ?></p></td> 
      </tr> 
           
    </table>

	<input type="hidden" id="wp_auctions-submit" name="wp_auctions-submit" value="1" />

    <p class="submit">
      <input type="submit" name="Submit" value="<?php _e('Update Options') ?> &raquo;" />
    </p>
  </form> 
</div>

<?php
}


function wp_auctions_welcome() {

global $wpa_version;
global $wp_version;

// first let's check if database is update date
wp_auctions_install();

// Use WordPress built-in RSS handling
require_once (ABSPATH . WPINC . '/rss.php');
$rss_feed = "http://www.wpauctions.com/feed/";
$rss = @fetch_rss( $rss_feed );

?>
<link href="../wp-content/plugins/wp-auctions/requisites/style.css" rel="stylesheet" type="text/css" />

<div class="wrap wp-auctions">
  
	<div class="wpa-intro">

	<p>Version: <?php echo $wpa_version ?></p>
    <div class="latestnews">
        <h3>Plugin News</h3>
        <ul>
        <?php
        if ( isset($rss->items) && 1 < count($rss->items) ) {
        $rss->items = array_slice($rss->items, 0, 4);
        foreach ($rss->items as $item ) {
        ?>
          <li><a href="<?php echo wp_filter_kses($item['link']); ?>"><?php echo wptexturize(wp_specialchars($item['title'])); ?></a></li>
        <?php } ?>
        </ul>
        <?php
        }
        else {
          echo ("No news found ..");
        }
        ?>
    </div>

    <div class="wpa-info">
	  	<h3 class="wpa-upgradepro">Upgrade to Pro</h3>
        	<p class="wpa-notice"><a href="../wp-admin/admin.php?page=wp-auctions-upgrade">Upgrade today! Click to view your options.</a></p>
	  		<p>Pro features: Simple bidding, reverse bidding, watching auctions, color customization, shipping price, private auctions, Buy it Now option, embed auctions in a post, extra image uploads and many more features!</p>
    </div>

    <div style="clear:both"></div>
</div>
<h2>Get Started</h2>

<ul class="wpa-start">
	<li><div class="buttons"><button onclick="window.location = 'admin.php?page=wp-auctions-add';" class="button"><strong>Add An Auction</strong></button></div></li>
    <li><div class="buttons">/ &nbsp;<button onclick="window.location = 'admin.php?page=wp-auctions-manage';" class="button"><strong>Manage Auctions</strong></button></div></li>
	<li><div class="buttons wpa-upgrade">/ &nbsp;<button onclick="window.location = '../wp-admin/admin.php?page=wp-auctions-upgrade';" class="button"><strong>Upgrade Plugin</strong></button></div></li>
</ul>
<div style="clear:both"></div>

<?php wp_auctions_options();  ?>

</div>

<?php   
}


function wpa_resetgetvars()
{
	unset($GLOBALS['_GET']["wpa_action"]);
	unset($GLOBALS['_GET']["wpa_id"]);
}

function wpa_chkfields($strName, $strDescription,$strEndDate)
{
	if($strName == "" || $strDescription == "" || $strEndDate == ""):
		$bitError = 1;
	endif;
	return $bitError;
}

function wpa_chkPrices($StartPrice, $ReservePrice,$BINPrice)
{
  if (($StartPrice < 0.01) && ($BINPrice <0.01)):
		$bitError = 1;
	elseif($ReservePrice > 0 && ($ReservePrice - $StartPrice) < 0):
		$bitError = 1;
	elseif($BINPrice > 0 && ($BINPrice - $StartPrice) < 0):
		$bitError = 1;
	endif;
	
	return $bitError;
}

function wp_auctions_add() {

   global $wpdb;
   $table_name = $wpdb->prefix . "wpa_auctions";

   $options = get_option('wp_auctions');
   $paypal = $options['paypal'];
   $mailingaddress = $options['mailingaddress'];
   $bankdetails = $options['bankdetails'];
   $customincrement = $options['customincrement'];
     
   // Primary action
   if(isset($_REQUEST["wpa_action"])):

      // security check
      check_admin_referer( 'WPA-nonce');

      // handle a file upload if there is one
		  $overrides = array('test_form' => false);
								
		  $file = wp_handle_upload($_FILES['upload_0'], $overrides);

      if ( !isset($file['error']) ) {
         $url = $file['url'];
         $type = $file['type'];
         $file = $file['file'];
         $filename = basename($file);

         // Construct the object array
         $object = array(
           'post_title' => $filename,
           'post_content' => $url,
           'post_mime_type' => $type,
           'guid' => $url);

         // Save the data
         $id = wp_insert_attachment($object, $file);

         // Add the meta-data
         wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

         do_action('wp_create_file_in_uploads', $file, $id); // For replication
      
         $strSaveImageURL = $url;
      } else {
         $strSaveImageURL = $_POST["wpa_ImageURL"];
      }

      if($_POST["wpa_action"] == "Add Auction"):
         $strSaveName = strip_tags(htmlspecialchars($_POST["wpa_name"]));
         $strSaveDescription = $_POST["wpa_description"];
         $strSaveStartPrice = $_POST["wpa_StartPrice"];
         $strSaveReservePrice = $_POST["wpa_ReservePrice"];
         $strSaveEndDate = $_POST["wpa_EndDate"];
         $strSaveImageURL1 = $_POST["wpa_ImageURL1"];
         $strPaymentMethod = $_POST["wpa_PaymentMethod"];              
         //$strSaveImageURL = $_POST["wpa_ImageURL"]; - handled above!
      elseif($_POST["wpa_action"] == "Update Auction"):
         $strUpdateID = $_POST["wpa_id"];
         $strSaveName = strip_tags(htmlspecialchars($_POST["wpa_name"]));
         $strSaveDescription = $_POST["wpa_description"];
         $strSaveStartPrice = $_POST["wpa_StartPrice"];
         $strSaveReservePrice = $_POST["wpa_ReservePrice"];
         $strSaveEndDate = $_POST["wpa_EndDate"];
         $strSaveImageURL1 = $_POST["wpa_ImageURL1"];
         $strPaymentMethod = $_POST["wpa_PaymentMethod"];              
         //$strSaveImageURL = $_POST["wpa_ImageURL"]; - handled above!

         $bolUpdate = true;
      elseif($_GET["wpa_action"] == "edit"):
         $wpa_id = $_GET["wpa_id"];
      
         if ($wpa_id > 0):
           $strSQL = "SELECT * FROM ".$table_name." WHERE id=".$wpa_id;
           
           $resultEdit = $wpdb->get_row($strSQL);
           $strUpdateID = $_GET["wpa_id"];
           $strSaveName = htmlspecialchars_decode($resultEdit->name, ENT_NOQUOTES);
           $strSaveDescription = stripslashes($resultEdit->description);
           $strSaveImageURL = $resultEdit->image_url;
           $strSaveStartPrice = $resultEdit->start_price;
           $strSaveReservePrice = $resultEdit->reserve_price;
           $strSaveEndDate = get_date_from_gmt($resultEdit->date_end);
           $strSaveImageURL1 = $resultEdit->extraimage1;
           $strPaymentMethod = $resultEdit->paymentmethod;
           $bolUpdate = true;
           wpa_resetgetvars();
         endif;
      elseif($_GET["wpa_action"] == "relist"):
         $wpa_id = $_GET["wpa_id"];
      
         if ($wpa_id > 0):
           $strSQL = "SELECT * FROM ".$table_name." WHERE id=".$wpa_id;
           $resultList = $wpdb->get_row($strSQL);
           $strSaveName = htmlspecialchars_decode($resultList->name, ENT_NOQUOTES);
           $strSaveDescription = stripslashes($resultList->description);
           $strSaveImageURL = $resultList->image_url;
           $strSaveStartPrice = $resultList->start_price;
           $strSaveReservePrice = $resultList->reserve_price;
           $strSaveEndDate = get_date_from_gmt($resultList->date_end);
           $strSaveImageURL1 = $resultList->extraimage1;
           $strPaymentMethod = $resultList->paymentmethod;
           wpa_resetgetvars();
         endif;
      endif;
   endif;

   // Validation & Save
   if($_POST["wpa_action"] == "Add Auction"):
      if(wpa_chkfields($strSaveName, $strSaveDescription,$strSaveEndDate)==1):
         $strMessage = "Please fill out all fields.";
      elseif(strtotime($strSaveEndDate) < strtotime(get_date_from_gmt(date('Y-m-d H:i:s')))):      
         $strMessage = "Auction end date/time cannot be in the past: (Specified: ".$strSaveEndDate." - Current: ".get_date_from_gmt(date('Y-m-d H:i:s')).")";
      elseif(wpa_chkPrices($strSaveStartPrice,$strSaveReservePrice,0) == 1):
         $strMessage = "Starting Price must be numeric and less than Reserve";
      endif;

      if ($strMessage == ""):
         // force reserve value (not implemented),BINPrice and Shipping Price to ensure value written in InnoDB (which doesn't like Null decimals)
         $strSaveReservePrice = 0;
         $strSaveDuration = 0;
         
         // convert date/time to GMT         
         $strSaveEndDate = get_gmt_from_date($strSaveEndDate);
         $GMTTime = current_time('mysql',"1");

         $strSQL = "INSERT INTO $table_name (date_create,date_end,name,description,image_url,start_price,reserve_price,BIN_price,duration,shipping_price,shipping_from,shipping_to,extraimage1,extraimage2,extraimage3,staticpage,paymentmethod) VALUES('".$GMTTime."','".$strSaveEndDate."','".$strSaveName."','".$strSaveDescription."','".$strSaveImageURL."','".$strSaveStartPrice."','".$strSaveReservePrice."','0','".$strSaveDuration."','0','','','".$strSaveImageURL1."','','','','".$strPaymentMethod."')";
         
         // defensive check to make sure noone's put "|" in any field (as this breaks AJAX)
         $strSQL = str_replace( "|" , "" , $strSQL );
         
         $wpdb->query($strSQL);
         $strMessage = "Auction added";
         $strSaveName = "";
         $strSaveDescription = "";
         $strSaveImageURL = "";
         $strSaveStartPrice = "";
         $strSaveReservePrice = "";
         $strSaveDuration = "";
         $strStaticPage = "";
         $strSaveEndDate = "";
         $strSaveImageURL1 = "";
         $strPaymentMethod = "";
         
      endif;
      wpa_resetgetvars();
   elseif($_POST["wpa_action"] == "Update Auction"):
      if(wpa_chkfields($strSaveName, $strSaveDescription,$strSaveStartPrice,$strSaveDuration)==1):
         $strMessage = "Please fill out all fields.";
      elseif(strtotime($strSaveEndDate) < strtotime(get_date_from_gmt(date('Y-m-d H:i:s')))):      
         $strMessage = "Auction end date/time cannot be in the past: (Specified: ".$strSaveEndDate." - Current: ".get_date_from_gmt(date('Y-m-d H:i:s')).")";
      elseif(wpa_chkPrices($strSaveStartPrice,$strSaveReservePrice,0) == 1):
         $strMessage = "Starting Price must be numeric and less than Reserve";
      //elseif(($othercondition) == 0):
      //   $strMessage = "Data is not valid";
      endif;

      if ($strMessage == ""):
         // force reserve value (not implemented),BINPrice and Shipping Price to ensure value written in InnoDB (which doesn't like Null decimals)
         $strSaveReservePrice = 0;
         $strSaveDuration = 0;

         // convert date/time to machine
         $strSaveEndDate = get_gmt_from_date($strSaveEndDate);

         $strSQL = "UPDATE $table_name SET name='$strSaveName', description = '$strSaveDescription', image_url = '$strSaveImageURL', start_price = '$strSaveStartPrice', reserve_price = '$strSaveReservePrice', duration = '$strSaveDuration', date_end = '$strSaveEndDate', extraimage1 = '$strSaveImageURL1', paymentmethod = '$strPaymentMethod' WHERE id=" . $_POST["wpa_id"];

         // defensive check to make sure noone's put "|" in any field (as this breaks AJAX)
         $strSQL = str_replace( "|" , "" , $strSQL );

         $strMessage = "Auction updated";
         //$bolUpdate = false;
         
         $wpdb->query($strSQL);
         wpa_resetgetvars();
      endif;
   endif;
			
   ?>
   
   <link href="../wp-content/plugins/wp-auctions/requisites/style.css" rel="stylesheet" type="text/css" />

	<div class="wrap wp-auctions">
		<?php if($strMessage != ""):?>
			<fieldset class="options">
				<legend>Information</legend>
				<p><font color=red><strong><?php print $strMessage ?></strong></font></p>
			</fieldset>
		<?php endif; ?>
		
        <div class="clearfix">
	    	<div class="wpa-upgrade"><p class="wpa-notice" style="margin: 0 !important;">Get WP Auctions Pro: <a href="../wp-admin/admin.php?page=wp-auctions-upgrade">Upgrade Plugin</a></p></div>
		</div>
    
		<h2 class="details"><em>Auction Details</em></h2>

<script language="Javascript">

jQuery(document).ready(function() {
  
  // set up datepicker
  jQuery("#wpa_EndDate").datetimepicker({ dateFormat: 'yy-mm-dd', timeFormat: 'hh:mm:ss' });

});

</script>
<?php
wp_tiny_mce( false , // true makes the editor "teeny"
	array(
		"editor_selector" => "wpa_description"
	)
);
?>


		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=wp-auctions-add" id="editform" enctype="multipart/form-data">

    <?php wp_nonce_field('WPA-nonce'); ?>

    <table width="100%" cellspacing="2" cellpadding="5" class="widefat"> 
      <tr valign="top" class="alternate"> 
        <th scope="row"><?php _e('Title:') ?></th> 
        <td><input type="text" name="wpa_name" value="<?php print $strSaveName ?>" maxlength="255" size="50" /><br>
        <?php _e('Specify the title for your auction.') ?></td> 
      </tr>
      <tr valign="top"> 
        <th scope="row"><?php _e('Description:') ?></th> 
        <td><textarea rows="5" cols="50" id="wpa_description" name="wpa_description" class="wpa_description"><?php print $strSaveDescription ?></textarea>
        <br>
        <p><?php _e('Specify the description for your auction.') ?></p>
		</td> 
      </tr>
      <tr valign="top" class="alternate"> 
        <th scope="row"><?php _e('Image URL:') ?></th> 
        <td><input type="text" name="wpa_ImageURL" value="<?php print $strSaveImageURL ?>" maxlength="255" size="50" id="upload_image_0"/>
        <p>You can specify a URL to an image, alternatively upload one from your computer</p>
        <label for="upload_0"><?php _e('Choose an image from your computer:'); ?></label><br /><input type="file" id="upload_0" name="upload_0" />
        <br>
        <?php _e('Specify the image for your auction. If your images do not appear please CHMOD the "wp-auctions/files" folder 777 via FTP. <a href="http://codex.wordpress.org/Changing_File_Permissions#Using_an_FTP_Client" target="_blank">Instructions</a>.') ?></td> 
      </tr>
      <tr valign="top" class="alternate"> 
        <th scope="row"><?php _e('Start Price:') ?></th> 
        <td><input type="text" name="wpa_StartPrice" value="<?php print $strSaveStartPrice ?>" maxlength="255" size="10" /><br>
        <?php _e('Specify the starting price for your auction. Leave empty (or 0) for Fixed Price BIN') ?>
        <?php if (!empty($customincrement)) { echo '<br>'; _e('Remember that you have configured bidding in increments of '); echo $customincrement; } ?>
        </td> 
      </tr>
      <tr valign="top"> 
        <th scope="row"><?php _e('End Date:') ?></th> 
        <td><input type="text" name="wpa_EndDate" id="wpa_EndDate" value="<?php print $strSaveEndDate ?>" maxlength="20" size="20" /><br>
        <?php _e('When would you like this auction to end? Note that blog time is: '); echo get_date_from_gmt(date('Y-m-d H:i:s')); ?></td> 
      </tr>
      <tr valign="top" class="alternate"> 
        <th scope="row" style="border-bottom: 0;"><?php _e('Payment Method:') ?></th> 
        <td style="border-bottom: 0;">
           <input name="wpa_PaymentMethod" id="wpa-radio" type="radio" value="paypal" <?php if ($strPaymentMethod=="paypal") echo "CHECKED";?> <?php if ($paypal=="") echo "DISABLED";?>><label for="wpa_PaymentMethod">PayPal<br>
           <input name="wpa_PaymentMethod" id="wpa-radio" type="radio" value="bankdetails" <?php if ($strPaymentMethod=="bankdetails") echo "CHECKED";?> <?php if ($bankdetails=="") echo "DISABLED";?>>Wire Transfer<br>        
           <input name="wpa_PaymentMethod" id="wpa-radio" type="radio" value="mailingaddress" <?php if ($strPaymentMethod=="mailingaddress") echo "CHECKED";?> <?php if ($mailingaddress=="") echo "DISABLED";?>>Cheque or Money Order<br>        
        <?php _e('Specify the payment method from this auction (Only options you filled on the Configuration screen are available)') ?></td> 
      </tr>
     </table>
   

		<?php if($bolUpdate == true): ?>
			<div class="buttons add-auction"><input type="hidden" name="wpa_id" value="<?php echo $strUpdateID ?>"><input type="hidden" name="wpa_action" value="Update Auction">
			<input type="submit" name="wpa_doit" value="Update Auction" class="button"></div>
		<?php else: ?>
			<div class="buttons add-auction"><input type="hidden" name="wpa_action" value="Add Auction"><input type="submit" name="wpa_doit" value="Add Auction &raquo;" class="button" ></div>
		<?php endif; ?>


			</form>
		
	</div>
<?php
}


function wp_auctions_upgrade() {
?>

<link href="../wp-content/plugins/wp-auctions/requisites/style.css" rel="stylesheet" type="text/css" />

<div class="wrap wp-auctions wp-auctions-upgrade"> 
	
    <div class="clearfix">
		<h2>Your Upgrade Options</h2>
		
			<div class="wpa-intro wpa-plugins">
				<p>You are using the Lite version</p>
				
				<div class="downloadplugin">
					<h3>Pro, Latest Version Instant Download</h3>
					<p class="downloadupgrade"><a href="https://www.e-junkie.com/ecom/gb.php?i=WPA&#038;c=single&#038;cl=16004" target="ejejcsingle">Only $35, Click for Instant Download</a></p>
					<p>After you buy, please follow these steps.</p>
						<ul>
							<li>Pay and download latest Pro version instantly.</li>
							<li>De-activate and delete the Lite version.</li>
							<li>Upload Pro version.</li>
							<li>Add Auctions!</li>
							<li>Make Money!</li>
						</ul>
				</div>

				<div class="downloadplugin">
					<h3>Pro, Subscription</h3>
					<p class="downloadupgrade"><a href="http://www.weborithm.com/products/signup.php?hide_paysys=free">Only $89, Register &amp; Download</a> Use coupon code <strong>1BCF1</strong> to save $15!</p>
					<p>After you buy, please follow these steps.</p>
						<ul>
							<li>Pay and download latest Pro version from your member area.</li>
							<li>De-activate and delete the Lite version.</li>
							<li>Upload Pro version.</li>
							<li>Add Auctions!</li>
							<li>Make Money!</li>
							<li>You also get free updates and forum support for one year.</li>
						</ul>
				</div>
				
				<div class="downloadthemes">
					<h3>ThemeSpace - WordPress Themes, HTML Templates</h3>
					<p>For only $35, get instant access to a growing library of all our WordPress themes, HTML templates and more!</p>
					<p class="downloadupgrade"><a href="http://www.weborithm.com/products/signup.php?hide_paysys=free">Join ThemeSpace</a></p>
						<ul>
							<li>Get access to ALL of our current and future themes and templates for one year.</li>
							<li>Professional design and code.</li>
							<li>Unlimited domain use.</li>
							<li>Easily customizable.</li>
							<li>Free updates.</li>
						</ul>
				</div>
				<div style="clear:both"></div>
			</div>
	</div>
</div>    
<?php
}

function wp_auctions_manage() {

   global $wpdb;

   // Primary action
   if(isset($_REQUEST["wpa_action"])):

      // security check
      check_admin_referer( 'WPA-nonce');

      if($_GET["wpa_action"] == "reverse"):
         $intAuctionID = $_GET["wpa_id"];
         $intBidID = $_GET["bid_id"];

         // get ready to reverse the last bid on the auction
     		 $bid_table_name = $wpdb->prefix . "wpa_bids";
     		 $auction_table_name = $wpdb->prefix . "wpa_auctions";

         // Step 1 - Delete Last bid
         $strSQL = "DELETE FROM $bid_table_name WHERE id=" . $intBidID;
         $wpdb->query($strSQL);

         // Step 2 - Assess highest bid
		     $strSQL = "SELECT * FROM $bid_table_name WHERE auction_id=".$intAuctionID." ORDER BY current_bid_price DESC LIMIT 1";
		     $current = $wpdb->get_row ($strSQL);

         // Step 3 - Update Auction with current bid price
			  $sql = "UPDATE ".$auction_table_name." SET current_price = ".$current->current_bid_price." WHERE id=".$intAuctionID;
			  $wpdb->query($sql);

      elseif ($_GET["wpa_action"] == "terminate"):
         $intAuctionID = $_GET["wpa_id"];

         // get ready to reverse the last bid on the auction
     		 $auction_table_name = $wpdb->prefix . "wpa_auctions";

         // Step 1 - Update auction to set end timestamp to now
			  $sql = "UPDATE ".$auction_table_name." SET date_end = '".current_time('mysql',"1")."' WHERE id=".$intAuctionID;
			  $wpdb->query($sql);

         // wait a bit, to make sure Now() in termination check doesn't match NOW() here.
         sleep (2);

         // Step 2 - Teminate Auction
         check_auction_end($intAuctionID );  
      elseif($_GET["wpa_action"] == "delete"):
     		 $auction_table_name = $wpdb->prefix . "wpa_auctions";
         $strSQL = "DELETE FROM $auction_table_name WHERE id=" . $_GET["wpa_id"];
         $wpdb->query($strSQL);         
      endif;
   endif;

   $options = get_option('wp_auctions');
   $currencysymbol = $options['currencysymbol'];

   $nonce = wp_create_nonce ('WPA-nonce')

?>

<link href="../wp-content/plugins/wp-auctions/requisites/style.css" rel="stylesheet" type="text/css" />

<div class="wrap wp-auctions"> 
	
    <div class="clearfix">
    <div class="wpa-upgrade"><p class="wpa-notice" style="margin: 0 !important;">Get WP Auctions Pro: <a href="../wp-admin/admin.php?page=wp-auctions-upgrade">Upgrade Plugin</a></p></div>
	<div class="wpa-time"><p>Wordpress Time: <?php echo get_date_from_gmt(date('Y-m-d H:i:s')); ?></p></div>
	</div>
    
	<h2 class="manage"><em><?php _e('Manage Auctions') ?></em></h2>
	
	<fieldset class="options">
	<legend>Current Auctions</legend>
	<?php
		$table_name = $wpdb->prefix . "wpa_auctions";
		$strSQL = "SELECT id, date_create, date_end, name, BIN_price, image_url, current_price FROM $table_name WHERE '".current_time('mysql',"1")."' < date_end ORDER BY date_end DESC";
		$rows = $wpdb->get_results ($strSQL);
		
		$bid_table_name = $wpdb->prefix . "wpa_bids";
	?>
	<table class="widefat">
       <thead>
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Created/Ending</th>
			<th>Bids</th>
			<th>Current Price</th>
			<th>Thumbnail</th>
			<th>Actions</th>
		</tr>
       </thead>
	<?php if (is_array($rows)): ?>
		<?php foreach ($rows as $row) { 
             $style=" ";
             if($intAlternate==1) $style=$style."alternate "; 
             if(strtotime($row->date_end)<=strtotime("now")) $style=$style."active ";

             ?>
			<tr<?php if($style!=" "): ?> class="<?php echo $style ?>"<?php endif; ?>>
				<td><?php print $row->id; ?></td>
				<td><?php print $row->name; ?> </td>
				<td><b>Created:</b><br><?php print get_date_from_gmt($row->date_create); ?> <br>
				    <b>Ending:</b><br><?php print get_date_from_gmt($row->date_end); ?></td>
				<td align="center">
<?php

  $bids=0;
					// prepare result
	$strSQL = "SELECT id, bidder_name, bidder_email , bidder_url, date,current_bid_price FROM $bid_table_name WHERE auction_id=".$row->id." ORDER BY current_bid_price";
	$bid_rows = $wpdb->get_results ($strSQL);
			
	foreach ($bid_rows as $bid_row) {
	   echo ('<a href="mailto:'.$bid_row->bidder_email.'">');
	   echo ($bid_row->bidder_name);
	   echo ('</a> ('.$bid_row->bidder_url.') - '.$currencysymbol.$bid_row->current_bid_price);
	   echo ('<br>');
	   $bids++;
	}		
	
	if ($bids!=0)	{
?>
	   <br>
	   
     <a href="javascript:if(confirm('Are you sure you want to reverse the last bid for \'<?php print $bid_row->current_bid_price; ?>\'?')==true) location.href='admin.php?page=wp-auctions-manage&amp;wpa_action=reverse&amp;wpa_id=<?php echo $row->id ?>&amp;bid_id=<?php echo $bid_row->id ?>&amp;_wpnonce=<?php echo $nonce ?>'" class="edit">Cancel Last Bid</a><br/><br/>
<?php
	}
?>			
          </td>
				<td><?php if ( $row->current_price > 0 ) { echo $currencysymbol.$row->current_price; } else { echo "No bids"; }?><?php if ($row->BIN_price>0) print "<br>BIN Price: ".$row->BIN_price ?></td>
				<td style="vertical-align: middle"><img src="<?php if ($row->image_url != "") { print wpa_resize($row->image_url,150); } ?>" width="150" height="150"></td>
				<td>
            <a href="javascript:if(confirm('Are you sure you want to end auction \'<?php print addslashes(str_replace ( '"' , "'" , $row->name)); ?>\'?')==true) location.href='admin.php?page=wp-auctions-manage&amp;wpa_action=terminate&amp;wpa_id=<?php echo $row->id ?>&amp;_wpnonce=<?php echo $nonce ?>'" class="edit">End Auction</a><br/><br/>
				    <a href="admin.php?page=wp-auctions-add&amp;wpa_action=edit&amp;wpa_id=<?php print $row->id ?>&amp;_wpnonce=<?php echo $nonce ?>" class="edit">Edit</a><br/><br/>
            <a href="javascript:if(confirm('Delete auction \'<?php print addslashes(str_replace ( '"' , "'" , $row->name)); ?>\'? (This will erase all details on bids, winners and the auction)')==true) location.href='admin.php?page=wp-auctions-manage&amp;wpa_action=delete&amp;wpa_id=<?php echo $row->id ?>&amp;_wpnonce=<?php echo $nonce; ?>'" class="edit">Delete</a>
        </td>
			</tr>
			<?php
				if($intAlternate == 1):
					$intAlternate=0;
				else:
					$intAlternate=1;
				endif;
			?>
		<?php } ?>
	<?php else: ?>
		<tr><td colspan="5">No auctions defined</td></tr>
	<?php endif; ?>
	</table>
	</fieldset>

	<fieldset class="options">
	<legend>Closed Auctions</legend>
	<?php
		$table_name = $wpdb->prefix . "wpa_auctions";
		$strSQL = "SELECT id, date_create, date_end, name, image_url, current_price FROM $table_name WHERE '".current_time('mysql',"1")."' >= date_end ORDER BY date_end";
		$rows = $wpdb->get_results ($strSQL);

	?>
	<table class="widefat">
       <thead>
		<tr>
			<th>ID</th>
			<th>Name</th>
			<th>Created/Ended</th>
			<th>Bids</th>
			<th>Final Price</th>
			<th>Thumbnail</th>
			<th>Actions</th>
		</tr>
       </thead>
	<?php if (is_array($rows)): ?>
		<?php foreach ($rows as $row) { 
             $style=" ";
             if($intAlternate==1) $style=$style."alternate "; 
             if(strtotime($row->date_end)<=strtotime("now")) $style=$style."active ";

             ?>
			<tr<?php if($style!=" "): ?> class="<?php echo $style ?>"<?php endif; ?>>
				<td><?php print $row->id; ?></td>
				<td><?php print $row->name; ?> </td>
				<td><b>Started:</b><br> <?php print get_date_from_gmt($row->date_create); ?> <br>
				    <b>Ended:</b><br> <?php print get_date_from_gmt($row->date_end); ?></td>
				<td>
				
<?php
					// prepare result
	$strSQL = "SELECT bidder_name, bidder_email ,date,current_bid_price FROM $bid_table_name WHERE auction_id=".$row->id." ORDER BY current_bid_price DESC";
	$bid_rows = $wpdb->get_results ($strSQL);
			
	foreach ($bid_rows as $bid_row) {
	   echo ('<a href="mailto:'.$bid_row->bidder_email.'">');
	   echo ($bid_row->bidder_name);
	   echo ('</a> - '.$currencysymbol.$bid_row->current_bid_price);
	   echo ('<br>');
	}		
			
?>
				</td>
				<td><?php print $currencysymbol.$row->current_price; ?> </td>
				<td><img src="<?php if ($row->image_url != "") { print wpa_resize($row->image_url,150); } ?>" width="150" height="1fM50"></td>
				<td>
				    <a href="admin.php?page=wp-auctions-add&amp;wpa_action=relist&amp;wpa_id=<?php print $row->id ?>&amp;_wpnonce=<?php echo $nonce ?>" class="edit">Relist</a><br/><br/>
            <a href="javascript:if(confirm('Delete auction \'<?php print addslashes(str_replace ( '"' , "'" , $row->name)); ?>\'? (This will erase all details on bids, winners and the auction)')==true) location.href='admin.php?page=wp-auctions-manage&amp;wpa_action=delete&amp;wpa_id=<?php echo $row->id; ?>&amp;_wpnonce=<?php echo $nonce ?>'" class="edit">Delete</a>
        </td>
			</tr>
			<?php
				if($intAlternate == 1):
					$intAlternate=0;
				else:
					$intAlternate=1;
				endif;
			?>
		<?php } ?>
	<?php else: ?>
		<tr><td colspan="5">No auctions defined</td></tr>
	<?php endif; ?>
	</table>
	</fieldset>

</div>

<?php   
}



// style header - Load CSS and LightBox Javascript

function wp_auctions_header() {

   $options = get_option('wp_auctions');
   $style = $options['style'];

   echo "\n" . '<!-- wp_auction start -->' . "\n";
   echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-includes/js/thickbox/thickbox.css" />' . "\n\n";
   echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . PLUGIN_EXTERNAL_PATH . 'styles/'.$style.'/color.css" />' . "\n";  
   if (function_exists('wp_enqueue_script')) {
      wp_enqueue_script('jquery');
      wp_enqueue_script('thickbox');
      wp_enqueue_script('wp_auction_AJAX', get_bloginfo('wpurl') . PLUGIN_EXTERNAL_PATH . JSCRIPT_NAME );

      wp_print_scripts();
      
?>      
  
<?php      
      
   } else {
      echo '<!-- WordPress version too low to run WP Auctions -->' . "\n";
   }
      
   echo '<!-- wp_auction end -->' . "\n\n";

}


function wpa_admin_scripts() {
   wp_enqueue_script( 'jquery-ui-datetimepicker', get_bloginfo('wpurl') . PLUGIN_EXTERNAL_PATH . 'js/jquery-ui-timepicker-addon.js', array('jquery-ui-datepicker','jquery-ui-slider') , 0.1, true );
}

function wpa_admin_styles() {
   wp_enqueue_style( 'jquery-ui-datetimepicker', get_bloginfo('wpurl') . PLUGIN_EXTERNAL_PATH . 'js/timepicker.custom.css' );
   wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css');
   
}

if (isset($_GET['page']) && $_GET['page'] == 'wp-auctions-add') {
   add_action('admin_print_scripts', 'wpa_admin_scripts');
   add_action('admin_print_styles', 'wpa_admin_styles');
}


function wp_auctions_adminmenu(){

   // add new top level menu page
   add_menu_page ('WP Auctions', 'WP Auctions' , 'manage_options' , PLUGIN_PATH , 'wp_auctions_welcome' );

   // add submenus
   add_submenu_page (PLUGIN_PATH, 'Manage', 'Manage', 'manage_options' , 'wp-auctions-manage', 'wp_auctions_manage' );
   add_submenu_page (PLUGIN_PATH, 'Add', 'Add', 'manage_options' , 'wp-auctions-add', 'wp_auctions_add' );
   add_submenu_page (PLUGIN_PATH, 'Upgrade', 'Upgrade', 'manage_options' , 'wp-auctions-upgrade', 'wp_auctions_upgrade' );
}

add_action('wp_head', 'wp_auctions_header');
add_action('widgets_init', 'widget_wp_auctions_init');
add_action('admin_menu','wp_auctions_adminmenu',1);
add_action('activate_'.plugin_basename(__FILE__), 'wp_auctions_install');
add_action('deactivate_'.plugin_basename(__FILE__), 'wp_auctions_uninstall');
add_action('wpa_daily_check', 'close_expired_auctions');

?>