<?php

require '../connection.php';
extract($_POST);
switch ($tipo) {
    case '1':
        $sql = "SELECT versao, descricao, inicio, editavel FROM versoes ORDER BY versao DESC";
        $result = mysqli_query($mysqli, $sql);
        
        if ($result) {
            $versoes = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $versoes[] = [
                    'versao' => $row['versao'],
                    'descricao' => $row['descricao'],
                    'inicio' => $row['inicio'],
                    'editavel' => $row['editavel']
                ];
            }
            echo json_encode(['success' => true, 'versoes' => $versoes]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar versões']);
        }
        break;
    case '1.1':
        // Retorna apenas a versão mais atual baseada no campo atualizar (timestamp)
        $sql = "SELECT versao, descricao, inicio, editavel FROM versoes ORDER BY atualizar DESC LIMIT 1";
        $result = mysqli_query($mysqli, $sql);
        
        if ($result && $row = mysqli_fetch_assoc($result)) {
            echo json_encode([
                'success' => true, 
                'versao' => $row['versao'],
                'descricao' => $row['descricao'],
                'inicio' => $row['inicio'],
                'editavel' => $row['editavel']
            ]);
        } else {
            // Fallback: se não encontrar ou campo atualizar não existir, pega a última versão por número
            $sql = "SELECT versao, descricao, inicio, editavel FROM versoes ORDER BY versao DESC LIMIT 1";
            $result = mysqli_query($mysqli, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                echo json_encode([
                    'success' => true, 
                    'versao' => $row['versao'],
                    'descricao' => $row['descricao'],
                    'inicio' => $row['inicio'],
                    'editavel' => $row['editavel']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao buscar versão atual']);
            }
        }
        break;
    case '2':
        $ano = substr($periodo, 0, 4);
        $mes = substr($periodo, 4, 2);
        
        // Mapeamento de meses em inglês para português
        $mesesPT = [
            'Jan' => 'Jan', 'Feb' => 'Fev', 'Mar' => 'Mar', 'Apr' => 'Abr',
            'May' => 'Mai', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago',
            'Sep' => 'Set', 'Oct' => 'Out', 'Nov' => 'Nov', 'Dec' => 'Dez'
        ];
        
        // Cria a data representando o primeiro dia do mês
        $data = DateTime::createFromFormat('Y-m-d', "$ano-$mes-01");
            for ($i = 0; $i < 24; $i++) {
                $mesIngles = $data->format('M');
                $ano = $data->format('y');
                $mesPortugues = $mesesPT[$mesIngles];
                $datas[] = $mesPortugues . '/' . $ano;
                $data->modify('+1 month');
            }
        echo json_encode($datas);
        break;
    case '3':
        $sql = "SELECT cod, itens.nome as nome, tipo, leadtime, multiplo, cobertura, minimo, familia, fornecedores.nome as fornecedor FROM itens, fornecedores WHERE itens.id_fornecedor = fornecedores.id";
        $result = mysqli_query($mysqli, $sql);
        
        if ($result) {
            $itens = mysqli_fetch_all($result, MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'itens' => $itens]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao buscar itens', 'itens' => []]);
        }
        break;
}

?>