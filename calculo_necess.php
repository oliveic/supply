<?php

require 'connection.php';
$versao = '202510';
$inicio = 202510;

function ListaPeriodos($periodo) {
    $ano = substr($periodo, 0, 4);
    $mes = substr($periodo, 4, 2);
    // Cria a data representando o primeiro dia do mês
    $data = DateTime::createFromFormat('Y-m-d', "$ano-$mes-01");
    for ($i = 0; $i < 24; $i++) {
        $datas[] = $data->format('Ym');
        $data->modify('+1 month');
    }
    return $datas;
    prt($datas);
}

$periodos = ListaPeriodos($inicio);

$sql = "SELECT * FROM mpsdb WHERE versao = '$versao' AND tipo = 'pr'";
$r = mysqli_query($mysqli,$sql);
$mpsdb = mysqli_fetch_all($r,MYSQLI_ASSOC);
$plano=[];
foreach($mpsdb as $row) {
    $plano[$row['cod']] = array_slice($row, 3,-1);
}
// prt($plano); // Comentado para não interferir na saída HTML

// Buscar dados do BOM para a versão 202510
$sql_bom = "SELECT parentcod, item, qtd FROM bom WHERE versao = '$versao'";
$r_bom = mysqli_query($mysqli, $sql_bom);
$bom_data = mysqli_fetch_all($r_bom, MYSQLI_ASSOC);

// Organizar BOM por parentcod para facilitar acesso
$bom_por_patern = [];
foreach ($bom_data as $bom_row) {
    $parentcod = $bom_row['parentcod'];
    if (!isset($bom_por_patern[$parentcod])) {
        $bom_por_patern[$parentcod] = [];
    }
    $bom_por_patern[$parentcod][] = [
        'item' => $bom_row['item'],
        'qtd' => floatval($bom_row['qtd'])
    ];
}

// Array para armazenar os dados da tabela necessidade
$necessidade = [];

// Processar cada patern (código pai) do plano
foreach ($plano as $patern => $periodos_plano) {
    // Verificar se existe BOM para este patern
    if (!isset($bom_por_patern[$patern])) {
        continue; // Pula se não houver BOM para este patern
    }
    
    // Para cada item do BOM deste patern
    foreach ($bom_por_patern[$patern] as $bom_item) {
        $cod_item = $bom_item['item'];
        $qtd_bom = $bom_item['qtd'];
        
        // Calcular as quantidades para cada período (p00 a p23)
        $linha_necessidade = [
            'cod' => $cod_item,
            'versao' => $versao,
            'patern' => $patern,
            'p00' => round($periodos_plano['p00'] * $qtd_bom, 2),
            'p01' => round($periodos_plano['p01'] * $qtd_bom, 2),
            'p02' => round($periodos_plano['p02'] * $qtd_bom, 2),
            'p03' => round($periodos_plano['p03'] * $qtd_bom, 2),
            'p04' => round($periodos_plano['p04'] * $qtd_bom, 2),
            'p05' => round($periodos_plano['p05'] * $qtd_bom, 2),
            'p06' => round($periodos_plano['p06'] * $qtd_bom, 2),
            'p07' => round($periodos_plano['p07'] * $qtd_bom, 2),
            'p08' => round($periodos_plano['p08'] * $qtd_bom, 2),
            'p09' => round($periodos_plano['p09'] * $qtd_bom, 2),
            'p10' => round($periodos_plano['p10'] * $qtd_bom, 2),
            'p11' => round($periodos_plano['p11'] * $qtd_bom, 2),
            'p12' => round($periodos_plano['p12'] * $qtd_bom, 2),
            'p13' => round($periodos_plano['p13'] * $qtd_bom, 2),
            'p14' => round($periodos_plano['p14'] * $qtd_bom, 2),
            'p15' => round($periodos_plano['p15'] * $qtd_bom, 2),
            'p16' => round($periodos_plano['p16'] * $qtd_bom, 2),
            'p17' => round($periodos_plano['p17'] * $qtd_bom, 2),
            'p18' => round($periodos_plano['p18'] * $qtd_bom, 2),
            'p19' => round($periodos_plano['p19'] * $qtd_bom, 2),
            'p20' => round($periodos_plano['p20'] * $qtd_bom, 2),
            'p21' => round($periodos_plano['p21'] * $qtd_bom, 2),
            'p22' => round($periodos_plano['p22'] * $qtd_bom, 2),
            'p23' => round($periodos_plano['p23'] * $qtd_bom, 2)
        ];
        
        $necessidade[] = $linha_necessidade;
    }
}

