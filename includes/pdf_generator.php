<?php
require_once __DIR__.'/functions.php';

/**
 * Generador PDF autónomo de RESpos.
 * No requiere Composer ni extensiones PDF.
 * Genera PDF 1.4 con objetos/xref válidos y texto posicionamiento absoluto.
 */
class RESposPDF{
    private array $pages=[];
    private array $current=[];
    private string $title='RESpos';
    private ?string $logo=null;
    private float $pageWidth=595;
    private float $pageHeight=842;

    public function __construct(string $title='', ?string $logo=null){
        $this->title=$title?:'RESpos';
        $this->logo=$logo;
    }
    public function line(string $text='',float $size=10,bool $bold=false):void{
        $this->current[]=['t'=>$text,'s'=>$size,'b'=>$bold];
    }
    public function heading(string $text):void{
        $this->line($text,15,true);
    }
    public function pageBreak():void{
        if($this->current!==[]) $this->pages[]=$this->current;
        $this->current=[];
    }
    private function esc(string $s):string{
        $s=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/','',$s);
        if(function_exists('iconv')){
            $converted=@iconv('UTF-8','windows-1252//TRANSLIT//IGNORE',$s);
            if($converted!==false) $s=$converted;
        }
        return str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$s);
    }
    private function wrap(string $text,int $maxChars=92):array{
        $text=trim($text);
        if($text==='') return [''];
        $out=[];
        foreach(preg_split('/\r\n|\r|\n/',$text) as $part){
            $part=trim($part);
            if($part===''){ $out[]=''; continue; }
            while(strlen($part)>$maxChars){
                $cut=strrpos(substr($part,0,$maxChars),' ');
                if($cut===false)$cut=$maxChars;
                $out[]=trim(substr($part,0,$cut));
                $part=trim(substr($part,$cut));
            }
            $out[]=$part;
        }
        return $out;
    }
    public function save(string $path):void{
        $this->pageBreak();
        if(!$this->pages)$this->pages=[[]];

        $objects=[];
        $objects[1]='<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2]='';
        $objects[3]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $imageNum=null;
        if($this->logo && is_file($this->logo)){
            $info=@getimagesize($this->logo);
            $bytes=@file_get_contents($this->logo);
            if($info && $bytes!==false && ($info['mime']??'')==='image/jpeg'){
                $imageNum=5;
                $w=max(1,(int)$info[0]); $h=max(1,(int)$info[1]);
                $objects[$imageNum]="<< /Type /XObject /Subtype /Image /Width ".$w." /Height ".$h." /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($bytes)." >>\nstream\n".$bytes."\nendstream";
            }
        }

        $next=max(array_keys($objects))+1;
        $pageNumbers=[];
        foreach($this->pages as $pageLines){
            $stream="q\n";
            if($imageNum!==null){
                $stream.="70 0 0 70 262 755 cm\n/Im1 Do\n";
            }
            $stream.="Q\nBT\n";
            $y=$imageNum!==null?730:790;
            foreach($pageLines as $row){
                foreach($this->wrap((string)$row['t']) as $wrapped){
                    $size=max(5,(float)$row['s']);
                    $font=$row['b']?'F4':'F3';
                    $stream.='/'.$font.' '.$size.' Tf'."\n".'1 0 0 1 45 '.number_format($y,2,'.','').' Tm'."\n".'('.$this->esc($wrapped).') Tj'."\n";
                    $y-=$size+6;
                    if($y<45) break 2;
                }
            }
            $stream.="ET";
            $contentNum=$next++;
            $objects[$contentNum]="<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream";
            $pageNum=$next++;
            $xobj=$imageNum!==null?' /XObject << /Im1 '.$imageNum.' 0 R >>':'';
            $objects[$pageNum]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F3 3 0 R /F4 4 0 R >>'.$xobj.' >> /Contents '.$contentNum.' 0 R >>';
            $pageNumbers[]=$pageNum;
        }
        $objects[2]='<< /Type /Pages /Kids ['.implode(' 0 R ',$pageNumbers).' 0 R] /Count '.count($pageNumbers).' >>';

        // Compact object numbering: every object from 1..max exists.
        ksort($objects,SORT_NUMERIC);
        $max=max(array_keys($objects));
        $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets=array_fill(0,$max+1,0);
        for($n=1;$n<=$max;$n++){
            if(!array_key_exists($n,$objects)) throw new RuntimeException('PDF object numbering error.');
            $offsets[$n]=strlen($pdf);
            $pdf.=$n." 0 obj\n".$objects[$n]."\nendobj\n";
        }
        $xref=strlen($pdf);
        $pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";
        for($n=1;$n<=$max;$n++)$pdf.=sprintf("%010d 00000 n \n",$offsets[$n]);
        $pdf.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
        ensureDir(dirname($path));
        if(file_put_contents($path,$pdf,LOCK_EX)===false)throw new RuntimeException('No se pudo guardar el PDF.');
        // XAMPP/Apache on Linux can inherit a restrictive umask. PDFs must be readable
        // by Apache and by the local user after generation.
        @chmod($path,0644);
        @chmod(dirname($path),0755);
    }
}

