<?php
if (!defined('ABSPATH')) exit;
class Studio_PDF_Engine {
    private $pages=array(''); private $page=0; private $w=595; private $h=842;
    public function __construct($orientation='portrait'){if($orientation==='landscape'){$this->w=842;$this->h=595;}}
    public function add_page(){ $this->pages[]=''; $this->page=count($this->pages)-1; }
    private function op($s){$this->pages[$this->page].=$s;}
    private function enc($s){$s=iconv('UTF-8','Windows-1252//TRANSLIT',(string)$s);return str_replace(array('\\','(',')'),array('\\\\','\\(','\\)'),$s);}
    public function color($hex){$hex=ltrim($hex,'#');$r=hexdec(substr($hex,0,2))/255;$g=hexdec(substr($hex,2,2))/255;$b=hexdec(substr($hex,4,2))/255;$this->op(sprintf('%.3f %.3f %.3f rg %.3f %.3f %.3f RG ',$r,$g,$b,$r,$g,$b));}
    public function rect($x,$y,$w,$h,$fill=true){$this->op(sprintf('%.2f %.2f %.2f %.2f re %s ',$x,$this->h-$y-$h,$w,$h,$fill?'f':'S'));}
    public function line($x1,$y1,$x2,$y2){$this->op(sprintf('%.2f %.2f m %.2f %.2f l S ',$x1,$this->h-$y1,$x2,$this->h-$y2));}
    public function text($x,$y,$text,$size=10,$bold=false,$color='#1e293b'){$this->color($color);$font=$bold?'F2':'F1';$this->op("BT /$font $size Tf $x ".($this->h-$y)." Td (".$this->enc($text).") Tj ET ");}
    public function wrapped($x,$y,$text,$width,$size=10,$leading=14,$bold=false,$color='#1e293b'){$chars=max(12,(int)($width/($size*.52)));foreach(preg_split('/\R/',(string)$text) as $p){foreach(explode("\n",wordwrap(trim($p),$chars,"\n",true)) as $line){$this->text($x,$y,$line,$size,$bold,$color);$y+=$leading;}$y+=$leading/3;}return $y;}
    public function output(){
        $count=count($this->pages);$pageObjStart=3;$contentObjStart=$pageObjStart+$count;$font1=$contentObjStart+$count;$font2=$font1+1;$max=$font2;$kids=array();$objs=array();$objs[1]='<</Type/Catalog/Pages 2 0 R>>';
        for($i=0;$i<$count;$i++)$kids[]=($pageObjStart+$i).' 0 R';$objs[2]='<</Type/Pages/Kids['.implode(' ',$kids).']/Count '.$count.'>>';
        for($i=0;$i<$count;$i++){$pobj=$pageObjStart+$i;$cobj=$contentObjStart+$i;$stream=$this->pages[$i];$objs[$pobj]='<</Type/Page/Parent 2 0 R/MediaBox[0 0 '.$this->w.' '.$this->h.']/Resources<</Font<</F1 '.$font1.' 0 R/F2 '.$font2.' 0 R>>>>/Contents '.$cobj.' 0 R>>';$objs[$cobj]='<</Length '.strlen($stream).">> stream\n$stream\nendstream";}
        $objs[$font1]='<</Type/Font/Subtype/Type1/BaseFont/Helvetica/Encoding/WinAnsiEncoding>>';$objs[$font2]='<</Type/Font/Subtype/Type1/BaseFont/Helvetica-Bold/Encoding/WinAnsiEncoding>>';$pdf="%PDF-1.4\n";$off=array(0);for($i=1;$i<=$max;$i++){$off[$i]=strlen($pdf);$pdf.="$i 0 obj\n{$objs[$i]}\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";for($i=1;$i<=$max;$i++)$pdf.=sprintf('%010d 00000 n ',$off[$i])."\n";return $pdf.'trailer <</Size '.($max+1).'/Root 1 0 R>>'."\nstartxref\n$xref\n%%EOF";
    }
}
