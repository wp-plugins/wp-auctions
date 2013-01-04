<?php 

// cater for stand-alone calls
if (!function_exists('get_option'))
	require_once('../../../wp-config.php');

$wpa_version = "2.0 Lite";

// set up security
$nonce= wp_create_nonce('WPA-nonce');

// Consts
if (!defined('PLUGIN_NAME')) {
   define('PLUGIN_EXTERNAL_PATH', '/wp-content/plugins/wp-auctions/');
   define('PLUGIN_STYLE_PATH', 'wp-content/plugins/wp-auctions/styles/');
   define('PLUGIN_NAME', 'wp_auctions.php');
   define('PLUGIN_PATH', 'wp-auctions/wp_auctions.php');

   define('BID_WIN', 'Congratulations, you are the highest bidder on this item.');
   define('BID_LOSE', "I'm sorry, but a preceeding bidder has outbid you.");
}

header("Content-Type:text/javascript"); ?>
// Popup front-end code

// This code needs to be refactored to consolidate all the similar routines

// AJAX Functions
// Functions are all seperate so we could do different funky stuff with each

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

//Ajax.Responders.register({
//    onCreate: function(){ Element.show('spinner')},
//    onComplete: function(){Element.hide('spinner')}
//});

function ajax_others_loading(on) {
   if (on) {
      ajax_other_loading = true;
      // do funky stuff here
   } else {
      // clear funky stuff here
      ajax_other_loading = false;
   }
}

function swap_image(url) {
  jQuery('#wp-image-p').fadeOut("slow",function() { 
     jQuery('#wp-image-p').html('<img src="' + url +'" alt="Loading image ..." width="250" height="250" />');
  } );  
  jQuery('#wp-image-p').fadeIn();
}


function ajax_auction_request() {

   // retreive form data
   var auction_id = jQuery("input#formauctionid").val(); 
   var currencysymbol = jQuery("input#currencysymbol").val();

   if (ajax_auction_loading) return false;
   
   ajax_auctions_loading ( true );
   
   // new jQuery AJAX routine
   jQuery.ajax ({
      cache: false,
      type: "POST",
      url: '<?php echo get_option('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME; ?>?queryauction',
      data : {
         auction_ID : auction_id,
         _ajax_nonce: '<?php echo $nonce ?>'
      },
      error: function(request,textStatus,errorThrown) {
	    alert((request.status!=406? ' WP_Auction Error '+request.status+' : '+request.statusText+'\n' : '')+request.responseText);
      },
   success: function(request, status) {
	    ajax_auctions_loading(false);   
	    if (status!="success") alert (status);  //"return"
	    	    
	    // update auction on screen
	    auction_details = request.split('|');

	    // process BIN if there is one (note: only if auction isn't closed)
      extraBIN = "";

      // process extra images if there are any
      extraimages = '';
      thisimage = 1;
      for(var i=0;i<3;i++) {
         if (auction_details[12+i] != '') { 
            if (extraimages != '') {extraimages = extraimages + ", "; }
         
            swapurl = 'Javascript:swap_image("' + auction_details[12+i] + '")';
            extraimages = extraimages + "<a href='" + swapurl + "'>#" + thisimage++ + "</a>" 
         }
      }
      
      // if we DO have extra images, let's append the main image to the end of the list
      if (extraimages != '' && auction_details[7] != "") {
         swapurl = 'Javascript:swap_image("' + auction_details[7] + '")';
         extraimages = "<strong>More Images:</strong> " + extraimages + ", <a href='" + swapurl + "'>#" + thisimage + "</a>"       
      }

      // reset value field to form (in case previous BIN messed with this)
      jQuery('#wp-bin-manip').html('<input name="BidAmount" type="text" class="formbid" id="BidAmount" value="" maxlength="8" align="right"/><input name="BINAmount" type="hidden" id="BINAmount" value="0"/>');

      jQuery('#wp_startb').html("<strong>Starting Bid:</strong> " + currencysymbol+auction_details[6]);
      jQuery('#wp-extrainfo').html('<font size="-2">Bid ' + currencysymbol + auction_details[15] + ' or higher</font>');
	    
      jQuery('#wp-description-p').html(auction_details[2]);
	    jQuery('#tc-heading-p').html(auction_details[1]);
	    jQuery('#wp_price').html("Current Bid: " + currencysymbol + auction_details[3]);
	    	    
	    if (auction_details[7] == "") { auction_details[7]='<?php echo get_option('siteurl').PLUGIN_EXTERNAL_PATH; ?>/requisites/wp-popup-def.gif'   }

      jQuery('#wp-image-p').fadeOut("slow",function() { 
         jQuery('#wp-image-p').html('<img src="'+auction_details[7]+'" alt="Loading image ..." width="250" height="250" />');
      } );  
      jQuery('#wp-image-p').fadeIn();

      // Check if auction is still open      
      if (auction_details[8] == 0) {
         // auction is closed
         jQuery('#wp_endd').html("Auction Ended");
         jQuery("#BidAmount").attr("disabled",true);
         jQuery('#wp-bidnow-p').html('');
         jQuery('#wp_winningb').html('<strong>Winning Bid:</strong> ' + currencysymbol + auction_details[10] + ' by ' + auction_details[9]);
      } else {
         // auction is open
         jQuery('#wp_endd').html("<strong>Ending Date:</strong> "+auction_details[5]);
         jQuery("#BidAmount").attr("disabled",false);
         jQuery('#bidnow').html('<a href="#" onclick="ajax_submit_bid();">Bid Now</a>');
         if (extraimages + extraBIN == '') {
            jQuery('#wp_winningb').html('<strong>Winning Bid:</strong> Bid to win');
         } else {
            jQuery('#wp_winningb').html(extraBIN + "  " + extraimages);
         }
         
      }
      
      }
   });
	 
     // fire off call to update bids
     ajax_bids_request(auction_id);

     // fire off call to update other auctions
     ajax_other_request(auction_id);

	 return false;
}

