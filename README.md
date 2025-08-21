# Batendo um papo com o Copilot desenvolvemos um visualizador de imagens!

**Nesta noite estava sem sono** e resolvi bater um papo com o Copilot e pedi para ele me ajudar a criar algo para visualizar algumas imagens.

## Recursos Principais

🖼️ Galeria de Imagens
- Imagens redimensionáveis via controle deslizante (range)
- Seleção por clique com destaque visual (borda e animação)
- Drag & drop para adicionar imagens ao painel lateral
- Limite de seleção configurável pelo usuário

🧭 Painel Lateral de Seleção
- Lista dinâmica de imagens selecionadas
- Miniaturas (thumbs) com botão de remoção individual
- Contador de imagens selecionadas
- Botão “Limpar Seleção” com confirmação e ícone

🎨 Personalização Visual
- Controle de tamanho das imagens com persistência
- Alternância de tema claro/escuro com salvamento automático
- Animação de feedback ao selecionar imagem (pulse)
- Fallback para imagens ausentes (fallback.png)

💾 Persistência de Dados
- Uso de localStorage para:
- Lista de imagens selecionadas
- Tamanho das imagens
- Limite de seleção
- Tema visual

🧠 Lógica Inteligente
- Validação de limite de seleção com alerta amigável
- Verificação de duplicidade ao selecionar imagens
- Delegação de eventos para melhor performance
- Funções utilitárias para salvar/carregar dados

🛠️ Estrutura Modular
- Código organizado em funções reutilizáveis
- Separação clara entre interface, lógica e persistência
- Fácil expansão para novos recursos (upload, ordenação, tags)




## 🧩 Estrutura do Projeto
- ✅ Galeria com imagens redimensionáveis e selecionáveis
- ✅ Painel lateral com lista de imagens selecionadas
- ✅ Drag & drop para adicionar imagens ao painel
- ✅ Tema claro/escuro com persistência
- ✅ Limite de seleção configurável
- ✅ Botão para limpar seleção
- ✅ Modo compacto do painel lateral
- ✅ Feedback visual com animação
- ✅ Fallback para imagens ausentes
- ✅ Código modular e otimizado


```php
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

```


# Wireframe (Esboço de Layout)

```txt
┌────────────────────────────┐
│ Início da Página (Load)   │
└────────────┬──────────────┘
             │
             ▼
┌────────────────────────────────────────────┐
│ Carrega preferências do localStorage       │
│ - Tamanho das imagens                      │
│ - Limite de seleção                        │
│ - Tema (claro/escuro)                      │
│ - Lista de imagens selecionadas            │
└────────────┬───────────────────────────────┘
             │
             ▼
┌────────────────────────────┐
│ Renderiza galeria de imagens│
└────────────┬──────────────┘
             │
             ▼
┌────────────────────────────────────────────┐
│ Usuário interage com controles:            │
│ - Slider de tamanho                        │
│ - Select de limite                         │
│ - Botão de tema                            │
│ - Botão de limpar seleção                  │
└────────────┬───────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────┐
│ Usuário clica ou arrasta imagem            │
└────────────┬───────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────┐
│ Verifica se imagem já está selecionada     │
│ ┌───────────────┐     ┌──────────────────┐ │
│ │ Sim           │     │ Não              │ │
│ └────┬──────────┘     └─────┬────────────┘ │
│      ▼                          ▼          │
│ Remove da lista           Verifica limite  │
│ Atualiza painel           ┌──────────────┐ │
│                           │ Limite ok?   │ │
│                           └────┬─────────┘ │
│                                ▼           │
│                         Adiciona à lista   │
│                         Atualiza painel    │
└────────────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────┐
│ Atualiza localStorage com nova seleção     │
└────────────────────────────────────────────┘
             │
             ▼
┌────────────────────────────┐
│ Fim da interação           │
└────────────────────────────┘
```

