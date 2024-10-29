<?php
/**
 * @package appmakr-comment
 * @author MDE Development, LLC
 * @version 0.0.2
 */
/*
Plugin Name: AppMakr-Comment
Plugin URI: http://wordpress.org/#
Description: This plugin was created to make it easy to accept comments from AppMakr
Author: MDE Development, LLC
Version: 0.0.2
Author URI: http://mde-dev.com
License: GPL
*/
#
# INSTALLATION: see readme.txt
#
# USAGE: Once the AppMakr Comment plugin has been installed, you can set the author 
#        via Settings -> AppMakr Comments in the  admin area. 
#      
#        Once enabled we will check to see if there is a message in the post parameter "message" and 
#				 that there is a GET parameter of comment_post_ID that is an integer greater than zero


	define("FRONTEND_TEXT_CHECK", "appmakr-comment-pluginhere");
	define("OPTION_AUTHOR", "appmakr_comment_author");
	define("OPTION_MAX_IMAGE_WIDTH", "appmakr_max_image_width");
	define("OPTION_MAX_IMAGE_HEIGHT", "appmakr_max_image_height");
	define("OPTION_APPMAKR_DEBUG", "appmakr_debugging");
	define("OPTION_APPMAKR_DEBUG_EMAIL", "appmakr_debugging_email");
	define("DEFAULT_DIMENSION_SIZE", 350);
	
	function appmakr_comments_frontend_handler($text) {
		global $allowedtags;
		global $wpdb; 
		$bAddedImg = false;
		$bAddedBr = false;
		
		$baseImagePath = ABSPATH . 'wp-content/appmakr-image';

		if(!isset($_GET['comment_post_ID']) || !is_numeric($_GET['comment_post_ID']) || ($_GET['comment_post_ID'] <= 0)
		  || !isset($_POST['message']) || empty($_POST['message'])) {
			return;
		}
		
		// This change is made so we can get around some spam functions that are used
		// sometimes to prevent comment spam that people write in their templates.  
		if(!isset($_SERVER['HTTP_REFERER']) || empty($_SERVER['HTTP_REFERER'])) {
			$_SERVER['HTTP_REFERER'] = "AppMakr";
		}
		
		$comment_post_ID = (int) $_GET['comment_post_ID'];
		$comment_author = get_option(OPTION_AUTHOR);
		$comment_author = ((empty($comment_author)) ? "Mobile App" : $comment_author);
		//check if post exists
		$status = $wpdb->get_row( $wpdb->prepare("SELECT post_status, comment_status FROM $wpdb->posts WHERE ID = %d", $comment_post_ID) );
		if ( empty($status->comment_status) ) {
		   echo "No Post with that ID Exists";
		   exit;
		}

		//get comment info from POST param
		$comment_content = ( isset($_POST['message']) ) ? trim($_POST['message']) : null;
		
		if(!file_exists($baseImagePath)) {
			mkdir($baseImagePath);
		}
		
		// photo
		if(isset($_FILES['photo']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
			$dotIdx = strrpos($_FILES['photo']['name'], ".");
			if($dotIdx !== false) {
				$extension = strToLower(substr($_FILES['photo']['name'], $dotIdx + 1));
				if(($extension == "jpg") || ($extension == "jpeg") || ($extension == "gif") || ($extension == "gif")) {
					$fileName = uniqid().".$extension";
					
					if(move_uploaded_file($_FILES['photo']['tmp_name'], $baseImagePath."/".$fileName)) {
						$maxWidth = get_option(OPTION_MAX_IMAGE_WIDTH);
						$maxWidth = ((!empty($maxWidth) && is_numeric($maxWidth)) ? $maxWidth : DEFAULT_DIMENSION_SIZE);
						$maxHeight = get_option(OPTION_MAX_IMAGE_HEIGHT);
						$maxHeight = ((!empty($maxHeight) && is_numeric($maxHeight)) ? $maxHeight : DEFAULT_DIMENSION_SIZE);
						
						$size = getImageSize($baseImagePath."/$fileName");
						if(($size[0] > $maxWidth) || ($size[1] > $maxHeight)) {
							resizeImage($extension, $baseImagePath."/$fileName", $maxWidth, $maxHeight);
						}
						
						$comment_content .= "<br /><br /><img src=\"".get_option('siteurl')."/wp-content/appmakr-image/$fileName\" />";
					}					
				}
			}
		}
		
		
		if(!isset($allowedtags['br'])) {
			$allowedtags['br'] = array();
			$bAddedBr = true;
		}
		
		if(!isset($allowedtags['img'])) {
			$allowedtags['img'] = array("src"=>array());
			$bAddedImg = true;
		}
		
		if((get_option(OPTION_APPMAKR_DEBUG) == "Y") && (get_option(OPTION_APPMAKR_DEBUG_EMAIL) != "")) {
			mail(get_option(OPTION_APPMAKR_DEBUG_EMAIL), "AppMakr Debugging Email before adding a comment", "Comment to add: ".$comment_content);
		}
		
		$commentdata = compact('comment_post_ID', 'comment_author', 'comment_content');	
		$comment_id = wp_new_comment( $commentdata );
		echo $comment_id;
		
		if((get_option(OPTION_APPMAKR_DEBUG) == "Y") && (get_option(OPTION_APPMAKR_DEBUG_EMAIL) != "")) {
			mail(get_option(OPTION_APPMAKR_DEBUG_EMAIL), "AppMakr Debugging Email comment added", "Comment Added: ".$comment_content."\r\nComment ID: $comment_id");
		}
		
		if($bAddedImg) {
			unset($allowedtags['img']);
		}
		
		if($bAddedBr) {
			unset($allowedtags['br']);
		}
		exit;
	}
	
	function resizeImage($extension, $file, $maxWidth, $maxHeight) {
		switch ($extension)
    {
        case 'jpeg':
        case 'jpg':
            $src_img = imagecreatefromjpeg($file);
            break;
        case 'gif':
            $src_img = imagecreatefromgif($file);
            break;
        case 'png':
            $src_img = imagecreatefrompng($file);
            break;
        // try with jpg for strange mime types
        default:
            $src_img = imagecreatefromjpeg($file);
    }

    if ($src_img === false) return false;

    $old_x = imagesx($src_img);
    $old_y = imagesy($src_img);

		if(($old_x < $old_y) && ($old_y > $maxHeight))
    {
        $thumb_h = $maxHeight;
        $thumb_w=$old_x*($maxHeight/$old_y);
    }
    else
    {
        if ($old_x > $old_y)
        {
            $thumb_w=$maxWidth;
            $thumb_h=$old_y*($maxHeight/$old_x);
        }
        if ($old_x < $old_y)
        {
            $thumb_w=$old_x*($maxWidth/$old_y);
            $thumb_h=$maxHeight;
        }
        if ($old_x == $old_y)
        {
            $thumb_w=$maxWidth;
            $thumb_h=$maxHeight;
        }
    }
    $dst_img = ImageCreateTrueColor($thumb_w,$thumb_h);
    imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);

    imagejpeg($dst_img, $file, 80);
    imagedestroy($dst_img);
    imagedestroy($src_img);
	}
	
	function appmakr_comments_admin_options() {
		$maxWidth = get_option(OPTION_MAX_IMAGE_WIDTH);
		$maxWidth = ((!empty($maxWidth)) ? $maxWidth : DEFAULT_DIMENSION_SIZE);
		
		$maxHeight = get_option(OPTION_MAX_IMAGE_HEIGHT);
		$maxHeight = ((!empty($maxHeight)) ? $maxHeight : DEFAULT_DIMENSION_SIZE);
	?>
		<div class="wrap">
			<h2>AppMakr Comments Options</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'appmakr-comment-option-group' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="<?php echo OPTION_AUTHOR; ?>">AppMakr Comment Author:</label></th>
						<td align="left"><input type="text" name="<?php echo OPTION_AUTHOR; ?>" id="<?php echo OPTION_AUTHOR; ?>" value="<?php echo htmlspecialchars(get_option(OPTION_AUTHOR)); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="<?php echo OPTION_MAX_IMAGE_WIDTH; ?>">Maximum Image Width:</label></th>
						<td align="left"><input type="text" name="<?php echo OPTION_MAX_IMAGE_WIDTH; ?>" id="<?php echo OPTION_MAX_IMAGE_WIDTH; ?>" 
							value="<?php echo htmlspecialchars($maxWidth); ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="<?php echo OPTION_MAX_IMAGE_HEIGHT; ?>">Maximum Image Height:</label></th>
						<td align="left"><input type="text" name="<?php echo OPTION_MAX_IMAGE_HEIGHT; ?>" id="<?php echo OPTION_MAX_IMAGE_HEIGHT; ?>" 
							value="<?php echo htmlspecialchars($maxHeight); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="<?php echo OPTION_APPMAKR_DEBUG_EMAIL; ?>">Email to send debugging emails to:</label></th>
						<td align="left"><input type="text" name="<?php echo OPTION_APPMAKR_DEBUG_EMAIL; ?>" id="<?php echo OPTION_APPMAKR_DEBUG_EMAIL; ?>" 
							value="<?php echo htmlspecialchars(get_option(OPTION_APPMAKR_DEBUG_EMAIL)); ?>" />
					</tr>
					<tr>
						<th scope="row" colspan="2">
							<input type="checkbox" id="<?php echo OPTION_APPMAKR_DEBUG; ?>" name="<?php echo OPTION_APPMAKR_DEBUG; ?>" value="Y" 
								<?php echo ((get_option(OPTION_APPMAKR_DEBUG) == "Y") ? " checked=\"checked\"" : ""); ?>
							/> <label for="<?php echo OPTION_APPMAKR_DEBUG; ?>">Debugging on</label>
						</th>
					</tr>
				</table>
				<input type="hidden" name="action" value="update" />
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
			</form>
		</div>
	<?php
	}
	
	function appmakr_comments_modify_menu() {
		
		add_options_page('AppMakr Comments Options',	//page title
	                   'AppMakr Comments Options',	//subpage title
	                   'manage_options',	//access
	                   'appmakr-comments-options',		//current file
	                   'appmakr_comments_admin_options'	//options function above
	                   );
	}
	
	function appmakr_comments_register_settings() {
		register_setting('appmakr-comment-option-group', OPTION_AUTHOR);
		register_setting('appmakr-comment-option-group', OPTION_MAX_IMAGE_HEIGHT);
		register_setting('appmakr-comment-option-group', OPTION_MAX_IMAGE_WIDTH);
		register_setting('appmakr-comment-option-group', OPTION_APPMAKR_DEBUG_EMAIL);
		register_setting('appmakr-comment-option-group', OPTION_APPMAKR_DEBUG);
	}
	
	add_action('admin_menu', 'appmakr_comments_modify_menu');
	add_action('admin_init', 'appmakr_comments_register_settings');
	add_filter('init', 'appmakr_comments_frontend_handler');
?>