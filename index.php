<?php
// Se for POST para gerar ZIP
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
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Visualizador de Imagens</title>
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
   <style>
    body { padding: 1rem 0; }
    .img-wrapper { display: flex; flex-wrap: wrap; }
    .img-container { margin: 0.5rem; position: relative; }
    .resizable-img {
      width: 150px; height: auto;
      transition: width .2s, opacity .2s;
      cursor: grab;
    }
    .resizable-img.selected {
      outline: 3px solid #0d6efd;
      opacity: 0.8;
    }
    .range-label { margin-left: .5rem; font-weight: bold; }
    aside { max-height: 80vh; overflow-y: auto; }
    .selected-thumb {
      width: 40px; height: auto; object-fit: cover;
      border-radius: 4px; margin-right: .5rem;
    }
    #slideshowOverlay img {
      max-height: 80vh; max-width: 90vw;
    }
  </style>


</head>
<body>



    <!-- Painel Adaptativo -->
    <aside id="selectedImagesPanel"
           class="col-12 col-lg-3 bg-light border rounded p-3">
      <h5>
        Selecionadas
        <span class="badge bg-primary" id="selectedCount">0</span>
      </h5>
      <ul id="selectedList" class="list-unstyled mb-3"></ul>
      <form id="downloadForm" method="post">
        <input type="hidden" name="files" id="filesInput">
        <button type="submit" class="btn btn-success w-100">
          📦 Baixar ZIP
        </button>
      </form>
    </aside>


