<?php
/*
Plugin Name: Image Twinning
Description: Plugin for reenactment of images.
Version: 1.0.0
Author: Bojan Božić and Sergiu Gordea
Author URI: http://homepage.univie.ac.at/bojan.bozic
*/

/**
 * Creates an input form for image uploads
 */

function setup_page() {
	global $wpdb;
	
	$admin_url = get_admin_url();
	$upload_dir = wp_upload_dir()['basedir'];
	
	$page_content = "
		<head>
		<title>Image Twinning</title>
		</head>
		
		<form enctype=\"multipart/form-data\" action='" . $admin_url . "admin-post.php' method=\"post\">
		<input type='hidden' name='action' value='submit-form' />
		<input type='hidden' name='uploads_path' value='" . $upload_dir . "/image-twinning/'>
		<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"1000000\" />
		<table>
		<tr><td>Original image*:</td><td><input type='file' name='file1'></td></tr>
		<tr><td>Reenactment*:</td><td><input type='file' name='file2'></td></tr>
		</table>
		<hr>
		<table>
		<tr>Selection coordinates:</tr>
		<tr><td>X:</td><td><input type='text' name='x'></td><td>Width:</td><td><input type='text' name='width'></td></tr>
		<tr><td>Y:</td><td><input type='text' name='y'></td><td>Height:</td><td><input type='text' name='height'></td></tr>
		<tr><td>Thumbnail width*:</td><td><input type='text' name='th_width' value=300></td></tr>
		</table>
		<hr>
		<table>
		<tr><td>Name of painting*:</td><td><input type='text' name='txt_painting'></td></tr>
		<tr><td>Name of artist:</td><td><input type='text' name='txt_artist'></td></tr>
		<tr><td>Date of painting:</td><td><input type='text' name='txt_date'></td></tr>
		<tr><td>Short URL:</td><td><input type='text' name='txt_shorturl'></td></tr> 
		<tr><td>Categorization</td><td><input type='text' name='category'></td></tr> 
		</table>
		<hr>
		Orientation*:<br>
		<input type='radio' name='hv' value='Horizontal' checked>Landscape<br>
		<input type='radio' name='hv' value='Vertical'>Portrait
		<hr>
		Framing*:<br>
		<input type='radio' name='frame' value='Color Frame'>Color Frame<br>
		<input type='radio' name='frame' value='Standard Frame'>Standard Frame<br>
		<input type='radio' name='frame' value='Image Frame'>Image Frame<br>
		<input type='radio' name='frame' value='No Frame' checked> No Frame<br>
		Color code: <input type='text' name='color_code' value='#FFFFFF'><br>
		Thickness: <input type='text' name='thickness' value='5'><br>
		Frame Image: <input type='file' name='frame_image'><br>
		Top right corner: <input type='file' name='top_right'><br>
		<hr>
		* Mandatory
		<hr>
		<input type='submit' value='Load'>
		</form>";

    $the_page_title = 'Image Twinning';
    $the_page_name = 'image-twinning';

    // the menu entry...
    delete_option("my_plugin_page_title");
    add_option("my_plugin_page_title", $the_page_title, '', 'yes');
    // the slug...
    delete_option("my_plugin_page_name");
    add_option("my_plugin_page_name", $the_page_name, '', 'yes');
    // the id...
    delete_option("my_plugin_page_id");
    add_option("my_plugin_page_id", '0', '', 'yes');

    $the_page = get_page_by_title( $the_page_title );

    if ( ! $the_page ) {

        // Create post object
        $_p = array();
        $_p['post_title'] = $the_page_title;
        $_p['post_content'] = $page_content;
        $_p['post_status'] = 'publish';
        $_p['post_type'] = 'page';
        $_p['comment_status'] = 'closed';
        $_p['ping_status'] = 'closed';
        $_p['post_category'] = array(1); // the default 'Uncategorised'

        // Insert the post into the database
        $the_page_id = wp_insert_post( $_p );

    }
    else {
        // the plugin may have been previously active and the page may just be trashed...

        $the_page_id = $the_page->ID;

        //make sure the page is not trashed...
        $the_page->post_status = 'publish';
        $the_page_id = wp_update_post( $the_page );

    }

    delete_option( 'my_plugin_page_id' );
    add_option( 'my_plugin_page_id', $the_page_id );

}

