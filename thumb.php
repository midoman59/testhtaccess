<?php
// require_once('./vendor/experience/http/thumb.class.php');
/** This file is part of the Experience package.
 * ---------------------------------------------
 * @version   : 0.0
 * @copyright : Copyright (c) 2007-2017
 * @license   : BSD License
 * @Author : Mathieu NINRINCK
 */

class Thumb
{
	private $quality = 90;
	private $app;
	private $cache;
	private $file;
	private $ext;

	private $width = 0;
	private $height = 0;	
	
	private $img_width = 0;
	private $img_height = 0;
	private $img_x = 0;	
	private $img_y = 0;
	
	private $thumb_width = 0;
	private $thumb_height = 0;
	private $thumb_x = 0;	
	private $thumb_y = 0;

	/* Function : function __construct(string $app, string $file, string $crop = '') instance
	 * @Param : file path
	 * @Param : crop type : ('crop' | 'nocrop' | '')
	 * Retourne une instance de la classe
	 * -------------------------------------------------------------------------------- */	
	function __construct($file, $width, $height, $crop = '')
	{
		$this->app = './';
		$this->file = ($file[0] == '/' ? substr($file,1) : $file);
		if(!file_exists($this->app.'ftp/'.$this->file)) $this->file = 'no-photo.jpg';
		$info = pathinfo($this->file);
		$this->ext = strtolower($info['extension']);
		$this->cache = 'ftp.'.str_replace('/', '.', strtolower(($info['dirname']!='.'?$info['dirname'].'/':'').$info['filename'])).'-'.$width.'x'.$height.$crop.'.'.$this->ext;

		$this->ext = ($this->ext == 'jpg' ? 'jpeg' : $this->ext);

		# Si fichier en cache
		if(file_exists($this->app.'cache/'.$this->cache)){
			$filemtime_cache = filemtime($this->app.'cache/'.$this->cache);
			if($filemtime_cache > filemtime($this->app.'ftp/'.$this->file)){
				header('Expires: '.gmdate("D, d M Y H:i:s \G\M\T", time() + 240*60*60));
				header('Content-type: image/'.$this->ext);
				if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $filemtime_cache){
					header('Last-Modified: '.gmdate("D, d M Y H:i:s \G\M\T", $filemtime_cache), true, 304);
					exit();
				}
				header("Last-Modified: ".gmdate("D, d M Y H:i:s \G\M\T", $filemtime_cache));
				readfile($this->app.'cache/'.$this->cache);
				exit();
			}
		}

		# Generate thumb
		$info = getimagesize($this->app.'ftp/'.$this->file);
		$this->img_width = $info[0];
		$this->img_height = $info[1];
		
		# If thumb ratio egal to image ration no crop possible
		$this->generate($width, $height, $crop);
		$this->display();
	}

	/* Function : public function generate(uint $width, uint $height) void
	 * @Param : width of thumb
	 * @Param : height of thumb
	 * -------------------------------------------------------------------------------- */		
	private function generate($width, $height, $crop)
	{
		# Fix width and height
		if($width != 'auto' && $height != 'auto'){
			if($crop == '-crop'){
				$aspect = $this->img_width / $this->img_height;
				$thumb_aspect = $width / $height;
				if($aspect >= $thumb_aspect){
					$this->thumb_height = $height;
					$this->thumb_width = $this->img_width / ($this->img_height / $height);
				}else{
					$this->thumb_width = $width;
					$this->thumb_height = $this->img_height / ($this->img_width / $width);
				}
				$this->thumb_x = round(($width-$this->thumb_width)/2);
				$this->thumb_y = round(($height-$this->thumb_height)/2);
				$this->width = $width;
				$this->height = $height;
			}elseif($crop == '-nocrop'){
				if($width/$height < $this->img_width/$this->img_height){
					$this->thumb_x = 0;
					$this->thumb_y = round(($height-($this->img_height*$width/$this->img_width))*0.5);
					$this->thumb_width = $width;
					$this->thumb_height = round($this->img_height*$width/$this->img_width);
				}else{
					$this->thumb_x = round(($width-($this->img_width*$height/$this->img_height))*0.5);
					$this->thumb_y = 0;
					$this->thumb_width = round($this->img_width*$height/$this->img_height);
					$this->thumb_height = $height;
				}
				$this->width = $width;
				$this->height = $height;
			}else{
				$this->width = $this->thumb_width = $width;
				$this->height = $this->thumb_height = $height;
			}
		}

		# Fix width
		if($width != 'auto' && $height == 'auto'){
			$this->width = $this->thumb_width = $width;
			$this->height = $this->thumb_height = round($this->thumb_width/($this->img_width/$this->img_height));
		}
		
		# Fix height
		if($width == 'auto' && $height != 'auto'){
			$this->height = $this->thumb_height = $height;
			$this->width = $this->thumb_width = round($this->thumb_height/($this->img_height/$this->img_width));
		}
		
		# Original size
		if($width == 'auto' && $height == 'auto'){
			$this->width = $this->thumb_width = $this->img_width;
			$this->height = $this->thumb_height = $this->img_height;
		}
	}

	/* Function : public function display() void
	 * -------------------------------------------------------------------------------- */		
	public function display()
	{
		header('Expires: '.gmdate("D, d M Y H:i:s \G\M\T", time() + 240*60*60));
		header('Content-type: image/'.$this->ext);
		header("Last-Modified: ".gmdate("D, d M Y H:i:s \G\M\T"));

		switch($this->ext){
			case 'jpeg':
				$src = imagecreatefromjpeg($this->app.'ftp/'.$this->file);
				break;
			case 'png':
				$src = imagecreatefrompng($this->app.'ftp/'.$this->file);
				break;
			case 'gif':
				$src = imagecreatefromgif($this->app.'ftp/'.$this->file);
				break;
		}

		$this->img = imagecreatetruecolor($this->width, $this->height);
		$color = imagecolorallocate($this->img, 255, 255, 255);
		imagefill($this->img, 0, 0, $color);
		if($this->ext == 'png'){
			imagecolortransparent($this->img, $color);		
			imagealphablending($this->img, false);
			imagesavealpha($this->img, true);		
		}
		
		imagecopyresampled($this->img, $src, $this->thumb_x, $this->thumb_y, $this->img_x, $this->img_y, $this->thumb_width, $this->thumb_height, $this->img_width, $this->img_height);
		imageinterlace($this->img, false);

		ob_start();
		switch($this->ext){
			case 'jpeg':
				imagejpeg($this->img, null, $this->quality);
				break;
			case 'png':
				imagepng($this->img);
				break;
			case 'gif':
				imagegif($this->img);
				break;
		}
		$ob_get = ob_get_clean();
		$handle = fopen($this->app.'cache/'.$this->cache, 'w');
		fwrite($handle, $ob_get);
		fclose($handle);	
		echo $ob_get;

		imagedestroy($this->img);
		imagedestroy($src);
	}
}

$thumb = isset($_GET['thumb']) ? $_GET['thumb'] : '';
$query = isset($_GET['query']) ? $_GET['query'] : '';
if(preg_match('/^([0-9]{1,}|auto)x([0-9]{1,}|auto)(-crop|-nocrop)?$/', $query, $match)){
	new Thumb($thumb, $match[1], $match[2], isset($match[3]) ? $match[3] : '');
}else{
	new Thumb($thumb, 'auto', 'auto');
}
?>