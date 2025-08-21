<?php
// Geração do ZIP quando o formulário é submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['files'])) {
  $files   = json_decode($_POST['files'], true);
  $zip     = new ZipArchive();
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
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Visualizador de Imagens</title>
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      padding-top: 1rem;
      padding-bottom: 1rem;
    }
    .img-wrapper {
      display: flex;
      flex-wrap: wrap;
    }
    .img-container {
      margin: 10px;
      position: relative;
    }
    .resizable-img {
      transition: width 0.2s ease, opacity 0.2s ease;
      cursor: grab;
    }
    .resizable-img.selected {
      outline: 3px solid #0d6efd;
      opacity: 0.8;
    }
    .range-label {
      font-weight: bold;
      margin-left: 0.5rem;
    }
    /* Thumb no aside */
    .selected-thumb {
      width: 40px;
      height: auto;
      border-radius: 4px;
      object-fit: cover;
      margin-right: 0.5rem;
    }
    /* Às vezes o Aside fica muito grande em telas pequenas */
    #selectedImagesPanel {
      max-height: 80vh;
      overflow-y: auto;
    }
  </style>
</head>
<body>

<div class="container-fluid">
  <div class="row gy-3">

    <!-- ASIDE ADAPTATIVO -->
    <aside id="selectedImagesPanel"
           class="col-12 col-lg-3 bg-light border rounded p-3">
      <h5>
        Selecionadas
        <span class="badge bg-primary" id="selectedCount">0</span>
      </h5>
      <ul id="selectedList" class="list-unstyled mb-3"></ul>

      <form id="downloadForm" method="post" action="">
        <input type="hidden" name="files" id="filesInput">
        <button type="submit" class="btn btn-success w-100">
          📦 Baixar ZIP
        </button>
      </form>
    </aside>

    <!-- CONTEÚDO PRINCIPAL -->
    <div class="col-12 col-lg-9">

      <!-- FILTROS -->
      <div class="card mb-3">
        <div class="card-body">
          <form method="get" class="row g-3 align-items-center">
            <div class="col-12 col-md-4">
              <label for="imageSizeRange" class="form-label">
                Tamanho:
                <span id="rangeValue" class="range-label">150px</span>
              </label>
              <input type="range"
                     class="form-range"
                     id="imageSizeRange"
                     min="50" max="300"
                     value="150">
            </div>
            <div class="col-12 col-md-4">
              <label for="limitSelect" class="form-label">
                Por página:
              </label>
              <select name="limit"
                      id="limitSelect"
                      class="form-select">
                <?php
                  $opts  = [5, 10, 20, 50];
                  $limit = isset($_GET['limit'])
                           ? (int)$_GET['limit']
                           : 10;
                  foreach ($opts as $o) {
                    $sel = $limit === $o ? 'selected' : '';
                    echo "<option value=\"$o\" $sel>$o</option>";
                  }
                ?>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Extensões:</label>
              <div>
                <?php
                  $allExts = ['jpg','jpeg','png','gif','jfif','avif','svg','ico','webp'];
                  $selExts = isset($_GET['ext'])
                             ? $_GET['ext']
                             : $allExts;
                  foreach ($allExts as $ext) {
                    $chk = in_array($ext, $selExts)
                           ? 'checked'
                           : '';
                    echo "<div class=\"form-check form-check-inline\">
                            <input class=\"form-check-input\"
                                   type=\"checkbox\"
                                   name=\"ext[]\"
                                   value=\"$ext\"
                                   id=\"chk_$ext\"
                                   $chk>
                            <label class=\"form-check-label\"
                                   for=\"chk_$ext\">$ext</label>
                          </div>";
                  }
                ?>
              </div>
            </div>
            <div class="col-12 text-end">
              <button type="submit" class="btn btn-primary">
                Aplicar filtros
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- GALERIA -->
      <div class="img-wrapper mb-3">
        <?php
          // Carrega, filtra e pagina
          $dir    = 'image-bank';
          $files  = scandir($dir);
          $exts   = isset($_GET['ext']) ? $_GET['ext'] : $allExts;
          $imgs   = array_filter($files, fn($f) => 
                      $f !== '.' && $f !== '..'
                      && in_array(
                           strtolower(pathinfo($f, PATHINFO_EXTENSION)),
                           $exts
                         )
                    );
          $total  = count($imgs);
          $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
          $page   = isset($_GET['page'])  ? (int)$_GET['page']  : 1;
          $offset = ($page - 1) * $limit;
          $slice  = array_slice($imgs, $offset, $limit);

          foreach ($slice as $f) {
            echo "<div class=\"img-container\">
                    <img src=\"$dir/$f\"
                         data-filename=\"$f\"
                         alt=\"$f\"
                         class=\"resizable-img rounded\"
                         draggable=\"true\">
                    <figcaption class=\"figure-caption small text-center\">
                      $f
                    </figcaption>
                  </div>";
          }
        ?>
      </div>

      <!-- PAGINAÇÃO -->
      <?php
        $pages = ceil($total / $limit);
        if ($pages > 1) {
          echo '<nav><ul class="pagination">';
          for ($i = 1; $i <= $pages; $i++) {
            $act = $i === $page ? 'active' : '';
            echo "<li class=\"page-item $act\">
                    <a class=\"page-link\"
                       href=\"?limit=$limit&page=$i"
                      . (isset($_GET['ext'])
                         ? '&'.http_build_query(['ext' => $_GET['ext']])
                         : '') ."\">$i</a>
                  </li>";
          }
          echo '</ul></nav>';
        }
      ?>

    </div> <!-- /.col-lg-9 -->
  </div> <!-- /.row -->
