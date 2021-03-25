<?php

// Ativa a exibição de erros do PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_WARNING);
// error_reporting(E_ALL); 

// Carrega o crawler
require_once 'CrawlerOLX.php';

// Instancia um objeto CrawlerOLX
$crawler = new CrawlerOLX();

// Define o PATH padrão
// $crawler->setWorkingDirectory();

// Extrai os parametros de busaca do arquivo de entrada
$inputData = $crawler->readXLSX('input.xlsx');

// Loop em $inputData
foreach ($inputData as $key => $search) {
	// Pesquisa os dados
	$dataReturned = $crawler->extractData($search);
	$dataReturned = $crawler->getSellerName($dataReturned);

	// Salva o resultado da escrita
	$writeRes = $crawler->generateOutputFile($dataReturned, 'output/');

	// Exibe uma mensagem caso o arquivo seja gerado com sucesso
	if ($writeRes) echo "Arquivo gerado com sucesso!\n";
}

?>