```txt
index.php
├─ PHP (POST / ZIP)
│   └─ ZipArchive → gera e envia o ZIP se houver arquivos
│
├─ <!doctype html>
└─ <html lang="pt-br">
    ├─ <head>
    │   ├─ <meta charset="utf-8">
    │   ├─ <meta name="viewport">
    │   ├─ <title>Visualizador de Imagens</title>
    │   ├─ <link href="bootstrap.min.css">
    │   └─ <style>…</style>
    │
    └─ <body>
        ├─ <aside id="selectedImagesPanel">
        │   ├─ <h5>Imagens selecionadas (<span id="selectedCount">0</span>)</h5>
        │   ├─ <ul id="selectedList"></ul>
        │   └─ <form action="download.php" method="post">
        │       ├─ <input type="hidden" id="filesInput" name="files">
        │       └─ <button>📦 Baixar ZIP</button>
        │
        ├─ <div class="container py-3">
        │   └─ <form id="preferencesForm" method="get" class="row g-3 align-items-center">
        │       ├─ Controle 1: Tamanho (input[type=range]#imageSizeRange + <span id="rangeValue">)
        │       ├─ Controle 2: Quantidade (select#limitSelect with opções PHP)
        │       └─ Controle 3: Tipos (checkboxes geradas em loop PHP)
        │       └─ <button>Aplicar filtros</button>
        │
        ├─ <div class="container-fluid bg-body-secondary">
        │   ├─ PHP → varre `image-bank`, filtra extensões, faz paginação
        │   ├─ <figure class="figure">
        │   │   └─ <div class="img-wrapper">
        │   │       ├─ .img-container*N (loop)
        │   │       │   ├─ <img class="resizable-img" draggable="true">
        │   │       │   └─ <figcaption>nome-do-arquivo</figcaption>
        │   │       └─ ...
        │   └─ Paginação (<nav class="fixed-bottom">…)
        │       ├─ « (página 1)
        │       ├─ números (1, 2, 3…)
        │       └─ » (última página)
        │
        ├─ <script src="bootstrap.bundle.min.js"></script>
        └─ <script> (interatividade)
            ├─ Leitura/escrita em localStorage (tamanho & limite)
            ├─ updateImageSize(size)
            ├─ Arrastar imagens (.resizable-img → dragstart → e.dataTransfer)
            ├─ Aside (dragover, dragleave, drop → adicionar ao painel)
            ├─ removeImage(filename)
            └─ updateSelected() → atualiza #filesInput e #selectedCount
```