function ajax_bids_request(auction_id) {

   var currencysymbol = jQuery("input#currencysymbol").val();  
   
   if (ajax_bid_loading) return false;
   ajax_bids_loading ( true );

   // new jQuery AJAX routine
   jQuery.ajax ({
      cache: false,
      type: "POST",
      url: '<?php echo get_option('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME; ?>?querybids',
      data : {
         auction_ID : auction_id,
         _ajax_nonce: '<?php echo $nonce ?>'
      },
      error: function(request,textStatus,errorThrown) {
	    alert((request.status!=406? ' WP_Auction Error '+request.status+' : '+request.statusText+'\n' : '')+request.responseText);
      },
   success: function(request, status) {

	    ajax_bids_loading(false);   

	    if (status!="success") alert (status);  //"return"
	    
	    // update bids on screen
        if (request == '') {
           var bid_output = 'No bids found';
        } else {
           bids_details = request.split('|');

           var bid_output = '<ol class="wp-detailsbidders-p">';
           var lines = (bids_details.length/4)-1;
	       for(var i=0;i<lines;i++) {
              bid_output = bid_output + '<li>';
              if (bids_details[i*4+2]=="") {
                 bid_output = bid_output + bids_details[i*4+1];
              } else {
                 bid_output = bid_output + '<a href="' + bids_details[i*4+2] + '" target="_blank">' + bids_details[i*4+1] + '</a>';
              }
              bid_output = bid_output + ' bid ' + currencysymbol + bids_details[i*4+4] + ' on ' + bids_details[i*4+3];
              bid_output = bid_output + '</li>';
           }
	       bid_output = bid_output + '</ol>';
        }   

        jQuery('#wp-bids-p').slideUp("slow",function() { 
           jQuery('#wp-bids-p').html(bid_output); 
        });
        jQuery('#wp-bids-p').slideDown();

	 }})
	 
	 return false;
}