function remove_page() {

    global $wpdb;

    $the_page_title = get_option( "my_plugin_page_title" );
    $the_page_name = get_option( "my_plugin_page_name" );

    //  the id of our page...
    $the_page_id = get_option( 'my_plugin_page_id' );
    if( $the_page_id ) {

        wp_delete_post( $the_page_id ); // this will trash, not delete

    }

    delete_option("my_plugin_page_title");
    delete_option("my_plugin_page_name");
    delete_option("my_plugin_page_id");

}

function handle_form_action() {
	$base_path = sanitize_text_field($_POST['uploads_path']);
	update_post_meta( $post->ID, 'uploads_path', $base_path );
	$target_path1 = $base_path . sanitize_file_name($_FILES['file1']['name']);
	update_post_meta( $post->ID, 'file1', $target_path1 );
	$target_path2 = $base_path . sanitize_file_name($_FILES['file2']['name']);
	update_post_meta( $post->ID, 'file2', $target_path2 );
	if(is_numeric($_POST['x'])){ $x = $_POST['x']; }
	else { $x = 0; }
	if(is_numeric($_POST['y'])) { $y = $_POST['y'];}
	else { $y = 0; }
	if(is_numeric($_POST['width'])) { $width = $_POST['width']; }
	else { $width = 1000; }
	if(is_numeric($_POST['height'])) { $height = $_POST['height']; }
	else { $height = 1000; }
	if(is_numeric($_POST['th_width'])) {$th_width = $_POST['th_width'];}
	else { alert("ERROR: Thumbnail width not set!"); }
	if($_POST['txt_painting'] == '') { echo("ERROR: Provide name for painting!"); return;}

	echo 'file1: ' . $_FILES['file1']['tmp_name'] . '<br>';
	echo 'file2: ' . $_FILES['file2']['tmp_name'] . '<br>';
	echo 'target path1: ' . $target_path1 . '<br>';
	echo 'target path2: ' . $target_path2 . '<br>';
	
	print_r($_FILES);
	
	move_uploaded_file($_FILES['file1']['tmp_name'], $target_path1);
	move_uploaded_file($_FILES['file2']['tmp_name'], $target_path2);

	$image1 = new Imagick($target_path1);
	$image2 = new Imagick($target_path2);

	$httppath = 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);

	$geo1 = $image1->getimagegeometry();
	if(!($width == '' && $height == '' && $x == '' && $y == '')){
		if($width == '')
		{$width = $geo1['width'];}
		if($height == '')
		{$height = $geo1['height'];}
		if($x == '')
		{$x = 0;}
		if($y == '')
		{$y = 0;}
		$image2->cropimage($width, $height, $x, $y);
	}
	$geo2 = $image2->getimagegeometry();
	session_start();
	$datedir = date('d-m-y');
	$sessioniddir = session_id();

	if(!is_dir($base_path . $datedir)) {
		mkdir($base_path . $datedir);
	}
	if(!is_dir($base_path . $datedir . '/' . $sessioniddir)) {
		mkdir($base_path . $datedir . '/' . $sessioniddir);
	}
	$target_path3 = $base_path . $datedir . '/' . $sessioniddir . '/' . time() . '-large.jpg';
	$resulturi = explode('wordpress', $httppath)[0] . 'wordpress' . explode('wordpress', $base_path)[1] . $datedir . '/' . $sessioniddir . '/' . time() . '-large.jpg';

	$icol = new Imagick();
	if($_POST['hv'] == 'Vertical') {
		if($geo1['width'] > $geo2['width']) {
			$image1->resizeimage($geo2['width'], 0, Imagick::FILTER_POINT, 1);
		}
		else {
			$image2->resizeimage($geo1['width'], 0, Imagick::FILTER_POINT, 1);
		}
		$icol->addimage($image1);
		$icol->addimage($image2);
		$icol->resetiterator();
		$result = $icol->appendimages(true);
	} else if($_POST['hv'] == 'Horizontal') {
		if($geo1['height'] > $geo2['height']) {
			$image1->resizeimage(0, $geo2['height'], Imagick::FILTER_POINT, 1);
		}
		else {
			$image2->resizeimage(0, $geo1['height'], Imagick::FILTER_POINT, 1);
		}
		$icol->addimage($image1);
		$icol->addimage($image2);
		$icol->resetiterator();
		$result =$icol->appendimages(false);
	}

	if($_POST['frame'] == 'Color Frame') {
		$result->frameimage($_POST['color_code'], $_POST['thickness'], $_POST['thickness'], 5, 5);
	} else if($_POST['frame'] == 'Standard Frame') {
		echo 'in standard frame';
		$result->borderimage('#000000', 2, 2); // Black
		$result->borderimage('#A0522D', 3, 3); // Siena
		$result->borderimage('#000000', 1, 1); // Black
		$result->borderimage('#000000', 2, 2); // Black
		$result->borderimage('#000000', 2, 2); // Black
		$result->borderimage('#CD853F', 3, 3); // Peru
		$result->borderimage('#000000', 1, 1); // Black
		$frame = new Imagick('uploads/fancy_add.gif');
		$result->compositeimage($frame, Imagick::COMPOSITE_OVER, 0, 0);
		$frame->flipImage();
		$result->compositeimage($frame, Imagick::COMPOSITE_OVER, 0, $result->getimagegeometry()['height']-48);
		$frame->flopimage();
		$result->compositeimage($frame, Imagick::COMPOSITE_OVER, $result->getimagegeometry()['width']-48, $result->getimagegeometry()['height']-48);
		$frame->flipimage();
		$result->compositeimage($frame, Imagick::COMPOSITE_OVER, $result->getimagegeometry()['width']-48, 0);
	} else if($_POST['frame'] == 'Image Frame') {
		$frame_image = new Imagick($base_path . basename($_FILES['frame_image']['name']));
		$geo_frame = $frame_image->getimagegeometry();
		$geo_result = $result->getimagegeometry();
		$framed_result = new Imagick();
		for($i = 0; $i < $geo_result['width']; $i += $geo_frame['width']) {
			$result->compositeimage($frame_image, imagick::COMPOSITE_DEFAULT, $i, 0);
			$frame_image->flipimage();
			$result->compositeimage($frame_image, imagick::COMPOSITE_DEFAULT, $i, $geo_result['height']-$geo_frame['height']);
			$frame_image->flipimage();
		}
		for($i = 0; $i < $geo_result['height']; $i += $geo_frame['width']) {
			$frame_image->rotateimage(new ImagickPixel('none'), 270);
			$result->compositeimage($frame_image, imagick::COMPOSITE_DEFAULT, 0, $i);
			$frame_image->rotateimage(new ImagickPixel('none'), -180);
			$result->compositeimage($frame_image, imagick::COMPOSITE_DEFAULT, $geo_result['width']-$geo_frame['height'], $i);
			$frame_image->rotateimage(new ImagickPixel('none'), -90);
		}
		$top_right = new Imagick($base_path . basename($_FILES['top_right']['name']));
		$geo_topright = $top_right->getimagegeometry();
		$result->compositeimage($top_right, imagick::COMPOSITE_DEFAULT, $geo_result['width']-$geo_topright['width'], 0);
		$top_right->flipimage();
		$result->compositeimage($top_right, imagick::COMPOSITE_DEFAULT, $geo_result['width']-$geo_topright['width'], $geo_result['height']-$geo_topright['height']);
		$top_right->flopimage();
		$result->compositeimage($top_right, imagick::COMPOSITE_DEFAULT, 0, $geo_result['height']-$geo_topright['height']);
		$top_right->flipimage();
		$result->compositeimage($top_right, imagick::COMPOSITE_DEFAULT, 0, 0);
	}

	$result->writeimage($target_path3);
	$result->resizeimage($th_width, 0, Imagick::FILTER_POINT, 1);
	$thumbpath = $base_path . $datedir . '/' . $sessioniddir . '/' . time() . '-small.jpg';
	$resultthumburi = explode('wordpress', $httppath)[0] . 'wordpress' . explode('wordpress', $base_path)[1] . $datedir . '/' . $sessioniddir . '/' . time() . '-small.jpg';
	$result->writeimage($thumbpath);
	
	// Create post object
	$new_post = array(
		'post_title'    => $_POST['txt_painting'],
		'post_content'  => '<img src='.$resulturi.'><img src='.$resultthumburi.'><br><br>' . '<a href="' . $resulturi . '">Download Twinned Image</a><br>' . '<a href="' . $resultthumburi . '">Download Twinned Thumbnail</a>',
		'post_status'   => 'publish',
		'post_author'   => 1,
		'post_category' => $_POST['category']
	);

	// Insert the post into the database
	wp_insert_post( $new_post );	
	header( 'Location: ' . explode('wp-admin', $httppath)[0]);
}

add_action('admin_post_submit-form', 'handle_form_action');
register_activation_hook(__FILE__,'setup_page'); 
register_deactivation_hook( __FILE__, 'remove_page' );
?>
