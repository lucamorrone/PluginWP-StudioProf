<?php
if (!defined('ABSPATH')) exit;
class Studio_PDF_Engine {
    private $ops=''; private $w=595; private $h=842;
    public function __construct($orientation='portrait'){if($orientation==='landscape'){$this->w=842;$this->h=595;}}
    private function enc($s){$s=iconv('UTF-8','Windows-1252//TRANSLIT',(string)$s);return str_replace(array('\\','(',')'),array('\\\\','\\(','\\)'),$s);}
    public function color($hex){$hex=ltrim($hex,'#');$r=hexdec(substr($hex,0,2))/255;$g=hexdec(substr($hex,2,2))/255;$b=hexdec(substr($hex,4,2))/255;$this->ops.=sprintf('%.3f %.3f %.3f rg %.3f %.3f %.3f RG ',$r,$g,$b,$r,$g,$b);}
    public function rect($x,$y,$w,$h,$fill=true){$this->ops.=sprintf('%.2f %.2f %.2f %.2f re %s ',$x,$this->h-$y-$h,$w,$h,$fill?'f':'S');}
    public function line($x1,$y1,$x2,$y2){$this->ops.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x1,$this->h-$y1,$x2,$this->h-$y2);}
    public function text($x,$y,$text,$size=10,$bold=false,$color='#1e293b'){$this->color($color);$font=$bold?'F2':'F1';$this->ops.="BT /$font $size Tf $x ".($this->h-$y)." Td (".$this->enc($text).") Tj ET ";}
    public function wrapped($x,$y,$text,$width,$size=10,$leading=14,$bold=false,$color='#1e293b'){$chars=max(12,(int)($width/($size*.52)));$paras=preg_split('/\R/',(string)$text);foreach($paras as $p){$lines=explode("\n",wordwrap(trim($p),$chars,"\n",true));foreach($lines as $line){$this->text($x,$y,$line,$size,$bold,$color);$y+=$leading;}$y+=$leading/3;}return $y;}
    public function output(){ $stream=$this->ops;$objs=array();$objs[1]='<</Type/Catalog/Pages 2 0 R>>';$objs[2]='<</Type/Pages/Kids[3 0 R]/Count 1>>';$objs[3]='<</Type/Page/Parent 2 0 R/MediaBox[0 0 '.$this->w.' '.$this->h.']/Resources<</Font<</F1 5 0 R/F2 6 0 R>>>>/Contents 4 0 R>>';$objs[4]='<</Length '.strlen($stream).">> stream\n$stream\nendstream";$objs[5]='<</Type/Font/Subtype/Type1/BaseFont/Helvetica/Encoding/WinAnsiEncoding>>';$objs[6]='<</Type/Font/Subtype/Type1/BaseFont/Helvetica-Bold/Encoding/WinAnsiEncoding>>';$pdf="%PDF-1.4\n";$off=array(0);for($i=1;$i<=6;$i++){$off[$i]=strlen($pdf);$pdf.="$i 0 obj\n{$objs[$i]}\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 7\n0000000000 65535 f \n";for($i=1;$i<=6;$i++)$pdf.=sprintf('%010d 00000 n ',$off[$i])."\n";return $pdf."trailer <</Size 7/Root 1 0 R>>\nstartxref\n$xref\n%%EOF"; }
}
