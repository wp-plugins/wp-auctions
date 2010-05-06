jQuery(document).ready(function() {

jQuery('.upload_image_button').click(function() {
 id = jQuery(this).attr('id').substring(13);
 formfield = jQuery('#upload_image').attr('name');
 tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
 return false;
});

window.send_to_editor = function(html) {
 imgurl = jQuery('img',html).attr('src');
 jQuery('#upload_image_'+id).val(imgurl);
 tb_remove();
}

});