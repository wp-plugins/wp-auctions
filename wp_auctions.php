<?php
/*
Plugin Name: WP_Auctions
Plugin URI: http://www.wpauctions.com/downloads
Description: Implements the ability to run auctions on your own blog. Once activated, add the widget to your sidebar or add <code>&lt;?php wp_auctions(); ?></code> to your sidebar. Please note that deactivating this plugin will erase your auctions.
Version: 1.0.6
Author: Owen Cutajar & Hyder Jaffari
Author URI: http://www.wpauctions.com/profile
*/

  /* History:
  v0.1 Beta  - OwenC - 29/01/08 - Initial beta release
  v1.0 Free  - OwenC - 21/02/08 - Free public release  
  v1.0.5 - Corrected screenshots and added some more help
  v1.0.6 - Corrected text on Style options
*/

// cater for stand-alone calls
if (!function_exists('get_option'))
	require_once('../../../wp-config.php');

$wpa_version = "1.0 Free";

// Consts
define('BID_WIN', 'Congratulations, you are the highest bidder on this item.');
define('BID_LOSE', "I'm sorry, but a preceeding bidder has outbid you.");

define('PLUGIN_EXTERNAL_PATH', '/wp-content/plugins/wp-auctions/');
define('PLUGIN_STYLE_PATH', 'wp-content/plugins/wp-auctions/styles/');
define('PLUGIN_NAME', 'wp_auctions.php');
define('PLUGIN_PATH', 'wp-auctions/wp_auctions.php');

// Echo Dynamic Javascript (.js) - technique borrowed from ajax-comments (http://www.mikesmullin.com) 
if (strstr($_SERVER['PHP_SELF'],PLUGIN_EXTERNAL_PATH.PLUGIN_NAME) && isset($_GET['js'])):
header("Content-Type:text/javascript"); ?>
// Popup front-end code

// This code needs to be "refactored" to consolidate all the similar routines

// 22nd Nov - ripped out countdown code

// AJAX Functions
// Functions are all seperate so we can do different funky stuff with each

var ajax_auction_loading = false;
var ajax_bid_loading = false;
var ajax_other_loading = false;

function ajax_auctions_loading(on) {
   if (on) {
      ajax_auction_loading = true;
      // do funky stuff here
   } else {
      // clear funky stuff here
      ajax_auction_loading = false;
   }
}

function ajax_bids_loading(on) {
   if (on) {
      ajax_bid_loading = true;
      // do funky stuff here
   } else {
      // clear funky stuff here
      ajax_bid_loading = false;
   }
}

function ajax_others_loading(on) {
   if (on) {
      ajax_other_loading = true;
      // do funky stuff here
   } else {
      // clear funky stuff here
      ajax_other_loading = false;
   }
}

function ajax_auction_request() {

   // retreive form data
   auction_id = $F("formauctionid"); 
   currencysymbol = $F("currencysymbol");

   if (ajax_auction_loading) return false;
   
   ajax_auctions_loading ( true );
   new Ajax.Request('<?=get_settings('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME?>?queryauction', {
     method: 'post',
     asynchronous: true,
     parameters : "auction_ID="+auction_id,
	 onLoading: function(request) {
	    request['timeout_ID'] = window.setTimeout(function() {
	       switch (request.readyState) {
	          case 1: case 2: case 3:
	             request.abort();
	             alert('WP_Auction Error: Timeout\nThe server is taking too long to respond');
	             break;
	       }
	    }, 25000);
	 },
	 onFailure: function(request) {
	    alert((request.status!=406? ' WP_Auction Error '+request.status+' : '+request.statusText+'\n' : '')+request.responseText);
	 },
	 onComplete: function(request) {
	    ajax_auctions_loading(false);   
	    window.clearTimeout(request['timeout_ID']);
	    if (request.status!=200) alert (request.status);  //"return"
	    
	    // update auction on screen
	    auction_details = request.responseText.split('|');
	    $('wp-tc-heading-p').innerHTML = auction_details[1];
	    $('wp_desc').innerHTML = auction_details[2];
	    $('wp_price').innerHTML = "Current Bid: " + currencysymbol + auction_details[3];
	    $('wp_startb').innerHTML = "<strong>Starting Bid:</strong> " + currencysymbol+auction_details[6];
	    
	    if (auction_details[7] == "") { auction_details[7]='<?=get_settings('siteurl').PLUGIN_EXTERNAL_PATH?>/requisites/wp-popup-def.gif'   }
		  $('wp-topimg-p').innerHTML = '<img src="'+auction_details[7]+'" alt="My Auction Image" width="190" height="190" />';

        // Check if auction is still open
        if (auction_details[8] == 0) {
           // auction is closed
	       $('wp_endd').innerHTML = "Auction Ended";
           $("Bid Amount").disabled = true;
           $('wp-bidnow-p').innerHTML = '';
           $('wp_winningb').innerHTML = '<strong>Winning Bid:</strong> ' + currencysymbol + auction_details[10] + ' by ' + auction_details[9];
        } else {
           // auction is open
	       $('wp_endd').innerHTML = "<strong>Ending Date:</strong> "+auction_details[5];
           $("Bid Amount").disabled = false;
           $('wp-bidnow-p').innerHTML = '<a href="#" onclick="ajax_submit_bid();">Bid Now</a>';
           $('wp_winningb').innerHTML = '<strong>Winning Bid:</strong> Bid to win';
        }

        // trigger countdown code - TODO

	 }})
	 
     // fire off call to update bids
     ajax_bids_request(auction_id);

     // fire off call to update other auctions
     ajax_other_request(auction_id);

	 return false;
}

// Checked this function
function ajax_bids_request(auction_id) {

   currencysymbol = $F("currencysymbol");
   
   if (ajax_bid_loading) return false;
   
   ajax_bids_loading ( true );
   new Ajax.Request('<?=get_settings('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME?>?querybids', {
     method: 'post',
     asynchronous: true,
     parameters : "auction_ID="+auction_id,
	 onLoading: function(request) {
	    request['timeout_ID2'] = window.setTimeout(function() {
	       switch (request.readyState) {
	          case 1: case 2: case 3:
	             request.abort();
	             alert('WP_Auction Error: Timeout\nThe server is taking too long to respond');
	             break;
	       }
	    }, 25000);
	 },
	 onFailure: function(request) {
	    alert((request.status!=406? ' WP_Auction Error '+request.status+' : '+request.statusText+'\n' : '')+request.responseText);
	 },
	 onComplete: function(request) {
	    ajax_bids_loading(false);   
	    window.clearTimeout(request['timeout_ID2']);
	    if (request.status!=200) alert (request.status);  //"return"
	    
	    // update bids on screen
        if (request.responseText == '') {
           var bid_output = 'No bids found';
        } else {
           bids_details = request.responseText.split('|');

           var bid_output = '<ol class="wp-detailsbidders-p">';
           var lines = (bids_details.length/4)-1;
	       for(var i=0;i<lines;i++) {
              bid_output = bid_output + '<li><span class="wp-liststyle-p">';
              if (bids_details[i*4+2]=="") {
                 bid_output = bid_output + bids_details[i*4+1];
              } else {
                 bid_output = bid_output + '<a href="' + bids_details[i*4+2] + '" target="_blank">' + bids_details[i*4+1] + '</a>';
              }
              bid_output = bid_output + ' bid ' + currencysymbol + bids_details[i*4+4] + ' on ' + bids_details[i*4+3];
              bid_output = bid_output + '</span></li>';
           }
	       bid_output = bid_output + '</ol>';
        }   

        $('wp-detailsbidders-p').innerHTML = bid_output;

	 }})
	 
	 return false;
}


