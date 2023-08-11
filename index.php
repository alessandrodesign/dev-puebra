<?php

class DbConnection
{
    private static $instance;
    private $connection;

    private function __construct()
    {
        $host = 'seu_host';
        $dbname = 'seu_banco_de_dados';
        $username = 'seu_usuario';
        $password = 'sua_senha';

        try {
            $this->connection = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Erro na conexão com o banco de dados: " . $e->getMessage();
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new DbConnection();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}

interface ContractRepositoryInterface
{
    public function getContractsInfo();
}

class ContractRepository implements ContractRepositoryInterface
{
    private $dbConnection;

    public function __construct(DbConnection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function getContractsInfo()
    {
        $connection = $this->dbConnection->getConnection();

        $query = "SELECT B.nome, C.verba, Ct.codigo, Ct.data_inclusao, Ct.valor, Ct.prazo 
                  FROM Tb_contrato AS Ct
                  INNER JOIN Tb_convenio_servico AS CS ON Ct.convenio_servico = CS.codigo
                  INNER JOIN Tb_convenio AS C ON CS.convenio = C.codigo
                  INNER JOIN Tb_banco AS B ON C.banco = B.codigo";

        $statement = $connection->prepare($query);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

class ContractPresenter
{
    public function presentContractsInfo($contractsInfo)
    {
        foreach ($contractsInfo as $contract) {
            echo "Nome do banco: {$contract['nome']}" . PHP_EOL;
            echo "Verba: {$contract['verba']}" . PHP_EOL;
            echo "Código do contrato: {$contract['codigo']}" . PHP_EOL;
            echo "Data de inclusão: {$contract['data_inclusao']}" . PHP_EOL;
            echo "Valor: {$contract['valor']}" . PHP_EOL;
            echo "Prazo: {$contract['prazo']}" . PHP_EOL;
        }
    }
}

$dbConnection = DbConnection::getInstance();
$contractRepository = new ContractRepository($dbConnection);
$contractPresenter = new ContractPresenter();

$contractsInfo = $contractRepository->getContractsInfo();
$contractPresenter->presentContractsInfo($contractsInfo);
