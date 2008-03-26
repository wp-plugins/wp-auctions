<?php if (!function_exists('get_option'))

require_once('../../../wp-config.php'); 

$options = get_option('wp_auctions');
$style = $options['style'];
$currencysymbol = $options['currencysymbol'];
$title = $options['title'];

$filepath = get_bloginfo('wpurl').'/wp-content/plugins/wp_auctions/styles/'.$style.'/popup/images/';

// Get auction to show

$auction=intval($_GET['ID']);

?>
<form id="auction_form">
<div id="wp-container-p">
  <!--Header Area Starts-->
  <div id="wp-logo-p">
    <!--Auction Heading Comes Here-->
    <span class="wp-logotext-p"><?php echo $title ?></span>
    <!--Auction Heading Ends Here-->
    <!--Close Window Starts-->
    <span class="wp-closew-p">Auctions RSS feed&nbsp; <a href="Javascript:get_rss();"><img src="<?php echo $filepath ?>feed-icon.png" alt="RSS feed for my auctions" width="14" height="14" border="0" /></a>&nbsp; <a href="#" class="lbAction" rel="deactivate" title="close window">close window</a></span>
    <!--Close Window Ends-->
  </div>
  <!--Header Area Ends-->
  <!--Main Bid Area Begins-->
  <div id="wp-main-p">
    <!--Top Bid Area Begins-->
    <div id="wp-topbox-p">
      <div id="wp-topbg-p">
        <!--This DIV is for background on the top-->
      </div>
      <!--Main Top Box Starts Here-->
      <div id="wp-topboxmid-p">
        <!--Main Top Content Starts Here-->
        <div id="wp-tc-p">
          <!--Heading Starts-->
          <div id="wp-tc-heading-p">Auction Heading</div>
          <!--Heading Ends-->
          <!--Auction Description Starts-->
          <div id="wp-tc-description-p">
		    <ol>
			  <li><span class="wp-liststyle-p"><div id="wp_endd"><strong>Ending Date:</strong></div></span></li>
			  <li><span class="wp-liststyle-p"><div id="wp_startb"><strong>Starting Bid:</strong></div></span></li>
			  <li><span class="wp-liststyle-p"><div id="wp_winningb"><strong>Winning Bid:</strong></div></span></li>
	        </ol>
            <div id="wp_desc"></div>
          </div>
		  <!--Refresh Bar Starts-->
          <div id="wp-refreshbar-p">
		  <!--Current Bid Container Starts-->
		  <div id="wp-currentbid-p">
		  <ul class="rightbid">
         <li><a href="#" title="Current Bid"><span><div id="wp_price">Current Bid:</div></span></a></li>
	     </ul>
		  </div>
		  <!--Current Bid Container Ends-->
		  
		  <!--Right Container Starts-->
		  <div id="wp-refreshright-p"><a href="#"><img src="<?php echo $filepath ?>refresh.png" alt="Refresh Current Bid" title="Refresh Current Bid" width="35" height="35" border="0"  onclick="ajax_auction_request();"/></a></div>
		  <!--Right Container Ends-->
		  
		  <!--Ending Date Starts-->
		 <div id="wp-refreshcurrentbid-p">
		    <p>Refresh Current Bid:</p>
		  </div>
		  <!--Ending Date Ends-->
		  
		  </div>
		  <!--Refresh Bar Ends-->
		  
          <!--Auction Description Ends-->
        </div>
        <!--Main Top Content Ends Here-->
        <!--Image Starts Here-->
        <div id="wp-topimg-p"><img src="<?php echo $filepath ?>test_image.gif" alt="My Auction Image" width="190" height="190" /> </div>
        <!--Image Ends Here-->
      </div>
      <!--Main Top Box Ends Here-->
      <div id="wp-bottombg-p">
        <!--This DIV is for background on the bottom-->
      </div>
    </div>
    <!--Top Bid Area Ends-->
	
	    <!--Bottom Bid Area Begins-->

    <div id="wp-bottombox-p">
      <div id="wp-bottomtopbg-p">
        <!--This DIV is for background on the top-->
      </div>
      <!--Main Bottom Box Starts Here-->
      <div id="wp-bottomboxmid-p">

	  <!--Left Details Box Starts Here-->
	  <div id="wp-leftbottom-p">
	  Enter Details
	  <div id="wp-detailsform-p">
	  <table width="259" border="0" cellpadding="0">
  <tr>
    <td width="59" align="right" valign="middle">*Name:</td>
    <td width="200" align="center" valign="middle"><input name="Name" type="text" class="forminput" id="Name" /></td>
  </tr>
  <tr>
    <td width="59" align="right" valign="middle">*Email:</td>
    <td width="200" align="center" valign="middle"><input name="Email" type="text" class="forminputemail" id="Email" /></td>
  </tr>
  <tr>
    <td width="59" align="right" valign="middle">URL:</td>
    <td width="200" align="center" valign="middle"><input name="URL" type="text" class="forminputurl" id="URL" /></td>
  </tr>
</table>
	  </div>
	  
	  <!--Bid Area Starts Here-->
	  Enter Maximum Bid
	  <div id="wp-detailsbid-p">
	    <table width="259" height="34" border="0" cellpadding="0">
          <tr>
            <td width="46" align="left" valign="middle"><input type="hidden" id="formauctionid" name="formauctionid" value="<?php echo $auction ?>"><input type="hidden" id="currencysymbol" name="currencysymbol" value="<?php echo $currencysymbol ?>">

                 <span class="currency"><?php echo $currencysymbol ?></span></td>
            <td width="46" align="left" valign="middle"><input name="Bid Amount" type="text" class="formbid" id="Bid Amount" value="" maxlength="8" align="right"/></td>
            <td width="165" align="center" valign="middle"><div id="wp-bidnow-p"><a href="#" onclick="ajax_submit_bid();">Bid Now</a></Div> </td>
          </tr>
        </table>
	  </div>

	  <!--Bid Area Ends Here-->
	  <div style="width:259px; font-size: 10px;">*required for bidding</div>

	  </div>
	  <!--Left Details Box Ends Here-->
	  
	  <!--Right Details Box Starts Here-->
	  <div id="wp-rightbottom-p">
	  Past Bids
	  <!--Bidder Names Starts Here-->
	  <div id="wp-detailsbidders-p">
	  <ol class="wp-detailsbidders-p">
	  </ol>
	  </div>
	  <!--Bidder Names Ends Here-->
	  
	  <!--Other Auctions Starts Here-->
	  <div id="wp-otherauctions-p">
	  <div class="wp-otherheading-p">My Other Auctions</div>
      <input type="hidden" id="filepath" name="filepath" value="<?php echo $filepath ?>">
	  <div id="wp-othercontainer-p">	  
	  <!--Main Container To Show Other Auctions Starts Here-->
	  
	  <!--Main Container To Show Other Auctions Ends Here-->
	  </div>
	  </div>
	  <!--Other Auctions Ends Here-->
	  	  <div id="wp-powered-p">Powered By <a href="http://www.wpauctions.com" target="_blank">WP Auctions</a></div>
	  </div>
	  <!--Right Details Box Ends Here-->
	  
	  </div>
      <!--Main Bottom Box Ends Here-->
      <div id="wp-bottombg-p">
        <!--This DIV is for background on the bottom-->
      </div>
    </div>
    <!--Bottom Bid Area Ends-->
	
  </div>
  <!--Main Bid Area Ends-->
</div>
<!--Main PopUp Container Ends-->
</form>

<script type="text/javascript">

  ajax_auction_request();

</script>