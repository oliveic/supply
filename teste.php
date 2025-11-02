<pre>
<?php
require 'connection.php';

/**
 * Lê o arquivo items.csv e transforma em um array cod => [descricao, tipo]
 * @return array Array associativo onde a chave é o código e o valor é [descricao, tipo]
 */
function lerItemsCSV(): array {
    $items = [];
    $file = file_get_contents('items.csv');
    $lines = explode("\n", $file);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        
        $parts = explode(';', $line);
        if (count($parts) >= 3) {
            $cod = trim($parts[0]);
            $descricao = trim($parts[1]);
            $tipo = trim($parts[2]);
            $items[$cod] = [$descricao, $tipo];
        }
    }
    
    return $items;
}


$file = file_get_contents('bom.csv');
$file = explode("\n", $file);

$file = array_map('trim', $file);
$file = array_map(function($item) { return explode(';', $item); }, $file);
$bom = $file;
// print_r($bom);
// die();
$codigos = [ '16598556', '16598557', '16598558', '16598559', '16598560', '16598561', '16598562', '16598563', '16598564', '16598565', '16598566', '16598567', '16598568', '16598569', '16598570', '16598571', '16598572', '16598573', '16598574', '16598575', '16598576', '16598577', '16598578', '16598579', '16598580', '16598581', '16598582', '16598583', '16598584', '16598585', '16598586', '16598587', '16598588', '16598589', '16598590', '16598591', '16598592', '16598593', '16598594', '16598595', '16598596', '16598597', '16598598', '16598599', '16598600', '16598601', '16598602', '16598603', '16598604', '16598605', '16598606', '16598607', '16598608', '16598609', '16598610', '16598611', '16598612', '16598613', '16598614', '16598615', '16598616', '16598617', '16598618', '16598619', '16598620', '16598621', '16598622', '16598623', '16598624', '16598625', '16598626', '16598627', '16598628', '16598629', '16598630', '16598631', '16598632', '16598633', '16598634', '16598635', '16598636', '16598637', '16598638', '16598639', '16598640', '16598641', '16598642', '16598643', '16598644', '16598645', '16598646', '16598647', '16598648', '16598649', '16598650', '16598651', '16598652', '16598653', '16598654', '16598655', '16598656', '16598657', '16598658', '16598659', '16598660', '16598661', '16598662', '16598663', '16598664', '16598665', '16598666', '16598667'];
  // Constrói índice pai -> lista de [componente, quantidade]
$lista_PF = [ 'P0000556', 'P0000557', 'P0000563', 'P0000564', 'P0000565', 'P0000566', 'P0000568', 'P0001032', 'P0001033', 'P0001089', 'P0001091', 'P0001092', 'P0001162', 'P0001163', 'P0001411', 'P0001412', 'P0001413', 'P0001415', 'P0001417', 'P0001418', 'P0001419', 'P0001420', 'P0001421', 'P0001422', 'P0001423', 'P0001425', 'P0001426', 'P0001427', 'P0001428', 'P0001430', 'P0001432', 'P0001433', 'P0001434', 'P0001435', 'P0001436', 'P0001437', 'P0001438', 'P0001439', 'P0001440', 'P0001441', 'P0001442', 'P0001443', 'P0001444', 'P0001445', 'P0001446', 'P0001447', 'P0001448', 'P0001449', 'P0001450', 'P0001451', 'P0001452', 'P0001748', 'P0001749', 'P0001755', 'P0001756', 'P0001758', 'P0001759', 'P0001760', 'P0001761', 'P0001764', 'P0001765', 'P0001766', 'P0001767', 'P0001768', 'P0001769', 'P0001770', 'P0001771', 'P0001772', 'P0001773', 'P0001776', 'P0001777', 'P0001778', 'P0001779', 'P0001780', 'P0001781', 'P0001782', 'P0001783', 'P0001784', 'P0001785', 'P0001786', 'P0001787', 'P0001788', 'P0001789', 'P0001790', 'P0001791', 'P0001792', 'P0001793', 'P0001794', 'P0001795', 'P0001796', 'P0001797', 'P0001804', 'P0001805', 'P0001806', 'P0001807', 'P0001827', 'P0001828', 'P0001829', 'P0001830', 'P0001831', 'P0001832', 'P0001833', 'P0001834', 'P0001835', 'P0001847', 'P0001914', 'P0001915', 'P0001950', 'P0001951', 'P0002084', 'P0002115', 'P0002116', ];

  function buildBomIndex(array $bom): array {
    $index = [];
    foreach ($bom as $row) {
      if (count($row) < 3) { continue; }
      $parentCode = (string)$row[0];
      $componentCode = (string)$row[1];
      $quantityPerParent = (float)$row[2];
      if (!isset($index[$parentCode])) {
        $index[$parentCode] = [];
      }
      $index[$parentCode][] = [$componentCode, $quantityPerParent];
    }
    return $index;
  }

  // Explode o BOM de um código pai acumulando quantidades dos componentes finais (folhas)
  function explodeBom(string $parentCode, array $bom): array {
    $index = buildBomIndex($bom);
    $totalsByComponent = [];
    $recursionStack = [];

    $walk = function (string $code, float $factor) use (&$walk, &$totalsByComponent, &$recursionStack, $index): void {
      if ($factor <= 0) { return; }
      if (isset($recursionStack[$code])) { return; } // proteção contra ciclos
      $recursionStack[$code] = true;

      if (!isset($index[$code])) {
        // folha: acumula diretamente
        if (!isset($totalsByComponent[$code])) { $totalsByComponent[$code] = 0.0; }
        $totalsByComponent[$code] += $factor;
        unset($recursionStack[$code]);
        return;
      }

      foreach ($index[$code] as [$childCode, $qtyPerParent]) {
        $childFactor = $factor * (float)$qtyPerParent;
        if ($childFactor <= 0) { continue; }
        if (!isset($index[$childCode])) {
          if (!isset($totalsByComponent[$childCode])) { $totalsByComponent[$childCode] = 0.0; }
          $totalsByComponent[$childCode] += $childFactor;
        } else {
          $walk($childCode, $childFactor);
        }
      }

      unset($recursionStack[$code]);
    };

    $walk($parentCode, 1.0);
    ksort($totalsByComponent);
    return $totalsByComponent;
  }

  // Exemplo usando o padrão solicitado
