<?php

require '../connection.php';

function cobertura ($e,$arr) {
    $c = 0;
    foreach ($arr as $key => $value) {
        if($e < $value) return $value == 0 ? 99 : $c + $e/$value;
        else
        {
            $c++;
            $e -= $value;
        }
    }
    return 99;
}

function eio ($c, $pv) {
    $pv = array_values($pv);
//     echo 'c: ' . $c . 'pv: ' . print_r($pv, true) . '<br>';
    if(count($pv) < $c) { return 0; }
    else
    {
        $r = $c - intval($c);
        $s=0;
        $index=0;
        for ($i=0; $i < $c; $i++) { 
            $s += floatval($pv[$i]);
            $index = $i;
        }
        if ($r == 0) return $s;
        else return $s + ($pv[$index+1] * $r);
    }
}

$function_exists_guard = true; // no-op to keep structure clear

/**
 * Determina o período de referência do pedido.
 * Regras: se periodo_confirmado > 0 usa-lo; caso contrário usa periodo.
 * Se o período resultante for menor que $inicio, retorna $inicio.
 */
function periodoReferencia(array $pedido, int $inicio) {
    $pc = isset($pedido['periodo_confirmado']) ? intval($pedido['periodo_confirmado']) : 0;
    $p = isset($pedido['periodo']) ? intval($pedido['periodo']) : 0;
    $ref = ($pc > 0) ? $pc : $p;
    if ($ref < $inicio) { $ref = $inicio; }
    return $ref;
}

/**
 * Soma quantidades por período a partir de uma lista de pedidos.
 * @param array $pedidos Lista com chaves: cod, qtd, periodo
 * @param string|null $cod Filtra por código específico (opcional)
 * @return array [periodo(YYYYMM) => soma_quantidade]
 */
function somaPedidosPorPeriodo(array $pedidos, $cod = null, $inicio = null) {
    $somas = [];
    foreach ($pedidos as $p) {
        if ($cod !== null && (!isset($p['cod']) || $p['cod'] != $cod)) { continue; }
        if (!isset($p['periodo']) || !isset($p['qtd'])) { continue; }
        $periodo = ($inicio === null)
            ? intval($p['periodo'])
            : periodoReferencia($p, intval($inicio));
        $qtd = floatval($p['qtd']);
        if (!isset($somas[$periodo])) { $somas[$periodo] = 0.0; }
        $somas[$periodo] += $qtd;
    }
    ksort($somas);
    return $somas;
}

/**
 * Constrói um vetor indexado (0..$horizonte-1) somando pedidos por período relativo a $inicio (YYYYMM).
 * Períodos fora da janela são ignorados.
 * @param array $pedidos Lista com chaves: cod, qtd, periodo
 * @param int $inicio Período base em YYYYMM
 * @param int $horizonte Número de períodos (padrão 24)
 * @param string|null $cod Filtra por código específico (opcional)
 * @return array float[] tamanho $horizonte
 */
function somaPedidosEmJanela(array $pedidos, int $inicio, int $horizonte = 24, $cod = null) {
    $out = array_fill(0, $horizonte, 0.0);
    $inicioAno = intval(substr((string)$inicio, 0, 4));
    $inicioMes = intval(substr((string)$inicio, 4, 2));
    foreach ($pedidos as $p) {
        if ($cod !== null && (!isset($p['cod']) || $p['cod'] != $cod)) { continue; }
        if (!isset($p['periodo']) || !isset($p['qtd'])) { continue; }
        $per = (string)periodoReferencia($p, $inicio);
        $ano = intval(substr($per, 0, 4));
        $mes = intval(substr($per, 4, 2));
        $delta = ($ano - $inicioAno) * 12 + ($mes - $inicioMes);
        if ($delta >= 0 && $delta < $horizonte) {
            $out[$delta] += floatval($p['qtd']);
        }
    }
    return $out;
}
if(isset($_POST)) { extract($_POST); }
// echo json_encode([
//     'versao' => $versao,
//     'inicio' => $inicio,
//     'lista' => $lista
// ]);


$l = "'" . implode("','",$lista) . "'";