function ajax_other_request() {

   // retreive auction id
   var auction_id = $F("formauctionid");

   if (ajax_other_loading) return false;
   
   ajax_others_loading ( true );
   new Ajax.Request('<?=get_settings('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME?>?queryother&killcache=' + auction_id, {
     method: 'post',
     asynchronous: true,
     parameters : "auction_ID="+auction_id,
	 onLoading: function(request) {
	    request['timeout_ID3'] = window.setTimeout(function() {
	       switch (request.readyState) {
	          case 1: case 2: case 3:
	             request.abort();
	             alert('WP_Auction Error: Timeout\nThe server is taking too long to respond');
	             break;
	       }
	    }, 25000);
	 },
	 onFailure: function(request) {
	    alert((request.status!=406? ' WP_Auction Error '+request.status+' : '+request.statusText+'\n' : '')+request.responseText);
	 },
	 onComplete: function(request) {
	    ajax_others_loading(false);   
	    window.clearTimeout(request['timeout_ID3']);
	    if (request.status!=200) alert (request.status);  //"return"
	    
	    // update others on screen - returns multiples of 3, max 12

	    other_details = request.responseText.split('|');
	    
        odetdiv = '';
        for(var i=0;i<4;i++) {
           if (other_details[i*3+3] != undefined) {
              if (other_details[i*3+3] == '') {
                 odetdiv = odetdiv + '<a href="#" title="' + other_details[i*3+2] + '">';  
                 odetdiv = odetdiv + '<img src="<?=PLUGIN_EXTERNAL_PATH?>/requisites/wp-thumb-def.gif" border="0" alt="' + other_details[i*3+2] + '" width="50" height="50" onclick="document.getElementById(\'formauctionid\').value=' + other_details[i*3+1] + ';ajax_auction_request()"/>'; 
                 odetdiv = odetdiv + '</a>';  
              }
              else {
                 odetdiv = odetdiv + '<a href="#" title="' + other_details[i*3+2] + '">';  
                 odetdiv = odetdiv + '<img src="' + other_details[i*3+3] + '" border="0" alt="' + other_details[i*3+2] + '" width="50" height="50" onclick="document.getElementById(\'formauctionid\').value=' + other_details[i*3+1] + ';ajax_auction_request()"/>';  
                 odetdiv = odetdiv + '</a>';  
              }
           } else {
              // Should be nothing here .. let's see how it goes ..
           }
        }
 
        $('wp-othercontainer-p').innerHTML = odetdiv;

	 }})
	 
	 return false;
}


function ajax_submit_bid() {
 
   // retreive form values
   var auction_id = $F("formauctionid")
   var bidder_name = $F("Name");
   var bidder_email = $F("Email");
   var bidder_url = $F("URL");
   var max_bid = $F("Bid Amount");

   new Ajax.Request('<?=get_settings('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME?>?postauction', {
     method: 'post',
     asynchronous: true,
     parameters : "auction_id=" + auction_id + "&bidder_name="+bidder_name+"&bidder_email="+bidder_email+"&bidder_url="+bidder_url+"&max_bid="+max_bid,
	 onLoading: function(request) {
	    request['timeout_ID'] = window.setTimeout(function() {
	       switch (request.readyState) {
	          case 1: case 2: case 3:
	             request.abort();
	             alert('WP_Auction Error: Timeout\nThe server is taking too long to respond');
	             break;
	       }
	    }, 25000);
	 },
	 onFailure: function(request) {
	    alert((request.status!=406? ' WP_Auction Error '+request.status+' : '+request.statusText+'\n' : '')+request.responseText);
	 },
	 onComplete: function(request) {  
	    window.clearTimeout(request['timeout_ID']);
	    if (request.status!=200) alert (request.status);  //"return"

		alert (request.responseText);

        // fire off call to update bids
        ajax_auction_request(auction_id);
	 }})
	 
	 return false;
}

function get_rss() {
   window.location = "<?=get_settings('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME?>?rss";
}

<?php endif;

//---------------------------------------------------
//--------------AJAX CALLPOINTS----------------------
//---------------------------------------------------

if (strstr($_SERVER['PHP_SELF'],PLUGIN_EXTERNAL_PATH.PLUGIN_NAME) && isset($_GET['postauction'])):

	global $wpdb;

    $options = get_option('wp_auctions');
    $notify = $options['notify'];
    $title = $options['title'];

	function fail($s) { header('HTTP/1.0 406 Not Acceptable'); die($s);}

	// process query string here
	$auction_id = $_POST['auction_id'];
	$bidder_name = htmlspecialchars(strip_tags(stripslashes($_POST['bidder_name'])), ENT_QUOTES);
	$bidder_email = strip_tags(stripslashes($_POST['bidder_email']));
	$bidder_url = htmlspecialchars(strip_tags(stripslashes($_POST['bidder_url'])), ENT_QUOTES);
	$max_bid = $_POST['max_bid'];

    $result = '';

	// validate input
	if (!is_numeric($auction_id)): // ID not numeric
		$result = 'Invalid Auction ID specified';
    elseif (trim($bidder_name == '')):  // Bidder name not specified
        $result = 'Bidder name not supplied';
    elseif (trim($bidder_email == '')):  // Bidder email not specified
        $result = 'Bidder email not supplied';
    elseif (!valid_email($bidder_email)):  // Bidder email not specified
        $result = 'Please supply a valid email address';
    elseif (!is_numeric($max_bid)):  // Bidder email not specified
        $result = 'Your bid value is invalid';
    endif;
		
    if ($result == '') {
       // If we get this far it means that the input data is completely valid, so sanity check the data

       // Before we start .. confirm if auction has ended or not
       check_auction_end($auction_id);

       // bid is the starting bid on the auction	
       $table_name = $wpdb->prefix . "wpa_auctions";
	     $strSQL = "SELECT winner FROM $table_name WHERE id=".$auction_id;
	     $winner = $wpdb->get_var ($strSQL);          

       if ($winner != "") $result="Sorry, this auction is now closed.";

       // Let's also check that the bid is in the right range for the 
  		 $table_name = $wpdb->prefix . "wpa_auctions";
			 $strSQL = "SELECT current_price,start_price FROM $table_name WHERE id=".$auction_id;
			 $rows = $wpdb->get_row ($strSQL);

       if ($rows->start_price > $max_bid) $result="Sorry, your bid must exceed the auction start price.";
       if ($rows->current_price > $max_bid) $result="Sorry, your bid must exceed the current bid price.";

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
				 $thisbid = $current->max_bid_price + get_increment($current->max_bid_price);
	
				 // check we haven't exceeded the new bidder's maximum
				 if ($thisbid > ($max_bid + 0)) { $thisbid = $max_bid; }
	
				 //pull in auction details
				 $table_name = $wpdb->prefix . "wpa_auctions";
				 $strSQL = "SELECT id, name,description,current_price,date_create,date_end,start_price,thumb_url FROM $table_name WHERE id=".$auction_id;
				 $rows = $wpdb->get_row ($strSQL);
	
				 // Setup email fields.
				 //$headers = "From: " . get_option('blogname') . " <" . get_option('admin_email') . ">\r\n";  --> Windows fix
				 $headers = "From: " . get_option('admin_email') . "\r\n";
				 $to      = $current->bidder_email;
				 $subject = "[".$title."] You have been outbid on ".$rows->name;
				 $body   = "You have just been outbid on an auction on " . get_option('blogname') . "\n\n";
				 $body  .= "Unfortunately someone else is currently winning ".$rows->name." after placing a bid for $".$thisbid.". ";
				 $body  .= "You're still in time to win the auction, so click the link below and bid again.";
				 $body 	.= "\n\nLink: " . get_option('siteurl');
				 $body 	.= "\n\n--------------------------------------------\n";
				
				 // Send the email.
				 mail($to, $subject, $body, $headers);
	
			  } else {
				 $winner = "old";
	
				 // increase bid to take it above new bid
				 $thisbid = $max_bid + get_increment($max_bid);
	
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
  

		   if ($result == BID_WIN || $result == BID_LOSE) {
			  // Update bid table with details on bid
			  $table_name = $wpdb->prefix . "wpa_bids";
			  $sql = "INSERT INTO ".$table_name." (id, auction_id, date, bidder_name ,bidder_email, bidder_url, current_bid_price, max_bid_price) VALUES (NULL, ".$auction_id.", NOW(), '".$bidder_name."', '".$bidder_email."', '".$bidder_url."', ".$thisbid.", ".$max_bid.");";
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
				 $body 	.= "\n\nLink: " . get_option('siteurl')."?auction_to_show=".$auction_id;
				 $body 	.= "\n\n--------------------------------------------\n";
				
				 // Send the email.
				 mail($to, $subject, $body, $headers);
              }
		   }
       } 
    }
		    	
    echo $result;
	exit;