$newBom=[];
$sf = [];
$ignored = [];
$bomIndex = buildBomIndex($bom);

  foreach($codigos as $codigo) {
    if (!isset($bomIndex[$codigo])) { $ignored[] = $codigo; continue; }
    $exploded = explodeBom($codigo, $bom);
    $newBom[$codigo] = $exploded;
  }
  // print_r($newBom);

// print_r(lerItemsCSV());

// Carrega os itens com descrições e tipos
$items = lerItemsCSV();

// Exibe os componentes de $newBom com descrições e tipos
echo "<h2>Componentes do BOM com Descrições e Tipos</h2>";
foreach($newBom as $codigoPai => $componentes) {
    echo "<h3>Produto Pai: {$codigoPai}</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr><th>Código</th><th>Descrição</th><th>Tipo</th><th>Quantidade</th></tr>";
    
    foreach($componentes as $codComponente => $quantidade) {
        $descricao = isset($items[$codComponente]) ? $items[$codComponente][0] : 'Não encontrado';
        $tipo = isset($items[$codComponente]) ? $items[$codComponente][1] : 'N/A';
        
        echo "<tr>";
        echo "<td>{$codComponente}</td>";
        echo "<td>{$descricao}</td>";
        echo "<td>{$tipo}</td>";
        echo "<td>" . number_format($quantidade, 4) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}
  
  // die();
  // Persistência em banco: tabela bom(id, parentcod, item, qtd, versao)
  $versao = '202510';
  try {
    // Apaga a versão alvo
    $pdo->exec("DELETE FROM bom WHERE versao = '" . $versao . "'");

    // Inserção em transação
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO bom (parentcod, item, qtd, versao) VALUES (:parent, :item, :qtd, :versao)');

    foreach ($newBom as $parent => $components) {
      foreach ($components as $item => $qtd) {
        $stmt->execute([
          ':parent' => $parent,
          ':item' => $item,
          ':qtd' => (float)$qtd,
          ':versao' => $versao,
        ]);
      }
    }
    $pdo->commit();
  } catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    // echo 'Erro ao gravar BOM: ' . $e->getMessage();
  }
 
  $aux=[];
  // echo "<hr>";
  foreach($newBom as $codigo => $exploded) {
    foreach(array_keys($exploded) as $c) {
    $aux[] = $c;
    // echo "<hr>";
  }
}

  

  // Lista de itens ignorados (sem BOM)
  print_r($ignored);


    ?>
</pre>