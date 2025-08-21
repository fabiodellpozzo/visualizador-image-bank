<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['files'])) {
  $files = json_decode($_POST['files']);
  $zip = new ZipArchive();
  $zipName = 'imagens_selecionadas.zip';

  if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    foreach ($files as $file) {
      $path = 'image-bank/' . basename($file);
      if (file_exists($path)) {
        $zip->addFile($path, basename($file));
      }
    }
    $zip->close();

    header('Content-Type: application/zip');
    header("Content-Disposition: attachment; filename=$zipName");
    header('Content-Length: ' . filesize($zipName));
    readfile($zipName);
    unlink($zipName);
    exit;
  } else {
    echo "Erro ao criar o arquivo ZIP.";
  }
}
?>