endif;


if (strstr($_SERVER['PHP_SELF'],PLUGIN_EXTERNAL_PATH.PLUGIN_NAME) && isset($_GET['queryauction'])):

	global $wpdb;

	function fail($s) { header('HTTP/1.0 406 Not Acceptable'); die($s);}

	// process query string here
	$auction_id = $_POST['auction_ID'];

	// validate input
	if (!is_numeric($auction_id)) // ID not numeric
		fail('Invalid Auction ID specified');
		
    // confirm if auction has ended or not
    check_auction_end($auction_id);

	// prepare result
	$table_name = $wpdb->prefix . "wpa_auctions";
	$strSQL = "SELECT id, name,description,current_price,date_create,date_end,start_price,image_url, NOW() < date_end, winner, winning_price FROM $table_name WHERE id=".$auction_id;
	$rows = $wpdb->get_row ($strSQL, ARRAY_N);
		
	// send back result
    if (!($rows)) // no records found
       fail('Cannot locate auction');

    // fudge date
    $rows[4] = date('dS M Y h:i A',strtotime($rows[4]));
    $rows[5] = date('dS M Y h:i A',strtotime($rows[5]));

	// prepare results   	
    $result_set = implode("|",$rows);
    
    	
    echo $result_set;
	exit;
endif;

if (strstr($_SERVER['PHP_SELF'],PLUGIN_EXTERNAL_PATH.PLUGIN_NAME) && isset($_GET['querybids'])):

	global $wpdb;

	function fail($s) { header('HTTP/1.0 406 Not Acceptable'); die($s);}

	// process query string here
	$auction_id = $_POST['auction_ID'];

	// validate input
	if (!is_numeric($auction_id)) // ID not numeric
		fail('Invalid Auction ID specified');
		
	// prepare result
	$table_name = $wpdb->prefix . "wpa_bids";
	$strSQL = "SELECT bidder_name, bidder_url ,date,current_bid_price FROM $table_name WHERE auction_id=".$auction_id." ORDER BY current_bid_price DESC";
	$rows = $wpdb->get_results ($strSQL, ARRAY_N);

	// send back result
    if (!($rows)) // no records found
       $result_set="";

	// prepare results   	
    $result_set = implode_r("|",$rows);
    	
    echo $result_set;
	exit;
endif;

if (strstr($_SERVER['PHP_SELF'],PLUGIN_EXTERNAL_PATH.PLUGIN_NAME) && isset($_GET['queryother'])):

	global $wpdb;

	function fail($s) { header('HTTP/1.0 406 Not Acceptable'); die($s);}

	// process query string here
	$auction_id = $_POST['auction_ID'];

	// validate input
	if (!is_numeric($auction_id)) // ID not numeric
		fail('Invalid Auction ID specified');
		
// WHERE NOW() < date_end

	// prepare result
	$table_name = $wpdb->prefix . "wpa_auctions";
	$strSQL = "SELECT id,name,thumb_url FROM $table_name WHERE id <> ".$auction_id." ORDER BY RAND() LIMIT 4";
	$rows = $wpdb->get_results ($strSQL, ARRAY_N);

	// send back result
    if (!($rows)) // no records found
       $result_set="";

	// prepare results   	
    $result_set = implode_r("|",$rows);
    	
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
	$strSQL = "SELECT * FROM $table_name WHERE NOW() < date_end ORDER BY ID desc LIMIT 15";
	$rows = $wpdb->get_results ($strSQL);

$now = date("D, d M Y H:i:s T");

$output = "<?xml version=\"1.0\"?>
            <rss version=\"2.0\">
                <channel>
                    <title>".get_option('blogname')." Auctions</title>
                    <link>".get_settings('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME."?rss.</link>
                    <description>Auction feed generated by wp_auctions (http://www.wpauctions.com) version".$wpa_version."</description>
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
                    <link>".get_settings('siteurl')."?auction_to_show=".$line->id."</link>
                    <description><![CDATA[<img src='".$line->thumb_url."' align='left'>".htmlentities(strip_tags($line->description))." - Closing: ".date('dS M Y',strtotime($line->date_end))." - Current Bid: ".$currencycode.number_format($line->current_price, 2, '.', ',')." -]]></description>
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
function implode_r ($glue, $pieces) {
 $out = "";
 foreach ($pieces as $piece)
  if (is_array ($piece)) $out .= implode_r ($glue, $piece);
  else                   $out .= $glue.$piece;
 return $out;
}

// helper function to calculate increment based on amount
function get_increment ($value) {

 $out = 0.01;

 if ($value >= 50) {
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
function valid_email($address)
{
// check an email address is possibly valid
return eregi('^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$', $address);
}

//---------------------------------------------------
//--------------INTERNAL CODE------------------------
//---------------------------------------------------

function wp_auctions_uninstall () {

   // Cleanup routine. Not sure if we'll need this in the final build, But for now it makes experimenting
   // with table structures much easier.

   global $wpdb;

   $table_name = $wpdb->prefix . "wpa_auctions";
   $wpdb->query("DROP TABLE {$table_name}");

   $table_name = $wpdb->prefix . "wpa_bids";
   $wpdb->query("DROP TABLE {$table_name}");   
}

function wp_auctions_install () {
   global $wpdb;
   global $wpa_version;

   $table_name = $wpdb->prefix . "wpa_auctions";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
     
      // Create Auctions Table
      
      $sql = "CREATE TABLE " . $table_name . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  wpa_state tinytext NOT NULL default '',
	  date_create datetime NOT NULL,
	  date_end datetime NOT NULL,
	  name tinytext NOT NULL,
	  description text NOT NULL,
	  image_url tinytext,
	  thumb_url tinytext,
	  start_price decimal(10,2) NOT NULL,
	  reserve_price decimal(10,2),
	  current_price decimal(10,2),
	  duration tinyint,
	  BIN_price decimal(10,2),
      winner tinytext,
      winning_price decimal(10,2),
	  UNIQUE KEY id (id)
	);";

      require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
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
	  current_bid_price decimal(10,2) NOT NULL,
	  max_bid_price decimal(10,2),
	  UNIQUE KEY id (id)
	);";

      require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
      dbDelta($sql);
  
      add_option("wpa_version", $wpa_version);
      
      //set initial values if none exist
      if ( !is_array($options) ) {
         $options = array( 'title'=>'WP Auctions', 'currency'=>'2', 'style'=>'default', 'notify'=>'', 'paypal'=>'', 'currencysymbol'=>'$', 'currencycode'=>'USD');
      }
       
   }
}

