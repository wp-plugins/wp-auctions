<?php if (!function_exists('get_option'))

require_once('../../../wp-config.php'); 

$options = get_option('wp_auctions');
$style = $options['style'];
$currencysymbol = $options['currencysymbol'];
$title = $options['title'];

$filepath = get_bloginfo('wpurl').'/wp-content/plugins/wp-auctions/styles/'.$style.'/';

// Get auction to show

$auction=intval($_GET['ID']);

?>
<!-- Lite -->
<style type="text/css">
/* WP Auctions Default Style
Style Name: WPA
Style URL: http://www.wpauctions.com/styles
Style Author: Hyder Jaffari
Author URL: http://www.weborithm.com
Get more styles at http://www.wpauctions.com/styles
Last Update: June 6th, 2010
*/

/* Base */
.clearfix:after { clear: both; content: " "; display: block; line-height: 0; height: 0; visibility: hidden; }
* { margin: 0; padding: 0; line-height: normal !important; vertical-align: inherit !important; }
table { margin: 0 !important; padding: 0 !important; }

/* Modal Box*/
.TB_modal { }
#TB_window img { border: 0 !important; display: inherit !important; margin: 0 !important; }

/* Container */
#wp-container-p { font: normal 11px Verdana, Arial, sans-serif !important; text-shadow: #fff 0 1px; width: 753px; }
#wp-container-p h1, #wp-container-p h2, #wp-container-p h3, #wp-container-p h4, #wp-container-p h5, #wp-container-p h6, #wp-container-p ol, #wp-container-p ul, #wp-container-p ul li, #wp-container-p ol li, #wp-container-p table p, #wp-container-p h3 p, #wp-container-p strong, #wp-container-p ul li p { font-family: Verdana, Arial, sans-serif !important; }

#wp-container-p a { text-decoration: none; }
#wp-container-p a:hover, #wp-close-p:hover { }

#wp-container-p h2 { font-size: 17px !important; font-weight: normal; }
#wp-container-p h3 { font-size: 17px !important; font-weight: normal; }

/* Header */
#wp-header-p { height: 20px; padding: 5px 10px; }
#wp-logo-p { float: left; }
#wp-logo-p h2 { line-height: normal; margin: 0; padding: 0; }
#wp-close-p { float: right; font-size: 9px; text-transform: uppercase; }
#wp-close-p img { margin-right: 6px; top: 2px; }

/* Top Area */
#wp-top-p { height: 296px; }
#wp-content-p { float: right; height: 296px; width: 497px; }
/* Top Image */
.wpa-image { float: left; height: 296px; position: absolute; text-align: center; width: 252px; }
#wp-image-p { height: 250px; padding: 1px; }
#wp_price { font-size: 13px; font-weight: bold; padding: 5px 0 0; }
#wp-refreshbid-p, #wp-refreshbid-p a { font-size: 10px; margin: 0 !important; padding: 2px 0 0 !important; text-transform: uppercase; }

/* Description */
.wpa-description { float: left; height: 286px; padding: 5px; width: 326px; }
h3#tc-heading-p { line-height: normal; margin: 0; padding: 0 0 10px; }
#wp-description-p { font-size: 12px; height: 256px; overflow: auto; }
#wp-description-p p { line-height: 18px !important; }
#wp-description-p ul { margin: 5px 5px 5px 20px; }
#wp-description-p li { list-style: disc; padding: 5px 0; }

/* Action List */
ul.wpa-details { float: right; list-style: none; width: 160px; }
ul.wpa-details li { padding: 0; }
ul.wpa-details li strong { display: block; padding: 5px 0 0; }

#wp_winningb p { padding: 0; }
#wp_winningb strong { padding: 5px 0; }
#wp_winningb img { margin: 0 5px 5px 0; padding: 2px; }

a.wpa-bin-price { display: block; font-weight: bold; margin: 5px 0; padding: 5px; text-align: center; }

/* Bottom Area */
#wp-bottom-p { height: 244px; }

