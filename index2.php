<?php

class Banco {
    public int $codigo;
    public string $nome;
}

class Convenio {
    public int $codigo;
    public string $convenio;
    public float $verba;
    public \Banco $banco;
}

class ConvenioServico {
    public int $codigo;
    public \Convenio $convenio;
    public string $servico;
}

class Contrato {
    public int $codigo;
    public int $prazo;
    public float $valor;
    public \DateTime $data_inclusao;
    public \ConvenioServico $convenio_servico;
}

class ConsultaContratos {
    private \PDO $connection; // Objeto de conexão PDO

    public function __construct(PDO $connection) {
        $this->connection = $connection;
    }

    public function listarContratosPorBanco() {
        $query = "SELECT
                      B.nome AS banco_nome,
                      C.verba AS verba,
                      MIN(CT.data_inclusao) AS data_minima,
                      MAX(CT.data_inclusao) AS data_maxima,
                      SUM(CT.valor) AS valor_total
                  FROM
                      Tb_contrato CT
                  JOIN
                      Tb_convenio_servico CS ON CT.convenio_servico = CS.codigo
                  JOIN
                      Tb_convenio C ON CS.convenio = C.codigo
                  JOIN
                      Tb_banco B ON C.banco = B.codigo
                  GROUP BY
                      B.nome, C.verba";

        $statement = $this->connection->prepare($query);
        $statement->execute();

        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        $relacaoContratos = [];

        foreach ($results as $result) {
            $banco = new \Banco();
            $banco->nome = $result['banco_nome'];

            $convenio = new \Convenio();
            $convenio->verba = $result['verba'];

            $contrato = new \Contrato();
            $contrato->data_inclusao = new \DateTime($result['data_minima']);
            $contrato->valor = $result['valor_total'];

            $convenio->banco = $banco;
            $contrato->convenio_servico = $convenio;

            $relacaoContratos[] = [
                'banco' => $banco->nome,
                'verba' => $convenio->verba,
                'data_minima' => $contrato->data_inclusao,
                'valor_total' => $contrato->valor,
            ];
        }

        return $relacaoContratos;
    }
}

$host = 'localhost';
$dbname = 'nome_do_banco';
$username = 'usuario';
$password = 'senha';

try {
    $connection = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $consultaContratos = new \ConsultaContratos($connection);
    $relacaoContratos = $consultaContratos->listarContratosPorBanco();

    // Exemplo de uso dos dados retornados
    foreach ($relacaoContratos as $contrato) {
        echo "Banco: {$contrato['banco']}, Verba: {$contrato['verba']}, Data Mínima: {$contrato['data_minima']->format('Y-m-d')}, Valor Total: {$contrato['valor_total']}\n";
    }
} catch (PDOException $e) {
    echo "Erro na conexão com o banco de dados: " . $e->getMessage();
}