function ajax_other_request(auction_id) {

   if (ajax_other_loading) return false;
   ajax_others_loading ( true );
   
   // new jQuery AJAX routine
   jQuery.ajax ({
      cache: false,
      type: "POST",
      url: '<?php echo get_option('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME; ?>?queryother',
      data : {
         auction_ID : auction_id,
         _ajax_nonce: '<?php echo $nonce ?>'
      },
      error: function(request,textStatus,errorThrown) {
   	    alert((request.status!=406? ' WP_Auction Error '+request.status+' : '+request.statusText+'\n' : '')+request.responseText);
      },
   success: function(request, status) {

	    ajax_others_loading(false);   

	    if (status!="success") alert (status);  //"return"
	    
	    // update others on screen - returns multiples of 6, max 24

      if (request == "") {
         jQuery('#wp-other-p').html(''); 
      } else {
      
        other_details = request.split('|');
        
          odetdiv = '';
          for(var i=0;i<4;i++) {
             if (other_details[i*6+3] != undefined) {
                if (other_details[i*6+3] == '') {
                   odetdiv = odetdiv + '<li><a href="#" title="' + other_details[i*6+2] + '">';  
                   odetdiv = odetdiv + '<img src="<?php echo get_option('siteurl').PLUGIN_EXTERNAL_PATH; ?>/requisites/wp-thumb-def.gif" border="0" alt="' + other_details[i*6+2] + '" width="50" height="50" onclick="document.getElementById(\'formauctionid\').value=' + other_details[i*6+1] + ';ajax_auction_request()"/>'; 
                   odetdiv = odetdiv + '</a><p>'+other_details[i*6+2]+'</p><p>Current Bid: '+other_details[i*6+5]+'</p></li>';  
                }
                else {
                   odetdiv = odetdiv + '<li><a href="#" title="' + other_details[i*6+2] + '">';  
                   odetdiv = odetdiv + '<img src="' + other_details[i*6+3] + '" border="0" alt="' + other_details[i*6+2] + '" width="50" height="50" onclick="document.getElementById(\'formauctionid\').value=' + other_details[i*6+1] + ';ajax_auction_request()"/>';  
                   odetdiv = odetdiv + '</a><p class="wpa-other-title"><a href="#" title="' + other_details[i*6+2] + '" onclick="document.getElementById(\'formauctionid\').value=' + other_details[i*6+1] + ';ajax_auction_request()">'+other_details[i*6+2]+'</a></p><p>Current Bid: '+other_details[i*6+6]+'</p></li>';  
                }
             } else {
                // Should be nothing here .. let's see how it goes ..
             }
          }
   
          jQuery('#wp-other-p').html('<ul id="wp-othercontainer-p">' + odetdiv + '</ul>');
     }

	 }})
	 
	 return false;
}


function ajax_submit_bid() {
 
   // retreive form values
   var auction_id = jQuery("input#formauctionid").val(); 
   var bidder_name = jQuery("input#Name").val();
   var bidder_email = jQuery("input#Email").val();
   var bidder_url = jQuery("input#URL").val();
   var max_bid = jQuery("input#BidAmount").val();

   // new jQuery AJAX routine
   jQuery.ajax ({
      cache: false,
      type: "POST",
      url: '<?php echo get_option('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME; ?>?postauction',
      data : {
         auction_id   : auction_id,
         bidder_name  : bidder_name,
         bidder_email : bidder_email,
         bidder_url   : bidder_url,
         max_bid      : max_bid,
         _ajax_nonce: '<?php echo $nonce ?>'
      },
      error: function(request,textStatus,errorThrown) {
   	    alert((request.status!=406? ' WP_Auction Error '+request.status+' : '+request.statusText+'\n' : '')+request.responseText);
      },
   success: function(request, status) {

	    if (status!="success") alert (status);  //"return"

      // give user their response
      alert ( request );

       // fire off call to update auction details
       ajax_auction_request(auction_id);
	 }})
	 
	 return false;
}

