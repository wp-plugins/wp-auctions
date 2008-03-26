<?php

if ($_POST['MAX_FILE_SIZE'] > 0)  {

	//specify either 'post' upload OR upload of a 'file' on webserver:
	$uploadType = 'post';

	if($uploadType == "post"){

	/* Sample upload form to use:
		<form method="post" action="xmlapi.php" enctype="multipart/form-data">
		<input type="hidden" name="MAX_FILE_SIZE" value="1048576">
		<input type="file" name="fileupload" size="30">
		<input style="width: 100px;" type="submit" value="host it!" >
		</form>
	*/

		if(!$_FILES[fileupload]){ exit; }
		$source = $_FILES[fileupload][tmp_name];
		$dest = '/tmp/'.$_FILES[fileupload][name];
		copy($source,$dest);
		$xmlString = uploadToImageshack($dest);
		unlink($source); unlink($dest);
	
	} elseif($uploadType == "file"){
	
	//specify location of file
		$dest = '/home/image/www/creative.jpg';
		$xmlString = uploadToImageshack($dest);
	
	}

	//begin parsing xml data

	if ($xmlString == 'failed') { echo "XML return failed"; exit; }
	
	$xmlData = explode("\n",$xmlString);
	
	foreach($xmlData as $xmlDatum){
	
		$xmlDatum = trim($xmlDatum);
	
		if($xmlDatum != "" && !eregi("links",$xmlDatum) && !eregi("xml",$xmlDatum)){
	
			$xmlDatum = str_replace(">","<",$xmlDatum);
			list($xmlNull,$xmlName,$xmlValue) = explode("<",$xmlDatum);
			$xmlr[$xmlName] = $xmlValue;
	
		}

	}
	
	/*-----------------------------------------------------------------------------
	available variables:
	image_link: link to image, like: http://img214.imageshack.us/img214/7053/creative0cj.jpg
	thumb_link: link to image thumbnail, like: http://img214.imageshack.us/img214/7053/creative0cj.th.jpg
	ad_link: link to imageshack page on which image is displayed, like: http://img214.imageshack.us/my.php?image=creative0cj.jpg
	thumb_exists: specifies whether thumb exists, either 'yes' or 'no'
	total_raters: specifies how many people rated image, numerical string
	ave_rating: specifies the average rating value, numericl string between 1 and 10
	image_location: internal-style link to image, like: img214/7053/creative0cj.jpg
	thumb_location: internal-style link to image thumbnail, like: img214/7053/creative0cj.th.jpg
	server: server name on which image resides, like: img214
	image_name: filename of image after it has been uploaded, like: creative0cj.jpg
	done_page: link to imageshack page on which users can get linking code, like: http://img214.imageshack.us/content.php?page=done&l=img214/7053/creative0cj.jpg
	resolution: pixel resolution of image, like: 300x250
	------------------------------------------------------------------------------*/

?>	
<script language="Javascript">
function KeepMe() {
      
	// update calling form
	opener.document.forms[0].wpa_ImageURL.value = '<?php print $xmlr["image_link"] ?>';
	opener.document.forms[0].wpa_ThumbURL.value = '<?php print $xmlr["thumb_link"] ?>';
    
    //close window
    self.close(); 
    return false;    
} 
</script>

<?php		

	//sample return
	echo   'Upload successful!<br /><br />
		<a href="'.$xmlr["ad_link"].'"><img src="'.$xmlr["thumb_link"].'" border="0" /></a>
		<br /><br />
		Resolution: '.$xmlr["resolution"].'.<br /><br />';
	echo '<a href="Javascript:KeepMe()">Keep this image</a> or <a href="IShack_upload.php">upload another</a>.';
	

}	
else {
?>

<form method="post" action="IShack_upload.php" enctype="multipart/form-data">
		<input type="hidden" name="MAX_FILE_SIZE" value="1048576">
		<input type="file" name="fileupload" size="30">
		<input style="width: 100px;" type="submit" value="host it!" >
		</form>

Please note that it may take up to a minute for your image to load and be processed. Please be patient.
<hr>
<img src="http://img211.imageshack.us/img211/5407/imageshackek3.gif" align="left"> This image upload facility is powered by <a href="http://imageshack.us/">ImageShack</a>. Please adhere to their <a hef="http://reg.imageshack.us/content.php?page=rules">Terms and Conditions</a>.
<?php
}

	
	//two functions, one for uploading from from file, the other for uploading from url, editing below this line advised only to those who know what they are doing :)
	
		function uploadToImageshack($filename) {
				$ch = curl_init("http://www.imageshack.us/index.php");

				$post['xml']='yes';
				$post['fileupload']='@'.$filename;
				$post['rembar'] = 'yes';

				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 240);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect: '));

				$result = curl_exec($ch);
				curl_close($ch);

				if (strpos($result, '<'.'?xml version="1.0" encoding="iso-8859-1"?>') === false) {
						return 'failed';
				} else {
						return $result; // XML data
				}
		}

		function uploadURLToImageshack($url) {
				$ch = curl_init("http://www.imageshack.us/transload.php");

				$post['xml']='yes';
				$post['url']=$url;
				$post['rembar'] = 'yes';

				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect: '));

				$result = curl_exec($ch);
				curl_close($ch);

				if (strpos($result, '<'.'?xml version="1.0" encoding="iso-8859-1"?>') === false) {
						return 'failed';
				} else {
						return $result; // XML data
				}
		}

?>