<div class="col-12 col-lg-9">

  <!-- Controles de filtro -->
  <div class="container py-3">
    <form method="get" class="row g-3 align-items-center">
      <div class="col-12 col-md-4">
        <label for="imageSizeRange" class="form-label">
          Tamanho das imagens:
          <span id="rangeValue" class="range-label">150px</span>
        </label>
        <input type="range" class="form-range" id="imageSizeRange" min="50" max="300" value="150">
      </div>
      <div class="col-12 col-md-4">
        <label for="limitSelect" class="form-label">Imagens por página:</label>
        <select name="limit" id="limitSelect" class="form-select">
          <?php
            $opcoes = [5, 10, 20, 50];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            foreach ($opcoes as $opcao) {
              $sel = $limit === $opcao ? 'selected' : '';
              echo "<option value=\"$opcao\" $sel>$opcao</option>";
            }
          ?>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Tipos de imagem:</label><br>
        <?php
          $extensoesDisponiveis = ['jpg','jpeg','png','gif','jfif','avif','svg','ico','webp'];
          $extensoesSelecionadas = isset($_GET['ext']) ? $_GET['ext'] : $extensoesDisponiveis;
          foreach ($extensoesDisponiveis as $ext) {
            $ck = in_array($ext, $extensoesSelecionadas) ? 'checked' : '';
            echo "<div class='form-check form-check-inline'>
                    <input class='form-check-input' type='checkbox' name='ext[]' value='$ext' id='ext_$ext' $ck>
                    <label class='form-check-label' for='ext_$ext'>$ext</label>
                  </div>";
          }
        ?>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-primary">Aplicar filtros</button>
      </div>
    </form>
  </div>

  <!-- Galeria com paginação -->
  <div class="container-fluid bg-body-secondary">
    <?php
      $diretorio = 'image-bank';
      $arquivos = scandir($diretorio);
      $exts = isset($_GET['ext']) ? $_GET['ext'] : $extensoesDisponiveis;
      $imagens = array_filter($arquivos, function($a) use($exts) {
        $e = strtolower(pathinfo($a, PATHINFO_EXTENSION));
        return $a !== '.' && $a !== '..' && in_array($e, $exts);
      });

      $total = count($imagens);
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $offset = ($page - 1) * $limit;
      $pag = array_slice($imagens, $offset, $limit);

      echo '<div class="img-wrapper">';
      foreach ($pag as $file) {
        // data-filename é usado no JS para identificar
        echo "<div class='img-container'>";
        echo "<img
                class='figure-img img-fluid rounded resizable-img'
                src='$diretorio/$file'
                alt='$file'
                data-filename='$file'
                draggable='true'
              >";
        echo "<figcaption class='figure-caption'>$file</figcaption>";
        echo "</div>";
      }
      echo '</div>';

      // Paginação simplificada
      $totalPg = ceil($total / $limit);
      if ($totalPg > 1) {
        echo '<nav class="fixed-bottom bg-light py-2 shadow">
                <ul class="pagination justify-content-center mb-0">';
        // «
        if ($page > 1) {
          echo "<li class='page-item'><a class='page-link' href='?limit=$limit&page=1" . buildExtQuery($extensoesSelecionadas) . "'>&laquo;</a></li>";
        }
        // numéros
        $maxVis = 5; $half = floor($maxVis/2);
        if ($totalPg <= $maxVis) {
          $start=1; $end=$totalPg;
        } elseif ($page <= $half) {
          $start=1; $end=$maxVis;
        } elseif ($page >= $totalPg-$half) {
          $start=$totalPg-$maxVis+1; $end=$totalPg;
        } else {
          $start=$page-$half; $end=$page+$half;
        }
        for ($i=$start;$i<=$end;$i++) {
          $act = $i===$page?'active':'';
          echo "<li class='page-item $act'><a class='page-link' href='?limit=$limit&page=$i" . buildExtQuery($extensoesSelecionadas) . "'>$i</a></li>";
        }
        // »
        if ($page < $totalPg) {
          echo "<li class='page-item'><a class='page-link' href='?limit=$limit&page=$totalPg" . buildExtQuery($extensoesSelecionadas) . "'>&raquo;</a></li>";
        }
        echo '</ul></nav>';
      }

      function buildExtQuery($exts) {
        $q = '';
        foreach ($exts as $e) { $q .= '&ext[]='.urlencode($e); }
        return $q;
      }
    ?>
  </div>

    </div>

  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script>
    const rangeInput    = document.getElementById('imageSizeRange');
    const rangeValue    = document.getElementById('rangeValue');
    const limitSelect   = document.getElementById('limitSelect');
    const panel         = document.getElementById('selectedImagesPanel');
    const list          = document.getElementById('selectedList');
    const filesInput    = document.getElementById('filesInput');
    const selectedCount = document.getElementById('selectedCount');

    // Carrega seleção persistente
    let selectedFiles = JSON.parse(localStorage.getItem('selectedImages') || '[]');

    // Atualiza UI e localStorage
    function updateSelected() {
      // limpa lista
      list.innerHTML = '';
      selectedFiles.forEach(fn => {
        const li = document.createElement('li');
        li.classList.add('d-flex','justify-content-between','align-items-center','mb-2');
        li.innerHTML = `
          <span>${fn}</span>
          <button class="btn btn-sm btn-outline-danger ms-2" onclick="removeImage('${fn}', this)">✖</button>
        `;
        list.appendChild(li);
      });
      selectedCount.textContent = selectedFiles.length;
      filesInput.value = JSON.stringify(selectedFiles);
      localStorage.setItem('selectedImages', JSON.stringify(selectedFiles));

      // marca/desmarca imagens na galeria
      document.querySelectorAll('.resizable-img').forEach(img => {
        const fn = img.dataset.filename;
        img.classList.toggle('selected', selectedFiles.includes(fn));
      });
    }

    function removeImage(fn, btn) {
      selectedFiles = selectedFiles.filter(x => x !== fn);
      updateSelected();
    }

    window.addEventListener('DOMContentLoaded', () => {
      // Carrega tamanho e limite
      const sz = localStorage.getItem('imageSize');
      const lim = localStorage.getItem('imageLimit');
      if (sz) {
        rangeInput.value = sz;
        rangeValue.textContent = sz + 'px';
        updateImageSize(sz);
      }
      if (lim) limitSelect.value = lim;

      // Drag start
      document.querySelectorAll('.resizable-img').forEach(img => {
        img.addEventListener('dragstart', e => {
          e.dataTransfer.setData('text/plain', img.dataset.filename);
        });
      });

      // Reconstrói seleção persistente
      updateSelected();
    });

    // Ajusta tamanho das imagens
    function updateImageSize(size) {
      document.querySelectorAll('.resizable-img').forEach(img => {
        img.style.width = size + 'px';
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
      localStorage.setItem('imageLimit', limitSelect.value);
    });

    // Arrastar e soltar no painel
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
</body>
</html>