$sql = "SELECT cod, sum(p00) as s00, sum(p01) as s01, sum(p02) as s02, sum(p03) as s03, sum(p04) as s04, sum(p05) as s05, sum(p06) as s06, sum(p07) as s07, sum(p08) as s08, sum(p09) as s09, sum(p10) as s10, sum(p11) as s11, sum(p12) as s12, sum(p13) as s13, sum(p14) as s14, sum(p15) as s15, sum(p16) as s16, sum(p17) as s17, sum(p18) as s18, sum(p19) as s19, sum(p20) as s20, sum(p21) as s21, sum(p22) as s22, sum(p23) as s23
FROM necessidades WHERE versao='$versao' AND cod IN ($l) GROUP BY cod";
$result = mysqli_query($mysqli, $sql);
$necessidades = mysqli_fetch_all($result, MYSQLI_ASSOC);
$aux = [];
foreach($necessidades as $n) {
    $aux[$n['cod']] = array_values(array_slice($n,1));
}
$necessidades = $aux;
// prt($necessidades);
$sql = "SELECT cod, itens.nome as nome, tipo, leadtime, multiplo, cobertura, minimo, familia, fornecedores.nome as fornecedor
FROM itens, fornecedores WHERE itens.id_fornecedor = fornecedores.id 
AND cod IN ($l)";
$result = mysqli_query($mysqli, $sql);
$itens = mysqli_fetch_all($result, MYSQLI_ASSOC);
$aux = [];
foreach($itens as $i) {
    $aux[$i['cod']] = $i;
}
$itens = $aux;

$sql = "SELECT  p.cod, po, qtd, data, confirmada, YEAR(data)*100+month(data) as periodo, YEAR(confirmada)*100+month(confirmada) as periodo_confirmado, versao, f.nome FROM pedidos as p, fornecedores as f 
WHERE p.id_forn=f.id  AND p.cod IN ($l) AND versao='$versao'";
$result = mysqli_query($mysqli, $sql);
$pedidos = mysqli_fetch_all($result, MYSQLI_ASSOC);

$sql = "SELECT cod, sum(qtd) as qtd FROM estoque_insumos WHERE versao = '$versao' GROUP BY cod";
$result = mysqli_query($mysqli, $sql);
$estoque_insumos = mysqli_fetch_all($result, MYSQLI_ASSOC);
$aux = [];
foreach($estoque_insumos as $e) {
    $aux[$e['cod']] = $e;
}
$estoque_insumos = $aux;

// helper para gerar 24 períodos a partir de $versao (formato YYYYMM)
$geraPeriodos = function($inicioYm) {
    $ano = intval(substr($inicioYm, 0, 4));
    $mes = intval(substr($inicioYm, 4, 2));
    $labels = [];
    for ($i = 0; $i < 24; $i++) {
        $labels[] = sprintf('%04d%02d', $ano, $mes);
        $mes++;
        if ($mes > 12) { $mes = 1; $ano++; }
    }
    return $labels;
};

// estilo simples para tabelas

// echo '<hr>Tabelas<hr>';
$data=[];
foreach($lista as $cod) {
    $pf = $itens[$cod];
    $n = $necessidades[$cod];
    $aux = [];
    foreach($pedidos as $po) {
        if($po['cod'] == $cod) {
            $aux[] = $po;
        }
    }
    // $po = $pedidos[$cod];
    // $ei = $estoque_insumos[$cod];
    $data[$cod]['pf'] = $pf;
    $data[$cod]['n'] = array_values($n);
    $data[$cod]['ped'] = $aux;
    if (isset($estoque_insumos[$cod]))
        $data[$cod]['ei'] = $estoque_insumos[$cod]['qtd'];
    else
        $data[$cod]['ei'] = 0;

    // Renderização das tabelas para cada item

    $periodos = $geraPeriodos($inicio);

 
}
// echo json_encode($data);
// die();
// print_r(somaPedidosEmJanela($data['16700562']['ped'],$inicio,24,'16700562'));

function build_grade($cod='16700562') {
    // echo $cod . "<br>";
    global $data;
    global $inicio;
    global $versao;
    $db=$data[$cod];
    $table= [];
    $cp = somaPedidosEmJanela($db['ped'],$inicio,24,$cod); // compras planejadas
    $ei = array_fill(0,23,0);
    $n = $db['n'];
    array_unshift($ei,$db['ei']);
    $eio=[];
    $cob = $db['pf']['cobertura'];
    for ($i=0; $i < 23; $i++) { 
        $eio[$i+1] = eio(2,array_slice($db['n'],$i+1,-1));
    }
    // $eio[0]=0;
    array_unshift($eio,0);

    for ($i=0; $i < 23; $i++) {
        $ei[$i+1] = $ei[$i] - $n[$i] + $cp[$i];
        $nl[$i] = $eio[$i+1] > $ei[$i+1] ? $eio[$i+1] - $ei[$i+1] : 0;
        $cober[$i] = round(cobertura($ei[$i],array_slice($n,$i,-1)),2);
    }
    unset($db['n']);
    unset($db['ei']);
    $db['table'] = [
        'ei' => $ei,
        'cob' => $cober,
        'eio' => $eio,
        'n' => $n,
        'nl' => $nl,
        'cp' => $cp
    ];
    return [ $cod => $db];
}

$aux = [];

function lista_pf ($l) {
    foreach ($l as $key => $value) {
        # code...
        // echo $value . "<br>";
        $aux[] = build_grade($value);
    }
    return $aux;
}

echo json_encode( lista_pf($lista));



?>