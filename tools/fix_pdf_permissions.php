<?php
// Ejecutar desde la raíz de RESpos si una instalación Linux heredó permisos restrictivos.
$root=realpath(__DIR__.'/../storage/pdfs');
if(!$root)die("No existe storage/pdfs\n");
$dirs=0;$files=0;
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
foreach($it as $p){if($p->isDir()){@chmod($p->getPathname(),0755);$dirs++;}elseif(strtolower($p->getExtension())==='pdf'){@chmod($p->getPathname(),0644);$files++;}}
@chmod($root,0755);
echo "Permisos reparados. Directorios: $dirs | PDFs: $files\n";
