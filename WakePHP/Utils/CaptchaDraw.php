<?php
namespace WakePHP\Utils;
class CAPTCHA_draw {
	public $text;
	public $heigth;
	public $width;
	public $quantity;
	public $noise1 = FALSE;
	public $noise2 = FALSE;
	public $noise3 = FALSE;
	public $noise_wave_n = 0;
	public $noise_skew_n = 1;
	public $fonts_dir;
	public $skin = 'black';
	public function __construct() {
		$this->fonts_dir = dirname(__FILE__) . '/fonts/';
	}
	public function setText($text) {
		$this->text = $text;
	}

	public static function getRandomText() {
		return \PHPDaemon\Utils\Crypt::randomString(4, 'ABCDEFGHKLMNPQRSTUVWXYZ23456789abcdefghkmnpqrstuvwxyz23456789');
	}

	public function show() {
		$text = $this->text;
		$draw = new ImagedDraw;
		$draw->type = 'png';
		$draw->W = 150;
		$draw->H = 50;
		$draw->init();
		$draw->createtruecolor();
		$draw->antialias(TRUE);
		$bgcolor = 0x1F1F1F;
		$textcolor = 0xC0C0C0;
		$draw->setbgcolor($bgcolor);
		$draw->colortransparent(0x000000);
	
		$fonts = [];
		$fonts = [
			'Verdana',
			'Times',
			'Tempsitc',
		];
		for ($i = 0;$i < strlen($text); ++$i) {
			$size = rand(25,30);
			$angle = rand(-10,10);
			$x = 15 + rand(-5,5)+$i*30;
			$y = 30 + rand(-5,5)+$size/5;
			$font = $fonts[array_rand($fonts)].'.ttf';
			$font_p = $this->fonts_dir.$font;
			$draw->ttftext($text{$i},$textcolor,$font_p,$size,$x,$y,$angle);
		}
		for ($j = 0; $j < 2; $j++) {
			 $lastX = -1;
			 $lastY = -1;
			 $N = rand(20,40);
	 		for ($i = 5; $i < $draw->W-1; $i+=10) {
				$X = $i;
				$Y = $draw->H/2-sin($X/$N)*10+$j*20;
				if ($lastX > -1) {$draw->line1($lastX,$lastY,$X,$Y,$textcolor,2);}
				$lastX = $X;
				$lastY = $Y;
	 		}
		}
	/*if ($this->noise3)
	{
	 for ($i = 0; $i < 2; $i++)
	 {
		$draw->line1(
				5,				rand(0,$draw->W-1),
				$draw->sX()-5,	rand(0,$draw->H),
		0x000000,6);
	 }
	}*/
		//for ($i = 0; $i < $this->noise_wave_n; $i++) {$draw->wave_region(0,0,$draw->W,$draw->H);}
		for ($i = 0; $i < $this->noise_skew_n; $i++) {$draw->skew_waves();}
		/*for ($i = 0; $i < $draw->W; ++$i)
		{
	 		for ($j = 0; $j < $draw->H; ++$j)
	 		{
				if (imagecolorat($draw->res,$i,$j) >= 0xFFFFFF) {imagesetpixel($draw->res,$i,$j,$bgcolor);}
			}
		}*/
	
	//if ($this->noise1) {for($x = 1; $x < $draw->W-1; $x++) {for($y = 1; $y < $draw->H-1; $y++) {if ($y%2 == 0 and $x%2 == 0) {$draw->setpixel($x,$y,'000000');}}}}
	//if ($this->noise2) {for($x=1;$x<$draw->W;$x++) {for($y=1;$y<$draw->H;$y++) {if (rand(0,10) == 0) {$draw->setpixel($x,$y,'FFFFFF');}}}}
	//$draw->border2();
	//$borderlight = 0xA2A2A2;
	//$borderdark = 0x4D4D4D;
	//$draw->line(0,0,$draw->W,0,$borderlight);
	//$draw->line(0,0,0,$draw->H,$borderlight);
	//$draw->line($draw->W-1,1,$draw->W-1,$draw->H,$borderdark);
	if ($this->skin == 'white') {imagefilter($draw->res,IMG_FILTER_NEGATE);}
	$draw->out();
 }
}
