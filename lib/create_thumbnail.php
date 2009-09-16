<?php
if (! (int) $id || ! (int) $entry)
{
	return false;
}
$imagePath = $this->upload_photo_abs_path .'/'. substr(get_class($this->dao), 4) .'/'. 
	$id .'/'.$entry.'.'. $this->img_type;
$thumbPath = $this->upload_thumb_abs_path .'/'. substr(get_class($this->dao), 4) .'/'.
	$size .'/'. $id .'/'.$entry .'.'. $this->thumb_type;

//echo 'imagePath:'.$imagePath ."\n";
//echo 'thumbPath:'.$thumbPath ."\n";

if (file_exists($thumbPath) || ! file_exists($imagePath)) { return false; }

if (!is_dir($this->upload_thumb_abs_path)) 
{ 
	@mkdir($this->upload_thumb_abs_path);
	@chmod($this->upload_thumb_abs_path, 0755); 
}
if (!is_dir($this->upload_thumb_abs_path .'/'. substr(get_class($this->dao), 4))) 
{ 
	@mkdir($this->upload_thumb_abs_path .'/'. substr(get_class($this->dao), 4)); 
	@chmod($this->upload_thumb_abs_path .'/'. substr(get_class($this->dao), 4), 0755); 
}
if (!is_dir($this->upload_thumb_abs_path .'/'. substr(get_class($this->dao), 4) .'/'. $size)) 
{ 
	@mkdir($this->upload_thumb_abs_path .'/'. substr(get_class($this->dao), 4) .'/'. $size); 
	@chmod($this->upload_thumb_abs_path .'/'. substr(get_class($this->dao), 4) .'/'. $size, 0755); 
}

if (!is_dir($this->upload_thumb_abs_path .'/'. substr(get_class($this->dao), 4) .'/'. $size .'/'.$id)) 
{ 
	@mkdir($this->upload_thumb_abs_path .'/'. substr(get_class($this->dao), 4) .'/'. $size .'/'.$id); 
	@chmod($this->upload_thumb_abs_path .'/'. substr(get_class($this->dao), 4) .'/'. $size .'/'.$id, 0755);
}

if (!is_dir($this->upload_thumb_abs_path .'/'. substr(get_class($this->dao), 4) .'/'. $size .'/'.$id))
{
	return false;
}

$cmd = '/usr/bin/convert';
$cmd .= " -size ".$size."x".$size." -thumbnail ".$size."x".$size." -quality 75"; 
//$cmd .= ' +profile "*"';
$cmd .= ' '.escapeshellarg($imagePath).' '.escapeshellarg($thumbPath);
//echo $cmd ."\n";
return exec($cmd);
?>