```ML e CSS em tempo real, nossa ferramenta oferece a velocidade e os recursos que você precisa.

```txt
┌──────────────────────────────────────────────────────────┐
│ Navbar/Topo (título do app)                             │
├──────────────────────────────────────────────────────────┤
│ [ Formulário de Filtros ]    │                            │
│ ┌──────────────────────────┐ │    [ Painel Lateral        │
│ │ Tamanho: [————●————]      │ │    ┌───────────────────┐   │
│ │ Quantidade: [10 ▼]       │ │    │ Imagens Selecionadas│   │
│ │ Extensões: [ ] jpg [ ]…  │ │    │ ● arquivo1.jpg      │   │
│ │ [ Aplicar Filtros ]      │ │    │ ● foto2.png         │   │
│ └──────────────────────────┘ │    │ [📦 Baixar ZIP]      │   │
│                              │    └───────────────────┘   │
├──────────────────────────────────────────────────────────┤
│                     Galeria de Imagens                   │
│ ┌─────┐ ┌─────┐ ┌─────┐  … ┌─────┐                         │
│ │ IMG │ │ IMG │ │ IMG │     │ IMG │                         │
│ └─────┘ └─────┘ └─────┘     └─────┘                         │
│                                                  [« 1 2 3 »] │
└──────────────────────────────────────────────────────────┘
```

## Fluxograma do Fluxo de Dados

```
st=>start: Início
load=>operation: Carrega página (index.php)
form=>inputoutput: Lê filtros (GET params)
scan=>operation: Scanneia 'image-bank/' (scandir)
filter=>operation: Filtra por extensões
paginate=>operation: Pagina resultados
display=>operation: Exibe imagens + paginação

zip_decision=>condition: Usuário clicou em "Baixar ZIP"?
zip_gen=>operation: Gera ZIP com ZipArchive
send_zip=>operation: Envia ZIP e termina
end=>end: Fim

st->load->form->scan->filter->paginate->display->zip_decision
zip_decision(yes)->zip_gen->send_zip->end
zip_decision(no)->display
```


## Wireframe + Interações JS / localStorage

```
┌────────────────────────────────────────────────────────────┐
│ ↑ page load                                               │
│ JS: onDOMContentLoaded                                   │
│   • ler localStorage: imageSize, imageLimit               │
│   • aplicar valores nos controles (range, select)        │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│ [ Controles de Filtro ]                                    │
│   • Tamanho: [————●————] (JS + localStorage)               │
│   • Quantidade: [10 ▼] (JS + localStorage)                │
│   • Extensões: [✔ jpg] [ ] png …                          │
│   • [Aplicar filtros] ↔ GET ?limit=…&ext[]=…              │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│ JS: event → rangeInput.input                               │
│   • set localStorage.imageSize = valor                     │
│   • updateImageSize(valor)                                 │
│   • ajusta largura das .resizable-img                      │
│                                                            │
│ JS: event → limitSelect.change                             │
│   • set localStorage.imageLimit = valor                    │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│ GET → index.php                                            │
│ Backend:                                                    │
│   • scandir('image-bank/')                                 │
│   • filtrar extensões (GET ext[])                          │
│   • calcular paginação (GET limit, page)                   │
│   • renderizar galeria HTML                                │
│                                                            │
│ JS ao final:                                               │
│   • adiciona dragstart em .resizable-img                   │
│   • panel: dragover → highlight                            │
│   • panel: drop → add file no array selectedFiles          │
│   • renderizar <li> no <aside> + updateSelected()          │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│                Painel Lateral (<aside>)                    │
│   • mostra filenames selecionados                          │
│   • botões “✖” ao lado de cada item → removeImage()        │
│   • contador dinâmico (#selectedCount) ← JS                │
│   • <form method=POST action="download.php">               │
│       hidden input name=files = JSON.stringify(selected)   │
│       botão 📦 Baixar ZIP                                  │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│ JS: ao click em “Baixar ZIP”                              │
│   • form post → TOP PHP:                                   │
│   • json_decode(files)                                     │
│   • ZipArchive: create zip com image-bank/…                │
│   • header → download direto                               │
└────────────────────────────────────────────────────────────┘
```

## Como o localStorage se encaixa:

1.  Ao carregar a página: JS lê localStorage.imageSize e localStorage.imageLimit para pré-ajustar controles.
2. Quando o usuário move o slider: o novo tamanho vai para localStorage.imageSize; imagens redimensionam imediatamente.
3. Quando muda seleção de limite: valor vai para localStorage.imageLimit; ao próximo reload, o select vem pré-definido.
4. Filtros de extensão não estão em localStorage, pois a aplicação sempre os obtém do GET.

## Principais mudanças: v2

Principais mudanças:
- selectedFiles é carregado de localStorage e salvo nele (selectedImages) sempre que for alterado.
- Ao carregar a página, a lista e as bordas das imagens já marcadas são reconstruídas.
- Ao paginar (GET recarrega a página), tudo se reconstrói a partir de localStorage.
- O hidden input files e o contador no aside refletem sempre o estado atual.
O download.php (ou o topo de index.php) fica igual: ele lê $_POST['files'], faz json_decode e gera o ZIP. Com isso, sua seleção passa de página em página.


- O <aside> e o conteúdo principal em uma row do Bootstrap, com colunas adaptativas (col-12 col-lg-3 e col-12 col-lg-9). Agora o painel não sobrepõe a galeria.
- Removido posicionamento fixo e bordas conflitantes; o layout flui para telas pequenas (aside fica acima da galeria) e grandes (lado a lado).
- No painel, cada item selecionado exibe uma miniatura (<img class="selected-thumb">) ao lado do nome do arquivo, com botão para remover.
- A seleção continua persistente ao paginar, pois selectedFiles é carregado/salvo em localStorage.