// Atualizar tabela necessidades no banco de dados
$mensagem_gravacao = '';
$registros_gravados = 0;
try {
    // Apaga os registros da versão atual antes de inserir novos
    $pdo->exec("DELETE FROM necessidades WHERE versao = '$versao'");
    
    // Preparar statement para inserção
    $stmt = $pdo->prepare('INSERT INTO necessidades (cod, versao, patern, p00, p01, p02, p03, p04, p05, p06, p07, p08, p09, p10, p11, p12, p13, p14, p15, p16, p17, p18, p19, p20, p21, p22, p23) VALUES (:cod, :versao, :patern, :p00, :p01, :p02, :p03, :p04, :p05, :p06, :p07, :p08, :p09, :p10, :p11, :p12, :p13, :p14, :p15, :p16, :p17, :p18, :p19, :p20, :p21, :p22, :p23)');
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Inserir cada linha
    foreach ($necessidade as $linha) {
        $stmt->execute([
            ':cod' => $linha['cod'],
            ':versao' => $linha['versao'],
            ':patern' => $linha['patern'],
            ':p00' => $linha['p00'],
            ':p01' => $linha['p01'],
            ':p02' => $linha['p02'],
            ':p03' => $linha['p03'],
            ':p04' => $linha['p04'],
            ':p05' => $linha['p05'],
            ':p06' => $linha['p06'],
            ':p07' => $linha['p07'],
            ':p08' => $linha['p08'],
            ':p09' => $linha['p09'],
            ':p10' => $linha['p10'],
            ':p11' => $linha['p11'],
            ':p12' => $linha['p12'],
            ':p13' => $linha['p13'],
            ':p14' => $linha['p14'],
            ':p15' => $linha['p15'],
            ':p16' => $linha['p16'],
            ':p17' => $linha['p17'],
            ':p18' => $linha['p18'],
            ':p19' => $linha['p19'],
            ':p20' => $linha['p20'],
            ':p21' => $linha['p21'],
            ':p22' => $linha['p22'],
            ':p23' => $linha['p23']
        ]);
        $registros_gravados++;
    }
    
    // Confirmar transação
    $pdo->commit();
    $mensagem_gravacao = "<p style='color: green; font-weight: bold;'>✓ Dados gravados com sucesso! Total de registros inseridos: {$registros_gravados}</p>";
    
} catch (Exception $e) {
    // Reverter em caso de erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $mensagem_gravacao = "<p style='color: red; font-weight: bold;'>✗ Erro ao gravar na tabela necessidades: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Criar tabela HTML para verificação
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verificação - Tabela Necessidade</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: right;
        }
        th {
            background-color: #4CAF50;
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
        .zero {
            color: #999;
        }
        .plano-header {
            background-color: #2196F3;
        }
        .section {
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    <h1>Verificação - Cálculo de Necessidade</h1>
    <p><strong>Versão:</strong> <?php echo $versao; ?></p>
    <?php echo $mensagem_gravacao; ?>
    
    <div class="section">
        <h2>Tabela Plano (Dados Originais)</h2>
        <p><strong>Total de paterns:</strong> <?php echo count($plano); ?></p>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr class="plano-header">
                        <th>Patern</th>
                        <th>p00</th>
                        <th>p01</th>
                        <th>p02</th>
                        <th>p03</th>
                        <th>p04</th>
                        <th>p05</th>
                        <th>p06</th>
                        <th>p07</th>
                        <th>p08</th>
                        <th>p09</th>
                        <th>p10</th>
                        <th>p11</th>
                        <th>p12</th>
                        <th>p13</th>
                        <th>p14</th>
                        <th>p15</th>
                        <th>p16</th>
                        <th>p17</th>
                        <th>p18</th>
                        <th>p19</th>
                        <th>p20</th>
                        <th>p21</th>
                        <th>p22</th>
                        <th>p23</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    ksort($plano);
                    foreach ($plano as $patern => $periodos): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($patern); ?></strong></td>
                        <td class="<?php echo $periodos['p00'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p00'], 0); ?></td>
                        <td class="<?php echo $periodos['p01'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p01'], 0); ?></td>
                        <td class="<?php echo $periodos['p02'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p02'], 0); ?></td>
                        <td class="<?php echo $periodos['p03'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p03'], 0); ?></td>
                        <td class="<?php echo $periodos['p04'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p04'], 0); ?></td>
                        <td class="<?php echo $periodos['p05'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p05'], 0); ?></td>
                        <td class="<?php echo $periodos['p06'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p06'], 0); ?></td>
                        <td class="<?php echo $periodos['p07'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p07'], 0); ?></td>
                        <td class="<?php echo $periodos['p08'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p08'], 0); ?></td>
                        <td class="<?php echo $periodos['p09'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p09'], 0); ?></td>
                        <td class="<?php echo $periodos['p10'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p10'], 0); ?></td>
                        <td class="<?php echo $periodos['p11'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p11'], 0); ?></td>
                        <td class="<?php echo $periodos['p12'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p12'], 0); ?></td>
                        <td class="<?php echo $periodos['p13'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p13'], 0); ?></td>
                        <td class="<?php echo $periodos['p14'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p14'], 0); ?></td>
                        <td class="<?php echo $periodos['p15'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p15'], 0); ?></td>
                        <td class="<?php echo $periodos['p16'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p16'], 0); ?></td>
                        <td class="<?php echo $periodos['p17'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p17'], 0); ?></td>
                        <td class="<?php echo $periodos['p18'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p18'], 0); ?></td>
                        <td class="<?php echo $periodos['p19'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p19'], 0); ?></td>
                        <td class="<?php echo $periodos['p20'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p20'], 0); ?></td>
                        <td class="<?php echo $periodos['p21'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p21'], 0); ?></td>
                        <td class="<?php echo $periodos['p22'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p22'], 0); ?></td>
                        <td class="<?php echo $periodos['p23'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($periodos['p23'], 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <hr>
    
    <div class="section">
        <h2>Tabela Necessidade (Dados Calculados)</h2>
        <p><strong>Total de registros:</strong> <?php echo count($necessidade); ?></p>
        
        <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>cod</th>
                    <th>versao</th>
                    <th>patern</th>
                    <th>p00</th>
                    <th>p01</th>
                    <th>p02</th>
                    <th>p03</th>
                    <th>p04</th>
                    <th>p05</th>
                    <th>p06</th>
                    <th>p07</th>
                    <th>p08</th>
                    <th>p09</th>
                    <th>p10</th>
                    <th>p11</th>
                    <th>p12</th>
                    <th>p13</th>
                    <th>p14</th>
                    <th>p15</th>
                    <th>p16</th>
                    <th>p17</th>
                    <th>p18</th>
                    <th>p19</th>
                    <th>p20</th>
                    <th>p21</th>
                    <th>p22</th>
                    <th>p23</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($necessidade as $linha): ?>
                <tr>
                    <td><?php echo htmlspecialchars($linha['cod']); ?></td>
                    <td><?php echo htmlspecialchars($linha['versao']); ?></td>
                    <td><?php echo htmlspecialchars($linha['patern']); ?></td>
                    <td class="<?php echo $linha['p00'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p00'], 2); ?></td>
                    <td class="<?php echo $linha['p01'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p01'], 2); ?></td>
                    <td class="<?php echo $linha['p02'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p02'], 2); ?></td>
                    <td class="<?php echo $linha['p03'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p03'], 2); ?></td>
                    <td class="<?php echo $linha['p04'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p04'], 2); ?></td>
                    <td class="<?php echo $linha['p05'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p05'], 2); ?></td>
                    <td class="<?php echo $linha['p06'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p06'], 2); ?></td>
                    <td class="<?php echo $linha['p07'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p07'], 2); ?></td>
                    <td class="<?php echo $linha['p08'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p08'], 2); ?></td>
                    <td class="<?php echo $linha['p09'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p09'], 2); ?></td>
                    <td class="<?php echo $linha['p10'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p10'], 2); ?></td>
                    <td class="<?php echo $linha['p11'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p11'], 2); ?></td>
                    <td class="<?php echo $linha['p12'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p12'], 2); ?></td>
                    <td class="<?php echo $linha['p13'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p13'], 2); ?></td>
                    <td class="<?php echo $linha['p14'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p14'], 2); ?></td>
                    <td class="<?php echo $linha['p15'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p15'], 2); ?></td>
                    <td class="<?php echo $linha['p16'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p16'], 2); ?></td>
                    <td class="<?php echo $linha['p17'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p17'], 2); ?></td>
                    <td class="<?php echo $linha['p18'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p18'], 2); ?></td>
                    <td class="<?php echo $linha['p19'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p19'], 2); ?></td>
                    <td class="<?php echo $linha['p20'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p20'], 2); ?></td>
                    <td class="<?php echo $linha['p21'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p21'], 2); ?></td>
                    <td class="<?php echo $linha['p22'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p22'], 2); ?></td>
                    <td class="<?php echo $linha['p23'] == 0 ? 'zero' : ''; ?>"><?php echo number_format($linha['p23'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    
    <hr>
    <h2>Resumo por Patern</h2>
    <table>
        <thead>
            <tr>
                <th>Patern</th>
                <th>Quantidade de Itens</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $resumo_patern = [];
            foreach ($necessidade as $linha) {
                if (!isset($resumo_patern[$linha['patern']])) {
                    $resumo_patern[$linha['patern']] = 0;
                }
                $resumo_patern[$linha['patern']]++;
            }
            ksort($resumo_patern);
            foreach ($resumo_patern as $patern => $qtd): ?>
            <tr>
                <td><?php echo htmlspecialchars($patern); ?></td>
                <td><?php echo $qtd; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php
// prt();

?>