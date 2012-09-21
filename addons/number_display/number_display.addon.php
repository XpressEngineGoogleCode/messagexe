<?php
if(!defined('__XE__')) exit();

/**
 * @file resize_image.addon.php
 * @author NHN (developers@xpressengine.com)
 * @brief Add-on to resize images in the body
 **/

if(Context::get('act') == 'dispMemberAdminList' && $called_position == 'after_module_proc' && Context::getResponseMethod() == "HTML") {
	Context::loadFile('./addons/number_display/css/style.css', true);
	Context::loadFile(array('./addons/number_display/js/number_display.js', 'body', '', null), true);
}
?>