</div> <!-- /.container-fluid -->

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
  // Controles e painel
  const rangeInput    = document.getElementById('imageSizeRange');
  const rangeValue    = document.getElementById('rangeValue');
  const limitSelect   = document.getElementById('limitSelect');
  const panel         = document.getElementById('selectedImagesPanel');
  const list          = document.getElementById('selectedList');
  const filesInput    = document.getElementById('filesInput');
  const selectedCount = document.getElementById('selectedCount');

  // Carrega seleção de localStorage
  let selectedFiles = JSON.parse(
    localStorage.getItem('selectedImages') || '[]'
  );

  // Atualiza a UI do painel e marca miniaturas
  function updateSelected() {
    list.innerHTML = '';
    selectedFiles.forEach(fn => {
      const li = document.createElement('li');
      li.classList.add('d-flex','align-items-center','mb-2');
      li.innerHTML = `
        <img src="image-bank/${fn}" 
             alt="${fn}" 
             class="selected-thumb">
        <span class="flex-grow-1">${fn}</span>
        <button class="btn btn-sm btn-outline-danger ms-2"
                onclick="removeImage('${fn}')">
          ✖
        </button>
      `;
      list.appendChild(li);
    });
    selectedCount.textContent = selectedFiles.length;
    filesInput.value         = JSON.stringify(selectedFiles);
    localStorage.setItem('selectedImages',
                         JSON.stringify(selectedFiles));

    // Marcar / desmarcar no grid
    document.querySelectorAll('.resizable-img').forEach(img => {
      const fn = img.dataset.filename;
      img.classList.toggle('selected',
                          selectedFiles.includes(fn));
    });
  }

  function removeImage(fn) {
    selectedFiles = selectedFiles.filter(x => x !== fn);
    updateSelected();
  }

  // Evento DOMLoaded
  window.addEventListener('DOMContentLoaded', () => {
    // Restaurar tamanho e limite
    const sz  = localStorage.getItem('imageSize');
    const lim = localStorage.getItem('imageLimit');
    if (sz)  rangeInput.value = sz;
    if (lim) limitSelect.value = lim;
    rangeValue.textContent = rangeInput.value + 'px';
    updateImageSize(rangeInput.value);

    // Tornar imagens arrastáveis
    document.querySelectorAll('.resizable-img').forEach(img => {
      img.addEventListener('dragstart', e => {
        e.dataTransfer.setData(
          'text/plain',
          img.dataset.filename
        );
      });
    });

    // Reconstrói seleção persistente
    updateSelected();
  });

  // Ajusta o tamanho das imagens
  function updateImageSize(size) {
    document.querySelectorAll('.resizable-img').forEach(img => {
      img.style.width  = size + 'px';
      img.style.height = 'auto';
    });
    rangeValue.textContent = size + 'px';
  }

  rangeInput.addEventListener('input', () => {
    const s = rangeInput.value;
    localStorage.setItem('imageSize', s);
    updateImageSize(s);
  });

  limitSelect.addEventListener('change', () => {
    localStorage.setItem('imageLimit',
                         limitSelect.value);
  });

  // Drag & drop no painel
  panel.addEventListener('dragover', e => {
    e.preventDefault();
    panel.classList.add('border-primary');
  });
  panel.addEventListener('dragleave', () => {
    panel.classList.remove('border-primary');
  });
  panel.addEventListener('drop', e => {
    e.preventDefault();
    panel.classList.remove('border-primary');
    const fn = e.dataTransfer.getData('text/plain');
    if (!selectedFiles.includes(fn)) {
      selectedFiles.push(fn);
      updateSelected();
    }
  });
</script>