/* Bid Area */
#wp-bottom-p h3 { font: bold 13px Verdana !important; margin: 1px !important; padding: 5px; }
#wp-bottom-p h3 p { display: inline; font-size: 10px; font-weight: normal; }
#wp-bottom-p table { font-size: 12px !important; width: 375px; }
#wp-bottom-p table td { padding: 10px 10px 0; text-align: right; }
#wp-bottom-p table td p { text-align: left; margin: 0 !important; width: 117px; }
#wp-bottom-p input { height: 16px; padding: 5px; width: 200px; }
#wp-bottom-p input:focus { }

/* Left */
#wp-left-p { float: left; height: 244px; width: 375px; }

/* Bid Now */
#wp-bid-p { margin: 10px 0 0; }
#wp-bid-p table .wpa-currency { font-size: 14px; font-weight: bold; }
#wp-bid-p input { padding: 5px; width: 96px; }
#wp-bid-p table td { padding: 10px; }
p#bidnow { font-size: 14px; font-weight: bold; text-align: center !important; width: 80px !important; }
p#bidnow a { color: #000 !important; }

#wp-extrainfo { font-weight: normal; }
#wp-extrainfo:before { content: "- "; }

/* Bids/Other Auctions */
.wpa-tabs { float: left; padding: 1px 0; width: 375px; }
	
ol.wp-detailsbidders-p { font-size: 12px; margin: 0 0 0 20px; }
ol.wp-detailsbidders-p li { padding: 0 0 10px; }
ol.wp-detailsbidders-p li:hover { }
.pane-bids { height: 174px; overflow: auto; }

/* Tabs */
.wpa-tabs { height: 242px; position: relative; }
.wpa-tabs ul { list-style: none; }

.wpa-pane { display: none; padding: 10px; }
.wpa-pane p { padding: 0; }

ul.wpatabs { height: 26px; list-style: none; margin: 0; padding: 0; }
	
/* Single Tab */
ul.wpatabs li { cursor: pointer; float: left; font-size: 13px !important; font-weight: bold; margin: 0; padding: 5px 10px; }
	
/* Tabs Link */
ul.wpatabs li:hover { display: block; line-height: 30px; position: relative; }
ul.wpatabs a:active { outline: none !important; }
ul.wpatabs a:hover { cursor: pointer; }

/* Current Tab */
ul.wpatabs li.current, ul.wpatabs li.current:hover, ul.wpatabs li.current { outline: none !important; }

/* Other Auctions */
.pane-other { padding: 0; }
ul#wp-othercontainer-p { height: 194px; overflow: auto; margin: 10px; }
ul#wp-othercontainer-p li { height: 54px; margin: 0 0 10px; padding: 0; }
ul#wp-othercontainer-p li p { padding: 5px 0 0; }
ul#wp-othercontainer-p li p.wpa-other-title { font-size: 13px !important; font-weight: bold; }
ul#wp-othercontainer-p li img { float: left; margin: 0 10px 0 0; padding: 1px; }
ul#wp-othercontainer-p li:hover { }

#wp-powered-p { bottom: 1px; font-size: 9px !important; position: absolute; right: 1px; }
#wp-powered-p a { }
</style>

<form id="auction_form">