function get_rss() {
   window.location = "<?php echo get_option('siteurl').PLUGIN_EXTERNAL_PATH.PLUGIN_NAME; ?>?rss";
}


// Tabs function added by Hyder May 1st, 2010

(function(d){d.tools=d.tools||{};d.tools.tabs={version:"1.0.4",conf:{tabs:"a",current:"current",onBeforeClick:null,onClick:null,effect:"default",initialIndex:0,event:"click",api:false,rotate:false},addEffect:function(e,f){c[e]=f}};var c={"default":function(f,e){this.getPanes().hide().eq(f).show();e.call()},fade:function(g,e){var f=this.getConf(),j=f.fadeOutSpeed,h=this.getPanes();if(j){h.fadeOut(j)}else{h.hide()}h.eq(g).fadeIn(f.fadeInSpeed,e)},slide:function(f,e){this.getPanes().slideUp(200);this.getPanes().eq(f).slideDown(400,e)},ajax:function(f,e){this.getPanes().eq(0).load(this.getTabs().eq(f).attr("href"),e)}};var b;d.tools.tabs.addEffect("horizontal",function(f,e){if(!b){b=this.getPanes().eq(0).width()}this.getCurrentPane().animate({width:0},function(){d(this).hide()});this.getPanes().eq(f).animate({width:b},function(){d(this).show();e.call()})});function a(g,h,f){var e=this,j=d(this),i;d.each(f,function(k,l){if(d.isFunction(l)){j.bind(k,l)}});d.extend(this,{click:function(k,n){var o=e.getCurrentPane();var l=g.eq(k);if(typeof k=="string"&&k.replace("#","")){l=g.filter("[href*="+k.replace("#","")+"]");k=Math.max(g.index(l),0)}if(f.rotate){var m=g.length-1;if(k<0){return e.click(m,n)}if(k>m){return e.click(0,n)}}if(!l.length){if(i>=0){return e}k=f.initialIndex;l=g.eq(k)}if(k===i){return e}n=n||d.Event();n.type="onBeforeClick";j.trigger(n,[k]);if(n.isDefaultPrevented()){return}c[f.effect].call(e,k,function(){n.type="onClick";j.trigger(n,[k])});n.type="onStart";j.trigger(n,[k]);if(n.isDefaultPrevented()){return}i=k;g.removeClass(f.current);l.addClass(f.current);return e},getConf:function(){return f},getTabs:function(){return g},getPanes:function(){return h},getCurrentPane:function(){return h.eq(i)},getCurrentTab:function(){return g.eq(i)},getIndex:function(){return i},next:function(){return e.click(i+1)},prev:function(){return e.click(i-1)},bind:function(k,l){j.bind(k,l);return e},onBeforeClick:function(k){return this.bind("onBeforeClick",k)},onClick:function(k){return this.bind("onClick",k)},unbind:function(k){j.unbind(k);return e}});g.each(function(k){d(this).bind(f.event,function(l){e.click(k,l);return false})});if(location.hash){e.click(location.hash)}else{if(f.initialIndex===0||f.initialIndex>0){e.click(f.initialIndex)}}h.find("a[href^=#]").click(function(k){e.click(d(this).attr("href"),k)})}d.fn.tabs=function(i,f){var g=this.eq(typeof f=="number"?f:0).data("tabs");if(g){return g}if(d.isFunction(f)){f={onBeforeClick:f}}var h=d.extend({},d.tools.tabs.conf),e=this.length;f=d.extend(h,f);this.each(function(l){var j=d(this);var k=j.find(f.tabs);if(!k.length){k=j.children()}var m=i.jquery?i:j.children(i);if(!m.length){m=e==1?d(i):j.parent().find(i)}g=new a(k,m,f);j.data("tabs",g)});return f.api?g:this}})(jQuery);