function check_auction_end($auction_id) {

   $options = get_option('wp_auctions');
   $paypal = $options['paypal'];
   $currencysymbol = $options['currencysymbol'];
   $currencycode = $options['currencycode'];
   $title = $options['title'];


   global $wpdb;

   // prepare result
   $table_name = $wpdb->prefix . "wpa_auctions";
   $strSQL = "SELECT id, NOW() <= date_end, winner FROM $table_name WHERE id=".$auction_id;
   $rows = $wpdb->get_row ($strSQL, ARRAY_N);

   if ($rows[0] == $auction_id && $rows[1] == 0 && $rows[2] == '') {
      // auction has closed - update winner and price

      // prepare result
      $table_name = $wpdb->prefix . "wpa_bids";
	    $strSQL = "SELECT bidder_name, bidder_email, date,current_bid_price FROM $table_name WHERE auction_id=".$auction_id." ORDER BY current_bid_price DESC LIMIT 1";
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
	       $body   = "You have won an auction.";
	       $body 	.= "\n\nAUction: " . $rows->name . " for " . $currencysymbol . $rows->winning_price;
	       $body 	.= "\n\nLink: " . get_option('siteurl')."?auction_to_show=".$auction_id;
	  
  	     $body 	.= "\n\nYou can pay for the auction by clicking on the link below:";
	       $body 	.= "\n\nhttps://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=".urlencode($paypal)."&item_name=".urlencode($rows->name)."&amount=".urlencode($rows->winning_price)."&no_shipping=0&no_note=1&currency_code=".$currencycode."&lc=GB&bn=PP%2dBuyNowBF&charset=UTF%2d8";
	  
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
		 $body 	.= "\n\nLink: " . get_option('siteurl')."?auction_to_show=".$auction_id;
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

   // select a random record
   $table_name = $wpdb->prefix . "wpa_auctions";

   $auction_id = $_GET["auction_to_show"];

   if(!is_numeric($auction_id)) {
      $strSQL = "SELECT id, thumb_url, name, description, date_end, duration, current_price FROM ".$table_name." WHERE NOW() < date_end order by rand() limit 1";
   } else {
      $strSQL = "SELECT id, thumb_url, name, description, date_end, duration, current_price FROM ".$table_name." WHERE id=".$auction_id;
   }
   $row = $wpdb->get_row ($strSQL);

   // grab values we need
   $thumb_url = $row->thumb_url;
   $name = $row->name;
   $description = substr($row->description,0,75)."...";
   $end_date = $row->date_end;
   $current_price = $row->current_price;
   $id = $row->id;

   // show default image if no thumbnail is specified
   if ($thumb_url == "") $thumb_url = get_settings('siteurl').PLUGIN_EXTERNAL_PATH."requisites/default.png";

   // cater for no records returned
   if ($id == '') {
?>
<div>
<!--WP-Auction - Sidebar Presentation Section -->     
<div id="wp-container">
    <div id="wp-head"><?php echo $title ?></div>

    <div id="wp-body">
      <div id="wp-image"><img src="<?php echo $thumb_url ?>" width="125" height="125" /></div>
      <div class="wp-heading">No auctions found</div>
      <div id="wp-desc">Sorry, we seem to have sold out of everything we had!</div>
    <div id="wp-other"></div>
    </div>
    <div id="wp-bidcontainer"></div>
    <!--You CANNOT remove the below attribution-->
    <div id="wp-powered">Powered by <a href="http://www.wpauctions.com" target="_blank">WP Auctions</a></div>
    <!--End attribution here-->
  </div>
  <!-- Main WP Container Ends -->
</div>     
<!--WP-Auction - End -->     
<?php  
} else {

   // select "other" auctions
   $table_name = $wpdb->prefix . "wpa_auctions";

   $strSQL = "SELECT id, name  FROM ".$table_name." WHERE NOW() < date_end and id<>".$id." order by rand() limit 3";
   $rows = $wpdb->get_results ($strSQL);

?>
<!--WP-Auction - Sidebar Presentation Section -->     
<div>

  <!-- Main WP Container Starts -->
  <div id="wp-container">
    <div id="wp-head"><?php echo $title ?></div>

    <div id="wp-body">
      <div id="wp-image"><a href="<?php echo get_settings('siteurl').PLUGIN_EXTERNAL_PATH . 'auction.php?ID=' . $id ?>"  class="lbOn" title="read more"><img src="<?php echo $thumb_url ?>" width="125" height="125" /></a></div>
      <div class="wp-heading"><?php _e($name) ?></div>
      <div id="wp-desc"><?php _e($description) ?><span class="wp-more"> - <a href="<?php echo get_settings('siteurl').PLUGIN_EXTERNAL_PATH . 'auction.php?ID=' . $id ?>"  class="lbOn" title="read more">more...</a></span> </div>
      <div id="wp-date">Ending: <?php echo date('dS M Y',strtotime($end_date)) ?></div>

      <div id="wp-other">
        <div class="wp-heading">Other Auctions</div>
        <ul>
      <?php foreach ($rows as $row) {  
         echo "<li>";
         echo "- <a href='".get_settings('siteurl')."?auction_to_show=".$row->id."'>";
         echo $row->name;
         echo "</a></li>";
      } ?>
        </ul>

        <div class="wp-rss"><a href="<?=get_settings('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME?>?rss"><img src="<?=get_settings('siteurl').'/'.PLUGIN_STYLE_PATH.$style?>/frontend/images/feed-icon.png" alt="Auctions RSS Feed" border="0" title="Grab My Auctions RSS Feed"/></a> <a href="<?=get_settings('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME?>?rss" title="Grab My Auctions RSS Feed" >Auctions RSS Feed</a></div>
      </div>
    </div>
    <div id="wp-bidcontainer">
      <div id="wp-bidcontainerleft"> Current Bid: <?php echo $currencysymbol.number_format($current_price, 2, '.', ',') ?></div>
      <div id="wp-bidcontainerright"><a href="<?php echo get_settings('siteurl').PLUGIN_EXTERNAL_PATH . 'auction.php?ID=' . $id ?>" class="lbOn" title="Bid Now"><img src="<?=get_settings('siteurl').'/'.PLUGIN_STYLE_PATH.$style?>/frontend/images/bidnow.png" alt="Bid Now" width="75" height="32" border="0" /></a> </div>

    </div>
    <!--You CANNOT remove the below attribution-->
    <div id="wp-powered">Powered by <a href="http://www.wpauctions.com" target="_blank">WP Auctions</a></div>
    <!--End attribution here-->
  </div>
  <!-- Main WP Container Ends -->
  
</div>
<!--WP-Auction - End -->     
<?php

// hook to terminate auction if needed (not strictly correct, but more efficient if it's here)
check_auction_end($id);
  
}     
}

function wp_auctions_options() {

   // Note: Options for this plugin include a "Title" setting which is only used by the widget
   $options = get_option('wp_auctions');
	
   //set initial values if none exist
   if ( !is_array($options) ) {
      $options = array( 'title'=>'WP Auctions', 'currency'=>'1', 'style'=>'default', 'notify'=>'', 'paypal'=>'', 'currencysymbol'=>'$', 'currencycode'=>'USD');
   }

   if ( $_POST['wp_auctions-submit'] ) {
      $options['currency'] = strip_tags(stripslashes($_POST['wpa-currency']));
      $options['title'] = strip_tags(stripslashes($_POST['wpa-title']));
      $options['notify'] = strip_tags(stripslashes($_POST['wpa-notify']));
      $options['paypal'] = strip_tags(stripslashes($_POST['wpa-paypal']));
      
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


      update_option('wp_auctions', $options);
   }

   $currency = htmlspecialchars($options['currency'], ENT_QUOTES);
   $title = htmlspecialchars($options['title'], ENT_QUOTES);
   $notify = htmlspecialchars($options['notify'], ENT_QUOTES);
   $paypal = htmlspecialchars($options['paypal'], ENT_QUOTES);
	
?>
<div class="wrap"> 
  <h2><?php _e('WP Auctions Options') ?></h2> 
  <form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=wp-auctions-options">


    <table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
      <tr valign="top"> 
        <th scope="row"><?php _e('Auction Title:') ?></th> 
        <td><input name="wpa-title" type="text" id="wpa-title" value="<?php echo $title; ?>" size="80" />
        <br />
        <?php _e('Enter header title for your auctions') ?></td> 
      </tr> 
      <tr valign="top"> 
        <th scope="row"><?php _e('Currency:') ?></th> 
        <td>
        <select id="wpa-currency" name="wpa-currency">
                <option value="1" <?php if ($currency=='1') echo 'selected'; ?>>GBP</option>
                <option value="2" <?php if ($currency=='2') echo 'selected'; ?>>USD</option>
                <option value="3" <?php if ($currency=='3') echo 'selected'; ?>>EUR</option>
                <option value="4" <?php if ($currency=='4') echo 'selected'; ?>>JPY</option>
         </select>
        <br />
        <?php _e('Choose the currency you would like to run your auctions in') ?></td> 
      </tr> 
      <tr valign="top"> 
        <th scope="row"><?php _e('PayPal account:') ?></th> 
        <td><input name="wpa-paypal" type="text" id="wpa-paypal" value="<?php echo $paypal; ?>" size="80" />
        <br />
        <?php _e('Enter your PayPal email address (where you want auction winners to pay for their items)') ?></td> 
      </tr> 
      <tr valign="top"> 
        <th scope="row"><?php _e('Bid Notification:') ?></th> 
        <td><input name="wpa-notify" type="text" id="wpa-notify" value="<?php echo $notify; ?>" size="80" />
        <br />
        <?php _e('Enter your email address if you want to be notified whenever a new bid is placed') ?></td> 
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

// Use WordPress built-in RSS handling
require_once (ABSPATH . WPINC . '/rss.php');
$rss_feed = "http://demotest.wpauctions.com/feed/";
$rss = @fetch_rss( $rss_feed );

?>
<div class="wrap"> 
  <h2><?php _e('Welcome to WP Auctions') ?></h2>

<div id="zeitgeist">
<h2><?php _e('About WP Auctions'); ?></h2>

<div style="float:right">
<h3>Latest News</h3>
<ul>
<?php
if ( isset($rss->items) && 1 < count($rss->items) ) {
$rss->items = array_slice($rss->items, 0, 10);
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

<div>
<h3>Help support WP Auctions</h3>

<p>If you ever find any problems with WP Auctions, please report them on our <a href="http://demotest.wpauctions.com/errors/">Errors</a> page.</p>
<p>We also appreciate any donations you may want to give for the further development of this plugin</p>
<p>It keeps the pizza man coming back to our house</p>
<p>Thanks!<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIHfwYJKoZIhvcNAQcEoIIHcDCCB2wCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYBvR87FZ3X4uLN6PsH5x2/BSYkApKaSPWuT/IrsMttkI6uKR5fBIic9EMXBpyQerTAKr0ng6t59/nd7SXY3sX9U3RR8CmcamGmRp9P68fu1JNqABDdLEKO8Vwmgpk0PELRDvfVysd79/qwLvGS0o6RobejrgCuI3avIv/9xoJHGEjELMAkGBSsOAwIaBQAwgfwGCSqGSIb3DQEHATAUBggqhkiG9w0DBwQIM0LMNoxJOdWAgdiN9FAlz5/rVlE2IlH/00OPs7ffVJUVT8tOiLOp7REV6APcYRC/VnP9ypRgLu5qn/7MAOZ9jrHGlkmBedx+pcIyedDAVs5OyJqzN3l4aY19mVRoP92MN/8JhiBjdoirXMB5N+gHiyIvfT1QrHSADqG4bXby7wfmkCjfnhQ6sXEmTDLubQMOTLwp1Oy9a9W8jaoeavKiDaFeyV9hPltzLjaCeespXK4iTJj1IgVGTWQPsBCy83Y+nXgLbdwYtsoyJCuQ5vWwu/JSFu+vuPvtS6Lt+CCN9kkw/jagggOHMIIDgzCCAuygAwIBAgIBADANBgkqhkiG9w0BAQUFADCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wHhcNMDQwMjEzMTAxMzE1WhcNMzUwMjEzMTAxMzE1WjCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMFHTt38RMxLXJyO2SmS+Ndl72T7oKJ4u4uw+6awntALWh03PewmIJuzbALScsTS4sZoS1fKciBGoh11gIfHzylvkdNe/hJl66/RGqrj5rFb08sAABNTzDTiqqNpJeBsYs/c2aiGozptX2RlnBktH+SUNpAajW724Nv2Wvhif6sFAgMBAAGjge4wgeswHQYDVR0OBBYEFJaffLvGbxe9WT9S1wob7BDWZJRrMIG7BgNVHSMEgbMwgbCAFJaffLvGbxe9WT9S1wob7BDWZJRroYGUpIGRMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbYIBADAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4GBAIFfOlaagFrl71+jq6OKidbWFSE+Q4FqROvdgIONth+8kSK//Y/4ihuE4Ymvzn5ceE3S/iBSQQMjyvb+s2TWbQYDwcp129OPIbD9epdr4tJOUNiSojw7BHwYRiPh58S1xGlFgHFXwrEBb3dgNbMUa+u4qectsMAXpVHnD9wIyfmHMYIBmjCCAZYCAQEwgZQwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tAgEAMAkGBSsOAwIaBQCgXTAYBgkqhkiG9w0BCQMxCwYJKoZIhvcNAQcBMBwGCSqGSIb3DQEJBTEPFw0wODAyMTIxNjU0MTBaMCMGCSqGSIb3DQEJBDEWBBRe1RELcDwu7jIFPRnpJkeMoz+bQjANBgkqhkiG9w0BAQEFAASBgIO1SOwVP1GnDDOiBwponPw0v8XJUFGTle6rA5WB+xwoMvHG9JDd4YRuhRcUeSg0Yd+W2A+ppcsGs+f3RM2hStsgRuO9g9FmoH7UmS6grtf1Qpl85LSCxdkBLKv2Mya6vtFiJShuD+KBonQUgdEk3Y30ZbywJKlS+evibM6cEK/5-----END PKCS7-----
">
</form></p>
</div>

</div>

<p>WP Auctions helps you to host and manage auctions on your own blog. You do not pay any fees to anyone for anything. Ain't it cool.</p>

<p>You are using Version: <?php echo $wpa_version ?> on WordPress v<?php echo $wp_version ?>. <strong>You may want to consider upgrading to our Gold Version which has tons of other features you can read about <a href="http://www.wpauctions.com/download/">here</a></strong></p>

   <p>Choose an option from the menus above or select a shortcut below:</p>
   <ul>
     <li><a href="admin.php?page=wp-auctions-add">Create an auction</a></li>
     <li><a href="admin.php?page=wp-auctions-manage">Edit an auction</a></li>
     <li><a href="admin.php?page=wp-auctions-manage">Close an auction</li>
   </ul>

<table width="500px" border="1" bgcolor="#eeeeee" cellpadding="5" cellspacing="5"><tr><td>
<h3>Live Auctions</h3>

<p>Have you registered your blog yet on our <a href="http://www.wpauctions.com/live/">Live Auctions</a> area?</p>
<p>By doing so you can broadcast your auction on our site to visitors who may be looking for what you are selling. Registration is easy and free!</p>
<p>It's a great way to sell your products faster, and get FREE traffic</p>
</td></tr></table>

   
</div>

<?php   
}

function wp_auctions_style() {

   $options = get_option('wp_auctions');

   //set initial values if none exist
   if ( !is_array($options) ) {
      $options = array( 'title'=>'WP Auctions', 'currency'=>'1', 'style'=>'default', 'notify'=>'', 'paypal'=>'', 'currencysymbol'=>'$', 'currencycode'=>'USD');
   }
	
   if ( $_POST['wp_auctions-submit'] ) {
      $options['style'] = strip_tags(stripslashes($_POST['wpa-style']));
      update_option('wp_auctions', $options);
   }

   $style = htmlspecialchars($options['style'], ENT_QUOTES);

   // Prepare style list

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
<div class="wrap"> 

  <h2><?php _e('WP Auctions Look and Feel') ?></h2>


<div id="zeitgeist">
<h2>Style News</h2>
<div id="latestnews">.</div>
</div>

   <p>You can change the style of WP Auctions, so that it matches your blog better and fits closer with the atmosphere of your website. Please select a style from the list below.</p>
   
   <p>Current style: <b><?php echo $style ?></b></p>

   <legend>Select a new style</legend>

  <form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=wp-auctions-style">


    <table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
      <tr valign="top"> 
        <th scope="row"><?php _e('Style:') ?></th> 
        <td>
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
        <?php _e('Specify the style you want to use.') ?></td> 
      </tr> 
    </table>

	<input type="hidden" id="wp_auctions-submit" name="wp_auctions-submit" value="1" />

    <p class="submit">
      <input type="submit" name="Submit" value="<?php _e('Update Options') ?> &raquo;" />
    </p>
  </form>    

   <p>Get new styles for your auction widget in our <a href="http://www.wpauctions.com/styles">style store</a>.</p>

</div>

<script type="text/javascript">document.getElementById('latestnews').innerHTML = "<iframe src='http://www.wpauctions.com/styles/feed.php' width='100%' height='100%' frameborder='no'></iframe>"</script>

<?php   
}

function wpa_resetgetvars()
{
	unset($GLOBALS['_GET']["wpa_action"]);
	unset($GLOBALS['_GET']["wpa_id"]);
}

function wpa_chkfields($strName, $strDescription,$strStartPrice,$strDuration)
{
	if($strName == "" || $strDescription == "" || $strStartPrice == "" || $strDuration == ""):
		$bitError = 1;
	endif;
	return $bitError;
}

function wpa_chkPrices($StartPrice, $ReservePrice,$BINPrice)
{
    if ($StartPrice < 0.01):
		$bitError = 1;
	elseif($ReservePrice > 0 && ($ReservePrice - $StartPrice) < 0):
		$bitError = 1;
	elseif($BINPrice > 0 && ($BINPrice - $StartPrice) < 0):
		$bitError = 1;
	endif;
	
	return $bitError;
}


function wpa_chkDuration($strDuration)
{
	if(intval($strDuration) < 1):
		$bitError = 1;
	endif;
	return $bitError;
}

function wp_auctions_add() {

   global $wpdb;

   $table_name = $wpdb->prefix . "wpa_auctions";

   $arrPublish = array('Public' => '1', 'Private' => '0');

   // Primary action
   if(isset($_REQUEST["wpa_action"])):
      if($_POST["wpa_action"] == "Save"):
         $strSaveName = $_POST["wpa_name"];
         $strSaveDescription = $_POST["wpa_description"];
         $strSaveImageURL = $_POST["wpa_ImageURL"];
         $strSaveThumbURL = $_POST["wpa_ThumbURL"];
         $strSaveStartPrice = $_POST["wpa_StartPrice"];
         $strSaveReservePrice = $_POST["wpa_ReservePrice"];
         $strSaveBINPrice = $_POST["wpa_BINPrice"];
         $strSaveDuration = $_POST["wpa_Duration"];
      elseif($_POST["wpa_action"] == "Update"):
         $strUpdateID = $_POST["wpa_id"];
         $strSaveName = $_POST["wpa_name"];
         $strSaveDescription = $_POST["wpa_description"];
         $strSaveImageURL = $_POST["wpa_ImageURL"];
         $strSaveThumbURL = $_POST["wpa_ThumbURL"];
         $strSaveStartPrice = $_POST["wpa_StartPrice"];
         $strSaveReservePrice = $_POST["wpa_ReservePrice"];
         $strSaveBINPrice = $_POST["wpa_BINPrice"];
         $strSaveDuration = $_POST["wpa_Duration"];
         $bolUpdate = true;
      elseif($_GET["wpa_action"] == "edit"):
         $strSQL = "SELECT * FROM ".$table_name." WHERE id=".$_GET["wpa_id"];
         $resultEdit = $wpdb->get_row($strSQL);
         $strUpdateID = $_GET["wpa_id"];
         $strSaveName = $resultEdit->name;
         $strSaveDescription = $resultEdit->description;
         $strSaveImageURL = $resultEdit->image_url;
         $strSaveThumbURL = $resultEdit->thumb_url;
         $strSaveStartPrice = $resultEdit->start_price;
         $strSaveReservePrice = $resultEdit->reserve_price;
         $strSaveBINPrice = $resultEdit->BIN_price;
         $strSaveDuration = $resultEdit->duration;
         $bolUpdate = true;
         wpa_resetgetvars();
      elseif($_GET["wpa_action"] == "relist"):
         $strSQL = "SELECT * FROM ".$table_name." WHERE id=".$_GET["wpa_id"];
         $resultList = $wpdb->get_row($strSQL);
         $strSaveName = $resultList->name;
         $strSaveDescription = $resultList->description;
         $strSaveImageURL = $resultList->image_url;
         $strSaveThumbURL = $resultList->thumb_url;
         $strSaveStartPrice = $resultList->start_price;
         $strSaveReservePrice = $resultList->reserve_price;
         $strSaveBINPrice = $resultList->BIN_price;
         $strSaveDuration = $resultList->duration;
         wpa_resetgetvars();
      endif;
   endif;

   // Validation & Save
   if($_POST["wpa_action"] == "Save"):
      if(wpa_chkfields($strSaveName, $strSaveDescription,$strSaveStartPrice,$strSaveDuration)==1):
         $strMessage = "Please fill out all fields.";
      elseif(wpa_chkDuration($strSaveDuration) == 1):
         $strMessage = "How many days should the auction run for? (Duration is invalid)";
      elseif(wpa_chkPrices($strSaveStartPrice,$strSaveReservePrice,$strSaveBINPrice) == 1):
         $strMessage = "Starting Price must be numeric and less than Reserve and BIN Prices";
      //elseif(($othercondition) == 0):
      //   $strMessage = "Data is not valid";
      endif;

      if ($strMessage == ""):
         $strSQL = "INSERT INTO $table_name (date_create,date_end,name,description,image_url,thumb_url,start_price,reserve_price,BIN_price,duration) VALUES(NOW(),DATE_ADD(NOW(), INTERVAL ".$strSaveDuration." DAY),'".$strSaveName."','".$strSaveDescription."','".$strSaveImageURL."','".$strSaveThumbURL."','".$strSaveStartPrice."','".$strSaveReservePrice."','".$strSaveBINPrice."','".$strSaveDuration."')";
         $wpdb->query($strSQL);
         $strMessage = "Auction added";
         $strSaveName = "";
         $strSaveDescription = "";
         $strSaveImageURL = "";
         $strSaveThumbURL = "";
         $strSaveStartPrice = "";
         $strSaveReservePrice = "";
         $strSaveBINPrice = "";
         $strSaveDuration = "";
      endif;
      wpa_resetgetvars();
   elseif($_POST["wpa_action"] == "Update"):
      if(wpa_chkfields($strSaveName, $strSaveDescription,$strSaveStartPrice,$strSaveDuration)==1):
         $strMessage = "Please fill out all fields.";
      elseif(wpa_chkDuration($strSaveDuration) == 1):
         $strMessage = "How many days should the auction run for? (Duration is invalid)";
      elseif(wpa_chkPrices($strSaveStartPrice,$strSaveReservePrice,$strSaveBINPrice) == 1):
         $strMessage = "Starting Price must be numeric and less than Reserve and BIN Prices";
      //elseif(($othercondition) == 0):
      //   $strMessage = "Data is not valid";
      endif;

      if ($strMessage == ""):
         $strSQL = "UPDATE $table_name SET name='$strSaveName', description = '$strSaveDescription', image_url = '$strSaveImageURL', thumb_url = '$strSaveThumbURL', start_price = '$strSaveStartPrice', reserve_price = '$strSaveReservePrice', BIN_price = '$strSaveBINPrice', duration = '$strSaveDuration', date_end = DATE_ADD(date_create, INTERVAL ".$strSaveDuration." DAY) WHERE id=" . $_POST["wpa_id"];
         $strMessage = "Auction updated";
         $bolUpdate = false;
         $wpdb->query($strSQL);
         wpa_resetgetvars();
      endif;
   endif;
			
   ?>
	<div class="wrap">
		<h2>Auction Management</h2>
		<?php if($strMessage != ""):?>
			<fieldset class="options">
				<legend>Information</legend>
				<p><font color=red><strong><?php print $strMessage ?></strong></font></p>
			</fieldset>
		<?php endif; ?>

<script language="Javascript">
function showUploadPopup() {
   childWindow=window.open("<?php print get_settings('siteurl').PLUGIN_EXTERNAL_PATH ?>IShack_upload.php","mywindow","width=500,height=200");
   if (childWindow.opener == null) childWindow.opener = self;
} 
</script>


		<fieldset class="options">
			<legend>Add Auction</legend>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=wp-auctions-add" id="editform">

    <table width="100%" cellspacing="2" cellpadding="5" class="editform"> 
      <tr valign="top"> 
        <th scope="row"><?php _e('Title:') ?></th> 
        <td><input type="text" name="wpa_name" value="<?php print $strSaveName ?>" maxlength="255" size="50" /><br>
        <?php _e('Specify the title for your auction.') ?></td> 
      </tr>
      <tr valign="top"> 
        <th scope="row"><?php _e('Description:') ?></th> 
        <td><textarea rows="5" cols="50" name="wpa_description"><?php print $strSaveDescription ?></textarea>
        <!--<input type="text" name="wpa_description" value="<?php print $strSaveDescription ?>" maxlength="255" size="50" /> -->
        <br>
        <?php _e('Specify the description for your auction.') ?></td> 
      </tr>
      <tr valign="top"> 
        <th scope="row"><?php _e('Image URL:') ?></th> 
        <td><input type="text" name="wpa_ImageURL" value="<?php print $strSaveImageURL ?>" maxlength="255" size="50" />  <a href="Javascript:showUploadPopup()">I'd like to upload an image</a><br>
        <?php _e('Specify the image URL for your auction.') ?></td> 
      </tr>
      <tr valign="top"> 
        <th scope="row"><?php _e('Thumbnail URL:') ?></th> 
        <td><input type="text" name="wpa_ThumbURL" value="<?php print $strSaveThumbURL ?>" maxlength="255" size="50" /><br>
        <?php _e('Specify the image thubnail URL for your auction.') ?></td> 
      </tr>
      <tr valign="top"> 
        <th scope="row"><?php _e('Start Price:') ?></th> 
        <td><input type="text" name="wpa_StartPrice" value="<?php print $strSaveStartPrice ?>" maxlength="255" size="10" /><br>
        <?php _e('Specify the starting price for your auction.') ?></td> 
      </tr>
      <tr valign="top"> 
        <th scope="row"><?php _e('Duration:') ?></th> 
        <td><input type="text" name="wpa_Duration" value="<?php print $strSaveDuration ?>" maxlength="2" size="2" /><br>
        <?php _e('How many days would you like the auction to run for?') ?></td> 
      </tr>
   </table>

		<?php if($bolUpdate == true): ?>
			<input type="hidden" name="wpa_id" value="<?php echo $strUpdateID ?>">
			<input type="submit" name="wpa_action" value="Update">		
		<?php else: ?>
			<input type="submit" name="wpa_action" value="Save">
		<?php endif; ?>


			</form>
		</fieldset>
		
	</div>
<?
}


function wp_auctions_manage() {

   global $wpdb;

   // Primary action
   if(isset($_REQUEST["wpa_action"])):
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
			  $sql = "UPDATE ".$auction_table_name." SET date_end = NOW() WHERE id=".$intAuctionID;
			  $wpdb->query($sql);

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


?>
<div class="wrap"> 
  <h2><?php _e('Manage your Auctions') ?></h2>

  <div align="right">System Time: <?php echo date('l dS F Y h:i:s A'); ?></div>
	<fieldset class="options">
	<legend>Current Auctions</legend>
	<?php
		$table_name = $wpdb->prefix . "wpa_auctions";
		$strSQL = "SELECT id, DATE_FORMAT(date_create,'%D %M %Y') as created, date_end, name, thumb_url, current_price FROM $table_name WHERE NOW() < date_end ORDER BY date_end DESC";
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
				<td><b>Created:</b><br><?php print $row->created; ?> <br>
				    <b>Ending:</b><br><?php print date('dS F Y h:i:s A',strtotime($row->date_end)); ?></td>
				<td align="center">
<?php

  $bids=0;
					// prepare result
	$strSQL = "SELECT id, bidder_name, bidder_email ,date,current_bid_price FROM $bid_table_name WHERE auction_id=".$row->id." ORDER BY current_bid_price";
	$bid_rows = $wpdb->get_results ($strSQL);
			
	foreach ($bid_rows as $bid_row) {
	   echo ('<a href="mailto:'.$bid_row->bidder_email.'">');
	   echo ($bid_row->bidder_name);
	   echo ('</a> - '.$currencysymbol.$bid_row->current_bid_price);
	   echo ('<br>');
	   $bids++;
	}		
	
	if ($bids!=0)	{
?>
	   <br>
     <a href="javascript:if(confirm('Are you sure you want to reverse the last bid for \'<?php print $bid_row->current_bid_price; ?>\'?')==true) location.href='admin.php?page=wp-auctions-manage&amp;wpa_action=reverse&amp;wpa_id=<?php echo $row->id ?>&amp;bid_id=<?php echo $bid_row->id ?>'" class="edit">Cancel Last Bid</a><br/><br/>
<?php
	}
?>			
          </td>
				<td><?php print $currencysymbol.$row->current_price; ?> </td>
				<td><img src="<?php print $row->thumb_url; ?>"></td>
				<td>
            <a href="javascript:if(confirm('Are you sure you want to end auction \'<?php print $row->name; ?>\'?')==true) location.href='admin.php?page=wp-auctions-manage&amp;wpa_action=terminate&amp;wpa_id=<?php echo $row->id ?>'" class="edit">End Auction</a><br/><br/>
				    <a href="admin.php?page=wp-auctions-add&amp;wpa_action=edit&amp;wpa_id=<?php print $row->id ?>" class="edit">Edit</a><br/><br/>
            <a href="javascript:if(confirm('Delete auction \'<?php print $row->name; ?>\'? (This will erase all details on bids, winners and the auction)')==true) location.href='admin.php?page=wp-auctions-manage&amp;wpa_action=delete&amp;wpa_id=<?php echo $row->id ?>;'" class="edit">Delete</a>
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
		$strSQL = "SELECT id, DATE_FORMAT(date_create,'%D %M %Y') as created, date_end, name, thumb_url, current_price FROM $table_name WHERE NOW() >= date_end ORDER BY date_end";
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
				<td><b>Started:</b><br> <?php print $row->created; ?> <br>
				    <b>Ended:</b><br> <?php print date('dS F Y',strtotime($row->date_end)); ?></td>
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
				<td><img src="<?php print $row->thumb_url; ?>"></td>
				<td>
				    <a href="admin.php?page=wp-auctions-add&amp;wpa_action=relist&amp;wpa_id=<?php print $row->id ?>" class="edit">Relist</a><br/><br/>
            <a href="javascript:if(confirm('Delete auction \'<?php print $row->name; ?>\'? (This will erase all details on bids, winners and the auction)')==true) location.href='admin.php?page=wp-auctions-manage&amp;wpa_action=delete&amp;wpa_id=<?php echo $row->id ?>;'" class="edit">Delete</a>
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




function wp_auctions_adminmenu(){

   // add new top level menu page
   add_menu_page ('WP Auctions', 'WP Auctions' , 8 , PLUGIN_PATH , 'wp_auctions_welcome' );

   // add submenus
   add_submenu_page (PLUGIN_PATH, 'Options', 'Options', 8 , 'wp-auctions-options', 'wp_auctions_options' );
   add_submenu_page (PLUGIN_PATH, 'Manage', 'Manage', 8 , 'wp-auctions-manage', 'wp_auctions_manage' );
   add_submenu_page (PLUGIN_PATH, 'Add', 'Add', 8 , 'wp-auctions-add', 'wp_auctions_add' );
   //add_submenu_page (PLUGIN_PATH, 'Style', 'Style', 8 , 'wp-auctions-style', 'wp_auctions_style' );

}


// style header - Load CSS and LightBox Javascript

function wp_auctions_header() {

   $options = get_option('wp_auctions');
   $style = $options['style'];

   echo "\n" . '<!-- wp_auction start -->' . "\n";
   echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . PLUGIN_EXTERNAL_PATH . 'common/lightbox.css" />' . "\n\n";
   echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . PLUGIN_EXTERNAL_PATH . 'styles/'.$style.'/popup/css/popup.css" />' . "\n\n";
   echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . PLUGIN_EXTERNAL_PATH . 'styles/'.$style.'/frontend/frontend.css" />' . "\n";
   if (function_exists('wp_enqueue_script')) {
      wp_enqueue_script('prototype');
      wp_enqueue_script('wp_auction_lightbox', get_bloginfo('wpurl') . PLUGIN_EXTERNAL_PATH . 'common/lightbox.js', array('prototype'), '0.1');
      wp_enqueue_script('wp_auction_AJAX', get_bloginfo('wpurl') . PLUGIN_EXTERNAL_PATH . PLUGIN_NAME .'?js');

      wp_print_scripts();
   } else {
      echo '<!-- WordPress version too low to run WP Auctions -->' . "\n";
   }
   echo '<!-- wp_auction end -->' . "\n\n";

}

add_action('wp_head', 'wp_auctions_header');
add_action('widgets_init', 'widget_wp_auctions_init');
add_action('admin_menu','wp_auctions_adminmenu',1);
add_action('activate_'.plugin_basename(__FILE__), 'wp_auctions_install');
add_action('deactivate_'.plugin_basename(__FILE__), 'wp_auctions_uninstall');
?>