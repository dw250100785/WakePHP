<?php
namespace WakePHP\Utils;
class ImageDrawDiagramm
{
 var $colors = array();
 var $legend = array();
 var $shadows = array();
 var $values;
 var $draw;
 function draw()
 {
  $black = ImageColorAllocate($this->draw->res,0,0,0);
  // Получим размеры изображения
  $W = $this->draw->sX();
  $H = $this->draw->sY();
  $this->draw->antialias(TRUE);
  // Вывод легенды #####################################
  // Посчитаем количество пунктов,от этого зависит высота легенды
  $this->legend_count = sizeof($this->legend);

  // Посчитаем максимальную длину пункта,от этого зависит ширина легенды
  $max_length = 0;
  foreach($this->legend as $v) {if ($max_length < strlen($v)) {$max_length = strlen($v);}} 

  // Номер шрифта,котором мы будем выводить легенду
  $FONT = 2;
  $font_w = ImageFontWidth($FONT);
  $font_h = ImageFontHeight($FONT);
  // Вывод прямоугольника - границы легенды ----------------------------
  $l_width = ($font_w*$max_length)+$font_h+10+5+10;
  $l_height = $font_h*$this->legend_count+10+10;
  // Получим координаты верхнего левого угла прямоугольника - границы легенды
  $l_x1 = $W-100-$l_width;
  $l_y1 = ($H-$l_height)/2;
  // Выводя прямоугольника - границы легенды
  ImageRectangle($this->draw->res,$l_x1,$l_y1,$l_x1+$l_width,$l_y1+$l_height,$black);
  // Вывод текст легенды и цветных квадратиков
  $text_x = $l_x1+10+5+$font_h;
  $square_x = $l_x1+10;
  $y = $l_y1+10;
  $i = 0;
  foreach($this->legend as $v)
  {
   $dy = $y+($i*$font_h);
   $this->draw->ttftext($v,$black,CORE_PATH.'fonts/TAHOMA.TTF',8,$text_x,$dy+11);
   ImageFilledRectangle($this->draw->res,
	$square_x+1,$dy+1,$square_x+$font_h-1,$dy+$font_h-1,
	$this->draw->hex2color($this->colors[$i]));
   ImageRectangle($this->draw->res,
	$square_x+1,$dy+1,$square_x+$font_h-1,$dy+$font_h-1,
	$black);
   $i++;
  }
  // Вывод круговой диаграммы ----------------------------------------
  $sv = sizeof($this->values);
  if (sizeof($this->values) == 1) {$this->values[] = 0.00000000001; ++$sv;}
  $total = array_sum($this->values);
  $anglesum = $angle = Array(0);
  $i = 1;
  // Расчет углов
  while ($i < $sv)
  {
   $part = $this->values[$i-1]/$total;
   $angle[$i] = floor($part*360);
   $anglesum[$i] = array_sum($angle);
   $i++;
  }
  $anglesum[] = $anglesum[0];
  // Расчет диаметра
  $diametr = $l_x1-10-10;

  // Расчет координат центра эллипса
  $circle_x = ($diametr/2)+10;
  $circle_y = $H/2-10;  

 // Поправка диаметра,если эллипс не помещается по высоте
  if ($diametr > ($H*2)-10-10) {$diametr = ($H*2)-20-20-40;}

  // Вывод тени
  for ($j = 20; $j > 0; $j--)
  {
   for ($i = 0;$i < sizeof($anglesum)-1; $i++)
   {
	ImageFilledArc($this->draw->res,$circle_x,$circle_y+$j,
	$diametr,$diametr/2,
	$anglesum[$i],$anglesum[$i+1],
	$this->draw->hex2color($this->shadows[$i]),IMG_ARC_PIE);
   }
  }
  // Вывод круговой диаграммы
  for ($i = 0; $i < sizeof($anglesum)-1; $i++)
  {
   ImageFilledArc($this->draw->res,$circle_x,$circle_y,
	$diametr,$diametr/2,
	$anglesum[$i],$anglesum[$i+1],
	$this->draw->hex2color($this->colors[$i]),IMG_ARC_PIE);
  }
 }
}