<div id="wp-container-p">

	<div id="wp-header-p" class="clearfix">
	
		<div id="wp-logo-p">
			<h2><?php echo $title ?></h2>
		</div><!-- Title Ends -->

		<div id="wp-close-p">
			<a href="Javascript:get_rss();">Auctions RSS feed <img src="<?php echo $filepath ?>rss.png" alt="Auctions RSS" border="0" /></a> [<a href="#" onclick="tb_remove()" title="close window">Close Window</a>]
		</div><!-- RSS/Close Ends -->
	
	</div><!-- Header Ends -->

	<div id="wp-top-p" class="clearfix">
	
		<div class="wpa-image">
			<div id="wp-image-p">
				<img src="<?php echo $filepath ?>test_image.gif" alt="Loading Image..." width="250" height="250" />
			</div><!-- Auction Image Ends -->
			
			<div id="wp-currentbid-p"><div id="wp_price">Current Bid:</div></div>
			<p id="wp-refreshbid-p">[ <a href="#" onclick="ajax_auction_request();">Refresh</a> ]</p>
		</div><!-- Image Ends -->

		<div id="wp-content-p" class="clearfix">
			
			<div class="wpa-description">
				<h3 id="tc-heading-p">Loading Auction...</h3>
			
				<div id="wp-description-p">
				
				</div><!-- Description Ends -->
			</div>
			
			<ul class="wpa-details">
				<li><div id="wp_endd"><strong>Ending Date:</strong><br /></div></li>
				<li><div id="wp_startb"><strong>Starting Bid:</strong><br /></div></li>
				<li><div id="wp_winningb"><strong>Winning Bid:</strong><br /></div></li>
			</ul>
		
		</div><!-- Content Ends -->
	
	</div><!-- Top Ends -->

	<div id="wp-bottom-p" class="clearfix">
	
		<div id="wp-left-p">	
			<div id="wp-details-p">
				<h3>Enter Your Details To Bid <p class="spinner">*required</p></h3>
								
			<?php if ($hidebid == "Yes") echo '<div style="display:none;">'; ?>
				
				<table border="0" cellpadding="0">
				<tr class="bidder-name">
					<td><p>Name*</p></td>
					<td><input name="Name" type="text" class="forminput" id="Name" value="<?php echo $defaultname; ?>" /></td>
				</tr>
				<tr class="bidder-email">
					<td><p>Email*</p></td>
					<td><input name="Email" type="text" class="forminput" id="Email" value="<?php echo $defaultemail; ?>" /></td>
				</tr>
				<tr class="bidder-url">
					<td><p>URL</p></td>
					<td><input name="URL" type="text" class="forminput" id="URL" value="<?php echo $defaulturl; ?>" /></td>
				</tr>
				</table>

			<?php if ($hidebid == "Yes") echo "</div>"; ?>

        </div><!-- Details Ends -->

        <div id="wp-bid-p">
			<?php if ($hidebid == "Yes") echo '<div style="display:none;">'; ?>
				<h3>Enter Your Maximum Bid <span id="wp-extrainfo"></span></h3>
				  
					<table border="0" cellpadding="0">
				  	<tr>
						<td class="wpa-currency"><input type="hidden" id="formauctionid" name="formauctionid" value="<?php echo $auction ?>"><input type="hidden" id="currencysymbol" name="currencysymbol" value="<?php echo $currencysymbol ?>"> <p class="currency"><?php echo $currencysymbol ?></p></td>
						<td class="wpa-bidamount"><div id="wp-bin-manip"><input name="BidAmount" type="text" class="formbid" id="BidAmount" value="" maxlength="8" align="right"/><input name="BINAmount" type="hidden" id="BINAmount" value="0"/></div></td>
						<td class="wpa-bidnow"><p class="bidnow" id="bidnow"><a href="#" onclick="ajax_submit_bid();">Bid Now</a></p></td>
				  	</tr>
				  	</table>
			<?php if ($hidebid == "Yes") echo "</div>"; ?>
	 				 
		</div><!-- Bid Ends -->

		<input type="hidden" id="filepath" name="filepath" value="<?php echo $filepath ?>">
				  
	</div><!-- Left Ends -->
		
		<div class="wpa-tabs">
		
			<ul class="wpatabs">
				<li>Current Bids</li>
				<li>Other Auctions</li>
			</ul>
				
			<div id="wp-right-p" class="wpa-pane pane-bids">	
				<div id="wp-bids-p">  
					<ol class="wp-detailsbidders-p">
						<li>Loading bids ...</li>
					</ol>
				</div>	  
			</div><!-- Right Ends -->
			
			<div id="wp-other-p" class="wpa-pane pane-other">			
				<ul id="wp-othercontainer-p">
					<li><a href="#"><img src="../wp-content/plugins/wp-auctions/requisistes/wp-thumb-def.gif" alt="Auction Image" width="50" height="50" /></a></li>
					<li><a href="#"><img src="../wp-content/plugins/wp-auctions/requisistes/wp-thumb-def.gif" alt="Auction Image" width="50" height="50" /></a></li>
				</ul>
			</div><!-- Other Auctions Ends -->
			<div id="wp-powered-p">Powered by <a href="http://www.wpauctions.com" target="_blank">WP Auctions</a></div>
		</div><!-- WPA Tabs -->
	
	</div><!-- Bottom Ends -->

</div><!-- Container Ends -->

</form>

<script type="text/javascript">
jQuery(document).ready(function(){
  ajax_auction_request();
});
jQuery(function() { jQuery("ul.wpatabs").tabs(".wpa-pane", {effect:'fade'}); });
</script>