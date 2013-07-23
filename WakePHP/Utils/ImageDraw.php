<?php
namespace WakePHP\Utils;
if (!function_exists('win2uni')) {
	function win2uni($s) {
		$s = convert_cyr_string($s,'w','i');
		$result='';
		$n = strlen($s);
		for ($i = 0; $i < $n; ++$i) {
			$charcode = ord($s[$i]);
			$result .= ($charcode>175)?'&#'.(1040+($charcode-176)).';':$s[$i];
		}
		return $result;
	}
}
class Imagedraw {
	public $res;
	public $W;
	public $H;
	public $scale = 1;
	public $cX;
	public $cY;
	public $colors = array();
	public $type = 'png';
	public $quality = 100;
	public $outfile = '';
	public $offsetX = 0;
	public $offsetY = 0;
	public $colortransparent;
	public function __construct() {
		$this->init();
	}
	public function init() {
		$this->sW = $this->W*$this->scale;
		$this->sH = $this->H*$this->scale;
		$this->cX = $this->W/2;
		$this->cY = $this->H/2;
		$this->set_offset($this->offsetX,$this->offsetY);
	}
	public function sY() {
		return $this->H = imagesy($this->res);
	}
	public function sX() {
		return $this->W = imagesx($this->res);
	}
	public function set_offset($offsetX, $offsetY) {
		$this->offsetX = $offsetX*$this->scale;
		$this->offsetY = $offsetY*$this->scale;
	}
	public function hex2color($color, $d = false) {
		if (is_resource($color)) {
			return $color;
		}
		if (is_string($color)) {
			$color = hexdec($color);
		}
		if (is_int($color)) {
			$color = sprintf('%06x',$color);}
			if (isset($this->colors[$color])) {
				return $this->colors[$color];
		}
		return $this->colors[$color] = imagecolorallocate($this->res,hexdec(substr($color,0,2)),hexdec(substr($color,2,2)),hexdec(substr($color,4,2)));
	}
	public function createfromstring($string) {
		$this->res = imagecreatefromstring($string);
		$this->sX();
		$this->sY();
		$this->init();
	}
	public function createfromjpeg($file) {
		$this->res = imagecreatefromjpeg($file);
		$this->sX();
		$this->sY();
		$this->init();
	}
	public function createtruecolor($w = null, $h = null) {
		if (is_null($w)) {
			$this->res = imagecreatetruecolor($this->sW+$this->offsetX,$this->sH+$this->offsetY);
		}
		else {$this->res = imagecreatetruecolor($w,$h);}
		$this->sX();
		$this->sY();
		$this->init();
	 }
	public function colorat($x, $y) {
		return imagecolorat($this->res, $x + $this->offsetX,$y + $this->offsetY);
	}
	public function create($w = null, $h = null) {
		if (is_null($w)) {
			return $this->res = imagecreate($this->sW + $this->offsetX, $this->sH + $this->offsetY);
		}
		else {return $this->res = imagecreate($w,$h);}
	}
	public function out($file = null) {
		if ($file !== null) {
			$this->outfile = $file;
		}
		if ($this->type === 'png') {
			return $this->outfile !== '' ? imagepng($this->res, strval($this->outfile)) : imagepng($this->res);
		}
		elseif ($this->type == 'jpeg') {
			return $this->outfile !== '' ? imagejpeg($this->res, strval($this->outfile), $this->quality) : imagejpeg($this->res, NULL, $this->quality);
		}
		elseif ($this->type === 'gif') {
			return $this->outfile !== '' ? imagegif($this->res, strval($this->outfile)) : imagegif($this->res);
		}
	}
	public function setbgcolor($color = 0xFFFFFF) {
		return imagefill($this->res, 0, 0, $this->hex2color($color));
	}
	public function border($color = 0x000000) {
		return imageRectangle($this->res,
			$this->offsetX,				$this->offsetY,
			$this->sW+$this->offsetX-1,	$this->sH+$this->offsetY-1,
			$this->hex2color($color)
		);
	}
	public function border2($color = 0x000000) {
		return imageRectangle($this->res,
			$this->offsetX,				$this->offsetY,
			$this->sW+$this->offsetX,	$this->sH+$this->offsetY,
			$this->hex2color($color)
		);
	}
	public function rscale($a) {
		return $a / $this->scale;
	}
	public function tscale($a) {
		return $a * $this->scale;
	}
	public function setScale($s) {
		return $this->scale = floatval($s);
	}
	function setpixel($x, $y, $color = 0x000000) {
		return imagesetpixel($this->res,$this->offsetX+$x,$this->offsetY+$y,$this->hex2color($color));
	}
	function line($x1,$y1,$x2,$y2,$color = 0x000000,$thick = 1) {
		$x1 = $x1*$this->scale+$this->offsetX;
		$x2 = $x2*$this->scale+$this->offsetX;
		$y1 = $y1*$this->scale+$this->offsetY;
		$y2 = $y2*$this->scale+$this->offsetY;
		$color = $this->hex2color($color);
		if ($thick === 1) {
			return imageline($this->res,$x1,$y1,$x2,$y2,$color);
		}
		$t = $thick/2-0.5;
		if ($x1 == $x2 || $y1 == $y2) {
			return imagefilledrectangle($this->res,round(min($x1,$x2)-$t),round(min($y1,$y2)-$t),round(max($x1,$x2)+$t),round(max($y1,$y2)+$t),$color);
		}
		$k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
		$a = $t / sqrt(1 + pow($k,2));
		$points = array(
			round($x1-(1+$k)*$a),round($y1+(1-$k)*$a),
			round($x1-(1-$k)*$a),round($y1-(1+$k)*$a),
			round($x2+(1+$k)*$a),round($y2-(1-$k)*$a),
			round($x2+(1-$k)*$a),round($y2+(1+$k)*$a),
		);   
		imagefilledpolygon($this->res,$points,4,$color);
		imagepolygon($this->res,$points,4,$color);
		return;
	}
	public function line1($x1, $y1, $x2, $y2, $color = 0x000000, $thickness=5) {
		imagesetthickness($this->res,$thickness);
		imageline($this->res,
			$this->offsetX+$x1*$this->scale,	$this->offsetY-1+$y1*$this->scale,
			$this->offsetX+$x2*$this->scale,	$this->offsetY-1+$y2*$this->scale,
			$this->hex2color($color)
		);
		imagesetthickness($this->res,1);
	}
	public function ttftext($text,$color,$font,$size,$x,$y,$angle=0) {
		if (!is_file($font) or !is_readable($font)) {
			user_error('Can\'t open font '.$font,E_USER_WARNING);
			return;
		}
		return imagettftext($this->res,$size*$this->scale,$angle,
			$this->offsetX+$x*$this->scale,
			$this->offsetY+$y*$this->scale,
			$this->hex2color($color),$font,win2uni($text)
		);
	}
	public function arc($cx,$cy,$w,$h,$s,$e,$color = 0x000000) {
		return imagearc($this->res,$cx+$this->offsetX,$cy+$this->offsetY,$w,$h,$s,$e,$this->hex2color($color));
	}
	public function filledarc($cx,$cy,$w,$h,$s,$e,$color = 0x000000) {
		return imagefilledarc($this->res,$cx+$this->offsetX,$cy+$this->offsetY,$w,$h,$s,$e,$this->hex2color($color));
	}
	public function ellipse($cx,$cy,$w,$h,$color = 0x000000) {
		return imageellipse($this->res,$cx+$this->offsetX,$cy+$this->offsetY,$w,$h,$s,$e,$this->hex2color($color));
	}
	public function filledellipse($cx,$cy,$w,$h,$color = 0x000000) {
		return imagefilledellipse($this->res,$cx+$this->offsetX,$cy+$this->offsetY,$w,$h,$this->hex2color($color));
	}
	public function pointmark($x,$y,$name=NULL,$color = 0x000000) {
		$this->filledellipse($x,$y,5,5,$color);
		if (is_null($name)) {
			$name = '('.round($x,3).','.round($y,3).')';
		}
		$this->ttftext($name,$color,CORE_PATH.'fonts/ARIAL.TTF',9,$x+5,$y-5);
	}
	public function filledpolygon($points,$color = 0x000000) {
		if (!is_array($points)) {
			return false;
		}
		else {
			$points = array_values($points);
			$points = array_map('intval',$points);
		}
 		foreach ($points as $i => $v) {
 			$points[$i] = $v*$this->scale+(($i%2 == 0)?$this->offsetX:$this->offsetY);
 		}
		return imagefilledpolygon($this->res,$points,sizeof($points)/2,$this->hex2color($color));
	}
	public function polygon($points,$color = 0x000000) {
		if (!is_array($points)) {
			return false;
		}
		else {
			$points = array_values($points);
			$points = array_map('intval',$points);
		}
		foreach ($points as $i=>$v) {
			$points[$i] = $v*$this->scale+(($i%2 == 0)?$this->offsetX:$this->offsetY);
		}
  		return imagefilledpolygon($this->res,$points,sizeof($points)/2,$this->hex2color($color));
 	}
 	public function antialias($bool) {
 		imageantialias($this->res,!!$bool);
 	}
	public function colortransparent($color = 0xFFFFFF) {
		return imagecolortransparent($this->res,$this->colortransparent = $this->hex2color($color));
	}
	public function filter_twirl($dimx = null, $dimy = null)  {
		if (is_null($dimx)) {
			$dimx = $this->W-1;
		}
  		if (is_null($dimy)) {
  			$dimy = $this->H-1;
  		}
		$wp = $dimx / 2;
		$hp = $dimy / 2;
	  	$im_source = imagecreatetruecolor($dimx,$dimy);
		imagecopy($im_source,$this->res,
			0,0,
			0,0,
			$dimx,$dimy
		);
		$im_filter = imagecreatetruecolor($dimx+100,$dimy+100);
		imagealphablending($im_filter,FALSE);
		imagesavealpha($im_filter,TRUE);
		$color_filter = imagecolorallocatealpha($im_filter,255,255,255,127);
		imagefill($im_filter,0,0,$color_filter);
		$a = atan2(-1.0,$wp-1.0);
		if ($a < 0.0) {
			$a += 2.0 * M_PI;
		}
		$dx = $dimx / $a;
		$d  = sqrt($hp*$hp+$wp*$wp);
		$dy = $dimy / $d;
		for ($h = 0; $h < $dimy + 1; $h++) {
			for ($w = 0; $w < $dimx + 1; $w++) {
				$x     = ($w-$wp);
				$y     = ($h-$hp);
				$dist  = sqrt($x*$x+$y*$y);
				$angle = atan2($y,$x);
				if ($angle < 0) {$angle += 2.0 * M_PI;}
				$rgb = imagecolorat($this->res,(int)($dimx - $dx*$angle),(int)($dy*$dist));
				$a = ($rgb >> 24) & 0xFF;
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$color_filter = imagecolorallocatealpha($im_filter,$r,$g,$b,$a);
				imagesetpixel($im_filter,$w,$h,$color_filter);
			}
		}
		imagecopy($this->res,$im_filter,
			0,0,
			0,0,
			$dimx, $dimy
		);
		imagedestroy($im_filter);
	}
 	public function filter_swirl($dimx=NULL,$dimy=NULL) {
 		if (is_null($dimx)) {
 			$dimx = $this->W;
 		}
		if (is_null($dimy)) {
			$dimy = $this->H;
		}
		$wp = $dimx/2;
	  	$hp = $dimy/2;
		$im_source = imagecreatetruecolor($dimx,$dimy);
		imagecopy($im_source, $this->res,
			0,0,
			0,0,
			$dimx,$dimy
  		);
		$im_filter = imagecreatetruecolor($dimx,$dimy);
		imagealphablending($im_filter,FALSE);
		imagesavealpha($im_filter,TRUE);
		$color_filter = imagecolorallocatealpha($im_filter,255,255,255,127);
		imagefill($im_filter,0,0,$color_filter);
		$dz = -0.01;
		for ($h = 0; $h < $dimy + 1; ++$h) {
			for ($w = 0; $w < $dimx + 1; ++$w) {
				$x     = ($w - $wp);
				$y     = ($h - $hp);
				$dist  = sqrt($x*$x + $y*$y);
				$angle = atan2($y,$x);
				if ($angle < 0) {
					$angle += 2.0 * M_PI;
				}
				@$rgb = imagecolorat($im_source,(int) ($wp + $dist * cos($angle + $dist * $dz)),(int)($hp + $dist * sin($angle + $dist * $dz)));
				$a = ($rgb >> 24) & 0xFF;
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$color_filter = imagecolorallocatealpha($im_filter,$r,$g,$b,$a);
				imagesetpixel($im_filter,$w,$h,$color_filter);
			}
		}
  		imagecopy($this->res,$im_filter,
			0,0,
			0,0,
			$dimx,$dimy
 		);
		//imagedestroy($im_source);
		//imagedestroy($im_filter);
	}
	public function wave_region($x,$y,$width,$height,$grade=10) {
		for ($i = 0; $i < $width; $i += 2) {
			imagecopy($this->res,$this->res,
				$x+$i-2,$y+sin($i/10)*$grade,   //dest
				$x+$i,$y,    			       //src
				2,$height
			);
		}
	}
	public function resize_file($src, $dest, $width, $height, $rgb = 0xFFFFFF, $quality = 100) {
		$size = getimagesize($src);
		if (!$size) {
			return -1;
		}
		if (($size[0] <= $width) && ($size[1] <= $height)) {
			return copy($src,$dest);
		}
		$format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));
		$icfunc = 'imagecreatefrom' . $format;
		if (!function_exists($icfunc)) {
			return -2;
		}

	  $x_ratio = $width / $size[0];
	  $y_ratio = $height / $size[1];

	  $ratio       = min($x_ratio, $y_ratio);
	  $use_x_ratio = ($x_ratio == $ratio);

	  $new_width   = $use_x_ratio  ? $width  : floor($size[0] * $ratio);
	  $new_height  = !$use_x_ratio ? $height : floor($size[1] * $ratio);
	  $new_left    = $use_x_ratio  ? 0 : floor(($width - $new_width) / 2);
	  $new_top     = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2);

	  $isrc = $icfunc($src);
	  //$idest = imagecreatetruecolor($width, $height);
	  $idest = imagecreatetruecolor($new_width, $new_height);
	  $new_left = $new_top = 0;

	  imagefill($idest, 0, 0, $rgb);
	  imagecopyresampled($idest, $isrc, $new_left, $new_top, 0, 0, 
		$new_width, $new_height, $size[0], $size[1]);

	  imagejpeg($idest,$dest,$quality);

	  imagedestroy($isrc);
	  imagedestroy($idest);

	  return TRUE;
	}
 	public function skew($skew_val) {
		$width = $this->sX();
		$height = $this->sY();
		$imgdest = imagecreatetruecolor($width,$height+($height*$skew_val));
		$trans = imagecolorallocate($imgdest,0,0,0);
		$temp = 0;
		for ($x = 0; $x < $width; ++$x) {
			for ($y = 0; $y < $height; ++$y) {
				imagecopy($imgdest,$this->res,$x,$y+$temp,$x,$y,1,1);
				imagecolortransparent($imgdest,$trans);    
			}
			$temp += $skew_val;
		}
		$this->destroy();
		$this->res = $imgdest;
	}
	public function flip($mode)  {
		$w = $this->sX();
		$h = $this->sY();
		$flipped = imagecreatetruecolor($w,$h);
		if ($mode & static::VERTICAL) {
			for ($y = 0; $y < $h; ++$y) {
				imagecopy($flipped, $this->res, 0, $y, 0, $h - $y - 1, $w, 1);
			}
  		}
		if ($mode & static::HORIZONTAL) {
			for ($x = 0; $x < $w; ++$x) {
				imagecopy($flipped, $this->res, $x, 0, $w - $x - 1, 0, 1, $h);
			}
		}
		$this->res = $flipped;
	}
	public function mirror($type = 1) {
		$imgsrc = $this->res;
		$width = $this->sX();
		$height = $this->sY();
		$imgdest = imagecreatetruecolor($width,$height);
		for ($x=0 ; $x<$width ; $x++) {
			for ($y=0 ; $y<$height ; $y++) {
				if ($type == MIRROR_HORIZONTAL) {
					imagecopy($imgdest,$imgsrc,$width-$x-1,$y,$x,$y,1,1);
				}
				if ($type == MIRROR_VERTICAL) {
					imagecopy($imgdest,$imgsrc,$x,$height-$y-1,$x,$y,1,1);
				}
	 			if ($type == MIRROR_BOTH) {
	 				imagecopy($imgdest,$imgsrc,$width-$x-1,$height-$y-1,$x,$y,1,1);
	 			}
   			}
  		}
		$this->destroy();
		$this->res = $imgdest; 
	}
	public function watermark($img) {
		$bwidth  = $this->sX();
		$bheight = $this->xY();
		$lwidth  = imagesx($img);
		$lheight = imagesy($img);
		$src_x = $bwidth - ($lwidth + 5);
		$src_y = $bheight - ($lheight + 5);
		imageAlphaBlending($this->res,TRUE);
		imagecopy($this->res,$img,$src_x,$src_y,0,0,$lwidth,$lheight);
	}
	public function skew_waves() {
		$width = $this->sx();
		$height = $this->sY();

		$img = $this->res;
		$img2 = imagecreatetruecolor($width,$height);
		$center = $width/2;
		// periods
		$rand1 = mt_rand(750000,1200000)/10000000;
		$rand2 = mt_rand(750000,1200000)/10000000;
		$rand3 = mt_rand(750000,1200000)/10000000;
		$rand4 = mt_rand(750000,1200000)/10000000;
		// phases
		$rand5 = mt_rand(0,31415926)/10000000;
		$rand6 = mt_rand(0,31415926)/10000000;
		$rand7 = mt_rand(0,31415926)/10000000;
		$rand8 = mt_rand(0,31415926)/10000000;
		// amplitudes
		$rand9 = mt_rand(330, 420) / 110;
		$rand10 = mt_rand(330, 450) / 110;

		$foreground_color = array(0x00, 0x00, 0x00);
		$background_color = array(0xC0, 0xC0, 0xC0);

		//wave distortion
		$jjj = 0;
		for($x=0;$x<$width;$x++){
			for($y=0;$y<$height;$y++){
				$sx=$x+(sin($x*$rand1+$rand5)+sin($y*$rand3+$rand6))*$rand9-$width/2+$center+1;
				$sy=$y+(sin($x*$rand2+$rand7)+sin($y*$rand4+$rand8))*$rand10;

				if($sx<0 || $sy<0 || $sx>=$width-1 || $sy>=$height-1){
					continue;
				}else{
					$color=imagecolorat($img, $sx, $sy) & 0xFF;
					$color_x=imagecolorat($img, $sx+1, $sy) & 0xFF;
					$color_y=imagecolorat($img, $sx, $sy+1) & 0xFF;
					$color_xy=imagecolorat($img, $sx+1, $sy+1) & 0xFF;
				}

				if($color==255 && $color_x==255 && $color_y==255 && $color_xy==255){
					continue;
				}else if($color==0 && $color_x==0 && $color_y==0 && $color_xy==0){
					$newred=$foreground_color[0];
					$newgreen=$foreground_color[1];
					$newblue=$foreground_color[2];
					
					$newred = 0x00;
					$newgreen = 0x00;
					$newblue = 0x00;
					//++$jjj;
				}else{
					$frsx=$sx-floor($sx);
					$frsy=$sy-floor($sy);
					$frsx1=1-$frsx;
					$frsy1=1-$frsy;

					$newcolor=(
						$color*$frsx1*$frsy1+
						$color_x*$frsx*$frsy1+
						$color_y*$frsx1*$frsy+
						$color_xy*$frsx*$frsy);

					if($newcolor>255) $newcolor=255;
					$newcolor=$newcolor/255;
					$newcolor0=1-$newcolor;

					$newred=$newcolor0*$foreground_color[0]+$newcolor*$background_color[0];
					$newgreen=$newcolor0*$foreground_color[1]+$newcolor*$background_color[1];
					$newblue=$newcolor0*$foreground_color[2]+$newcolor*$background_color[2];

				}
				imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $newred, $newgreen, $newblue));
			}
		}
	for($x=0;$x<$width;$x++)
	{
		 for($y=0;$y<=$height;$y++)
	 {
	  if (($y > $height-4) || (imagecolorat($img2,$x,$y) == 0x171717)) {imagesetpixel($img2,$x,$y,0x000000);}
	 }
	}
 
  $this->destroy();
  $this->res = $img2;
 }
 public function destroy() {
 	return imagedestroy($this->res);
 }
 public function __destruct() {
 	if (is_resource($this->res)) {
 		$this->destroy();
 	}
 }
}
