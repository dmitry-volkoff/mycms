<?php
/*
 * Thumbnail generator 
 *
 * @package shop
 * @author vdb
 * @version CVS: $Id$
 */

$thumbWidth = $thumbHeight = 50;
$im_dir = 'photo';
$thumb_dir = 'thumb';
$product_dir = 'shop_parts';
$thumbQuality = 75;
$thumbPath = '';
$imagePath = '';
$noimagePath = '/images/nophoto.jpg';
$root_abs_path = '';
$product = isset($_SERVER["QUERY_STRING"]) ? (int) $_SERVER["QUERY_STRING"] : 0;
$root_abs_path = realpath(dirname($_SERVER["SCRIPT_FILENAME"]).'/../');
//echo $root_abs_path;

if ($product)
{
	$imagePath = '/' .$im_dir .'/'. $product_dir .'/'. $product .'/1.jpg';
	$thumbPath = '/' .$thumb_dir .'/'. $product_dir .'/'. $thumbWidth .'/'. $product .'.jpg';    
} else {
	$thumbPath = '/img/nophoto.jpg';
}

if (! is_readable($root_abs_path . $imagePath))
{
	header("Location: ..". $noimagePath); exit;
}

if (is_readable($root_abs_path . $thumbPath))
{
	header("Location: ..".$thumbPath); exit;
}

if (!is_dir($root_abs_path.'/'. $thumb_dir)) 
{ 
	@mkdir($root_abs_path.'/'. $thumb_dir);
	@chmod($root_abs_path.'/'. $thumb_dir, 0755); 
}
if (!is_dir($root_abs_path.'/'. $thumb_dir .'/'. $product_dir)) 
{ 
	@mkdir($root_abs_path.'/'. $thumb_dir .'/'. $product_dir); 
	@chmod($root_abs_path.'/'. $thumb_dir .'/'. $product_dir, 0755); 
}
if (!is_dir($root_abs_path.'/'. $thumb_dir .'/'. $product_dir .'/'. $thumbWidth)) 
{ 
	@mkdir($root_abs_path.'/'. $thumb_dir .'/'. $product_dir .'/'. $thumbWidth); 
	@chmod($root_abs_path.'/'. $thumb_dir .'/'. $product_dir .'/'. $thumbWidth, 0755); 
}
if (!is_dir($root_abs_path.'/'. $thumb_dir .'/'. $product_dir .'/'. $thumbWidth))
{
	header("Location: ..". $noimagePath); exit;
}

$cmd = '/usr/bin/convert';
$cmd .= " -size {$thumbWidth}x{$thumbHeight} -thumbnail {$thumbWidth}x{$thumbHeight} -quality $thumbQuality"; 
//$cmd .= ' +profile "*"';
$cmd .= ' '.escapeshellarg($root_abs_path . $imagePath).' '.escapeshellarg($root_abs_path . $thumbPath);
exec($cmd);

if (! is_readable($root_abs_path . $thumbPath))
{
	header("Location: ..". $noimagePath); exit;
}

header("Location: ..".$thumbPath);
?>