function generarDocumentoPDF(string $type,string $number,string $heading,array $lines):string{
    $config=getAppConfig();
    $folder=match($type){
        'comprobante'=>'comprobantes','cierre_caja'=>'cierres','inventario'=>'inventario','reporte'=>'reportes',default=>'facturas'
    };
    $dir=__DIR__.'/../storage/pdfs/'.$folder; ensureDir($dir);
    $file=$dir.'/'.$number.'.pdf';
    $pdf=new RESposPDF($heading,logoPdfPath($config));
    $pdf->heading($config['nombre_restaurante']??'RESpos Restaurant');
    foreach([
        'Direccion: '.($config['direccion']??''),
        'Tel: '.($config['telefonos']??''),
        'Fecha: '.now(),
        'Documento: '.$number
    ] as $x)$pdf->line($x,9,false);
    $pdf->line('',5); $pdf->heading($heading);
    foreach($lines as $line)$pdf->line((string)$line,10,false);
    $pdf->save($file);
    return str_replace(__DIR__.'/../','',$file);
}

function generarComprobantePDF(string $number,array $restaurant,array $items,float $total,string $currency,float $received,float $change,string $method,string $mesa):string{
    $dir=__DIR__.'/../storage/pdfs/comprobantes'; ensureDir($dir);
    $file=$dir.'/'.$number.'.pdf';
    $pdf=new RESposPDF('Comprobante '.$number,logoPdfPath($restaurant));
    $pdf->heading($restaurant['nombre_restaurante']??'RESpos Restaurant');
    $pdf->line(($restaurant['direccion']??''),8,false);
    $pdf->line('Tel: '.($restaurant['telefonos']??''),8,false);
    $pdf->line(str_repeat('-',52),8,false);
    $pdf->line('COMPROBANTE DE CONSUMO',12,true);
    $pdf->line('No: '.$number,9,false);
    $pdf->line('Mesa: '.$mesa.'   '.now(),9,false);
    $pdf->line(str_repeat('-',52),8,false);
    $pdf->line('ARTICULO                  CANT.        IMPORTE',8,true);
    foreach($items as $it){
        $name=substr((string)$it['nombre'],0,25);
        $qty=number_format((float)$it['cantidad'],2,',','.');
        $sub=money((float)$it['cantidad']*(float)$it['precio_unitario']);
        $pdf->line($name.'   '.$qty.'   '.$sub,8,false);
    }
    $pdf->line(str_repeat('-',52),8,false);
    $pdf->line('TOTAL: '.money($total).' CUP',11,true);
    $pdf->line('PAGO: '.strtoupper($method).' / '.$currency,9,false);
    $pdf->line('RECIBIDO: '.money($received,$currency),9,false);
    $pdf->line('VUELTO: '.money($change,$currency),10,true);
    $pdf->line(str_repeat('-',52),8,false);
    $pdf->line('Gracias por su visita',10,true);
    $pdf->line('Vuelva pronto',10,true);
    $pdf->save($file);
    return str_replace(__DIR__.'/../','',$file);
}
?>
