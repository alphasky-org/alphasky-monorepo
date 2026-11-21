<?php
namespace Alphasky\Base\Supports\WebsiteDesign;

use RuntimeException;
use Symfony\Component\Process\Process;

final class ReferenceText
{
    public function extract(string $path, string $extension): string
    {
        if ($extension==='docx') {
            $zip=new \ZipArchive();
            if ($zip->open($path)!==true) throw new RuntimeException('attachment_invalid');
            try {
                $total=0; $text='';
                if ($zip->numFiles>2000 || $zip->locateName('word/document.xml')===false) throw new RuntimeException('attachment_invalid');
                for ($i=0;$i<$zip->numFiles;$i++) {
                    $entry=$zip->statIndex($i); $total+=$entry['size'];
                    if ($total>40000000 || stripos($entry['name'],'vbaProject')!==false) throw new RuntimeException('attachment_invalid');
                }
                $names=['word/document.xml'];
                for ($i=0;$i<$zip->numFiles;$i++) if (preg_match('#^word/(header\d+|footer\d+|footnotes|endnotes)\.xml$#D',$zip->getNameIndex($i))) $names[]=$zip->getNameIndex($i);
                foreach ($names as $name) {
                    $xml=$zip->getFromName($name);
                    if (strlen($xml)>5000000 || stripos($xml,'<!DOCTYPE')!==false || stripos($xml,'<!ENTITY')!==false) throw new RuntimeException('attachment_invalid');
                    $doc=new \DOMDocument();
                    $old=libxml_use_internal_errors(true);
                    try { if (!$doc->loadXML($xml,LIBXML_NONET)) throw new RuntimeException('attachment_invalid'); }
                    finally { libxml_clear_errors(); libxml_use_internal_errors($old); }
                    $xp=new \DOMXPath($doc);$xp->registerNamespace('w','http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    foreach ($xp->query('//w:p') as $p) {foreach($xp->query('.//w:t | .//w:tab | .//w:br',$p) as $node) $text.=$node->localName==='t'?$node->textContent:"\t";$text.="\n";}
                }
            } finally { $zip->close(); }
        } elseif ($extension==='doc') {
            if (file_get_contents($path,false,null,0,8)!==hex2bin('d0cf11e0a1b11ae1')) throw new RuntimeException('attachment_invalid');
            $binary=(string)config('website_design.antiword','/usr/bin/antiword');
            if (!is_executable($binary)) throw new RuntimeException('attachment_converter_unavailable');
            $process=new Process([$binary,'-m','UTF-8.txt',$path],null,['LC_ALL'=>'C.UTF-8'],null,20);
            $process->run();
            if (!$process->isSuccessful()) throw new RuntimeException('attachment_invalid');
            $text=$process->getOutput();
        } else {
            $text=file_get_contents($path);
            if (!mb_check_encoding($text,'UTF-8') || str_contains($text,"\0")) throw new RuntimeException('attachment_invalid');
        }
        $text=trim(preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/u','',$text)??'');
        if ($text==='') throw new RuntimeException('attachment_empty');
        if (mb_strlen($text)>80000) throw new RuntimeException('attachment_too_long');
        return $text;
    }
}
