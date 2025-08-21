<!doctype html>
<html lang="pt-br">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logos dev</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
      body {
        padding-bottom: 80px;
      }
      .img-wrapper {
        display: flex;
        flex-wrap: wrap;
      }
      .img-container {
        margin: 10px;
      }
      .resizable-img {
        transition: width 0.2s ease;
      }
      .range-label {
        font-weight: bold;
        margin-left: 10px;
      }
    </style>
  </head>
  <body>

    <div class="container py-3">
      <form method="get" class="row g-3 align-items-center" id="preferencesForm">
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
                $selected = $limit === $opcao ? 'selected' : '';
                echo "<option value=\"$opcao\" $selected>$opcao</option>";
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
              $checked = in_array($ext, $extensoesSelecionadas) ? 'checked' : '';
              echo "<div class='form-check form-check-inline'>
                      <input class='form-check-input' type='checkbox' name='ext[]' value='$ext' id='ext_$ext' $checked>
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

    <div class="container-fluid bg-body-secondary">
      <?php
        $diretorio = 'image-bank';
        $arquivos = scandir($diretorio);
        $extensoesPermitidas = isset($_GET['ext']) ? $_GET['ext'] : $extensoesDisponiveis;

        $imagens = array_filter($arquivos, function($arquivo) use ($extensoesPermitidas) {
          $ext = pathinfo($arquivo, PATHINFO_EXTENSION);
          return $arquivo !== '.' && $arquivo !== '..' && in_array(strtolower($ext), $extensoesPermitidas);
        });

        $totalImagens = count($imagens);
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $paginaAtual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($paginaAtual - 1) * $limit;
        $imagensPaginadas = array_slice($imagens, $offset, $limit);

        echo '<figure class="figure"><div class="img-wrapper">';
        foreach ($imagensPaginadas as $arquivo) {
          echo '<div class="img-container">';
          echo '<img class="figure-img img-fluid rounded resizable-img" src="' . $diretorio . '/' . $arquivo . '" alt="' . $arquivo . '">';
          echo '<figcaption class="figure-caption">' . $arquivo . '</figcaption>';
          echo '</div>';
        }
        echo '</div></figure>';

        // Paginação compacta com botões « »
        $totalPaginas = ceil($totalImagens / $limit);
        if ($totalPaginas > 1) {
          echo '<nav class="fixed-bottom bg-light py-2 shadow"><ul class="pagination justify-content-center mb-0">';

          if ($paginaAtual > 1) {
            echo "<li class='page-item'><a class='page-link' href='?limit=$limit&page=1" . buildExtQuery($extensoesSelecionadas) . "'>&laquo;</a></li>";
          }

          $maxVisible = 5;
          $half = floor($maxVisible / 2);

          if ($totalPaginas <= $maxVisible) {
            $start = 1;
            $end = $totalPaginas;
          } elseif ($paginaAtual <= $half) {
            $start = 1;
            $end = $maxVisible;
          } elseif ($paginaAtual >= $totalPaginas - $half) {
            $start = $totalPaginas - $maxVisible + 1;
            $end = $totalPaginas;
          } else {
            $start = $paginaAtual - $half;
            $end = $paginaAtual + $half;
          }

          for ($i = $start; $i <= $end; $i++) {
            if ($i >= 1 && $i <= $totalPaginas) {
              $active = $i === $paginaAtual ? 'active' : '';
              echo "<li class='page-item $active'><a class='page-link' href='?limit=$limit&page=$i" . buildExtQuery($extensoesSelecionadas) . "'>$i</a></li>";
            }
          }

          if ($paginaAtual < $totalPaginas) {
            echo "<li class='page-item'><a class='page-link' href='?limit=$limit&page=$totalPaginas" . buildExtQuery($extensoesSelecionadas) . "'>&raquo;</a></li>";
          }

          echo '</ul></nav>';
        }

        // Função para manter os filtros nos links de paginação
        function buildExtQuery($exts) {
          $query = '';
          foreach ($exts as $e) {
            $query .= '&ext[]=' . urlencode($e);
          }
          return $query;
        }
      ?>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
      const rangeInput = document.getElementById('imageSizeRange');
      const rangeValue = document.getElementById('rangeValue');
      const limitSelect = document.getElementById('limitSelect');

      window.addEventListener('DOMContentLoaded', () => {
        const savedSize = localStorage.getItem('imageSize');
        const savedLimit = localStorage.getItem('imageLimit');

        if (savedSize) {
          rangeInput.value = savedSize;
          rangeValue.textContent = savedSize + 'px';
          updateImageSize(savedSize);
        }

        if (savedLimit) {
          limitSelect.value = savedLimit;
        }
      });

      const updateImageSize = (size) => {
        document.querySelectorAll('.resizable-img').forEach(img => {
          img.style.width = size + 'px';
          img.style.height = 'auto';
        });
        rangeValue.textContent = size + 'px';
      };

      rangeInput.addEventListener('input', () => {
        const size = rangeInput.value;
        localStorage.setItem('imageSize', size);
        updateImageSize(size);
      });

      limitSelect.addEventListener('change', () => {
        localStorage.setItem('imageLimit', limitSelect.value);
      });
    </script>
  </body>
</html>