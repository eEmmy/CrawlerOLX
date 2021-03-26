<?php

// Inclui o SimpleXLSX ao arquivo
require_once "SimpleXLSX.php";

// Inlcui o SimpleXLSXGen ao arquivo
require_once "XLSXWriter.php";

/**
 * Crawler para recolher dados da OLX a partir de uma planilha de entrada.
 */
class CrawlerOLX
{
	/**
	 * Guarda o PATH do diretório de trabalho.
	 *
	 * @var String $path
	 */
	protected $path;

	/**
	 * Guarda o nome do arquivo de saida.
	 *
	 * @var String $outputFilename
	 */
	protected $outputFilename;

	/**
	 * Guarda a quantidade de anuncios.
	 *
	 * @var String $outputFilename
	 */
	protected $adsAmount;

	/**
	 * Guarda a quantidade de paginas.
	 *
	 * @var String $outputFilename
	 */
	protected $pagesAmount;

	/**
	 * Extrai dados de uma planilha.
	 *
	 * @param String $inputFile
	 *
	 * @return Array $data
	 */
	public function readXLSX($inputFile)
	{
		// Pega os dados do arquivo
		$file = SimpleXLSX::parse('input/' . $inputFile);
		$file = $file->rows();	

		// Array de dados
		$data = array();

		// Loop em $file
		foreach ($file as $row => $rowData) {
			// Pula a primeira linha
			if ($row === 0) continue;

			// Guarda os dados
			$data[$row-1] = array(
				'name' => $rowData[0],
				'outputFilename' => $rowData[4]
			);
		}

		// Retorna os dados extraidos
		return $data;
	}

	/**
	 * Pega o conteudo de uma página.
	 *
	 * @param String $url
	 *
	 * @return String $pageContents
	 */
	protected function getPageContents($url)
	{
		// Pega o conteudo da página
		$pageContents = file_get_contents($url);

		// Retorna o conteudo da pagina encodado em UTF8
		return $pageContents;
	}

	/**
	 * Filtra os dados de uma lista HTML de anuncios.
	 *
	 * @param String $pageContents
	 *
	 * @return Array $data
	 */
	protected function filterULdata($pageContents)
	{
		// Inicia um documento DOM
		$doc = new DOMDocument();
		$doc->loadHTML($pageContents);

		// Pega o conteudo HTML do documento
		$xpath = new DOMXPath($doc);

		// Extrai a lista de anuncios
		$ads = $xpath->query("//ul[@id='ad-list']");
		$ads = $ads->item(0);

		// Guarda a quantidade de anuncios retornada
		$adsAmount = $ads->getElementsByTagName('li')->length;

		// Define a quantidade de anuncios
		$this->adsAmount = $adsAmount;

		// Array de dados
		$data = array();

		// Loop for (0 a 17)
		for ($i=0; $i < $adsAmount; $i++) {
			// Guarda apenas o elemento atual
			$ad = $ads->getElementsByTagName('li')->item($i);

			// Evita Anuncios
			if ($ad->firstElementChild === null) continue;

			// Salva os stributos para pegar o link do produto
			$data[$i]['link'] = $ad->getElementsByTagName('a')->item(0)->attributes;

			// Loop em $data[$i]['link']
			foreach ($data[$i]['link'] as $key => $value) {
				// Se o atributo for href, guarda o link
				if ($key == 'href') $data[$i]['link'] = $value->value;
			}			

			// Pega a url da imagem do produto
			$data[$i]['img'] = $ad->getElementsByTagName('img')->item(0)->attributes[0]->value;

			// Pega o nome do produto
			$data[$i]['name'] = $ad->getElementsByTagName('h2')->item(0)->nodeValue;

			// Verifica se o span atual é igual a "Anunciante online"
			if ($ad->getElementsByTagName('span')->item(2)->nodeValue == "Anunciante online" || $ad->getElementsByTagName('span')->item(1)->nodeValue == "Anunciante online" || $ad->getElementsByTagName('span')->item(3)->nodeValue == "Anunciante online") {
				// Pega o preço do produto
				if (strpos($data[$i]['link'], 'autos-e-pecas') === false) $data[$i]['price'] = $ad->getElementsByTagName('span')->item(3)->nodeValue;
				else $data[$i]['price'] = $ad->getElementsByTagName('span')->item(4)->nodeValue;

				// Evita anuncios promovendo outros anuncios
				if ($data[$i]['price'] == 'DESTAQUE') {
					unset($data[$i]);
					$i--;
					continue;
				}

				// Guarda a cidade e o estado
				$adOrigin = "";

				// Verifica se o span atual é igual ao desconto do preço
				if (strpos($ad->getElementsByTagName('span')->item(4)->nodeValue, '$') !== false) {
					// Pega a cidade e o estado
					$adOrigin = $ad->getElementsByTagName('span')->item(7)->nodeValue;
				} else {
					// Pega a cidade e o estado
					$adOrigin = $ad->getElementsByTagName('span')->item(6)->nodeValue;
				}

				// Separa a cidade do estado
				$adOrigin = explode(" - ", $adOrigin);
				$data[$i]['city'] = $adOrigin[0];
				$data[$i]['uf'] = $adOrigin[1];
			}
			else {
				// Pega o preço do produto
				if (strpos($data[$i]['link'], 'autos-e-pecas') === false) $data[$i]['price'] = $ad->getElementsByTagName('span')->item(1)->nodeValue;
				else $data[$i]['price'] = $ad->getElementsByTagName('span')->item(2)->nodeValue;

				// Evita anuncios promovendo outros anuncios
				if ($data[$i]['price'] == 'DESTAQUE') {
					unset($data[$i]);
					continue;
				}

				// Guarda a cidade e o estado
				$adOrigin = "";

				// Verifica se o span atual é igual ao desconto do preço
				if (strpos($ad->getElementsByTagName('span')->item(2)->nodeValue, '$') !== false) {
					// Pega a cidade e o estado
					$adOrigin = $ad->getElementsByTagName('span')->item(5)->nodeValue;
				} else {
					// Pega a cidade e o estado
					$adOrigin = $ad->getElementsByTagName('span')->item(4)->nodeValue;
				}

				// Separa a cidade do estado
				$adOrigin = explode(" - ", $adOrigin);
				$data[$i]['city'] = $adOrigin[0];
				$data[$i]['uf'] = $adOrigin[1];
			}
		}

		// Retorna os dados obtidos
		return $data;
	}

	/**
	 * Conta quantas páginas uma busca retornou.
	 *
	 * @param String $pageContents
	 *
	 * @return Int $pagesAmount
	 */
	protected function countPages($pageContents)
	{
		// Inicia um documento DOM
		$doc = new DOMDocument();
		$doc->loadHTML($pageContents);

		// Pega o conteudo HTML do documento
		$xpath = new DOMXPath($doc);

		// Extrai a lista de anuncios
		$lastPage = $xpath->query("//a[@data-lurker-detail='last_page']");
		$lastPage = $lastPage->item(0);

		// Extrai o link da página
		$lastPageLink = $lastPage->attributes[0]->value;

		// Extrai o numero de paginas
		$pagesAmount = substr($lastPageLink, strpos($lastPageLink, '=')+1);
		$pagesAmount = explode('&', $pagesAmount);
		$pagesAmount = $pagesAmount[0];
		
		// Converte de string para int
		$pagesAmount = intval($pagesAmount);

		// Verifica se existe mais de uma pagina
		if (strpos($lastPageLink, 'o=') === false) $pagesAmount = 1;

		// Retorna $pagesAmount
		return $pagesAmount;
	}

	/**
	 * Extrai os dados de anuncios do produto informado.
	 *
	 * @param Array $inputData
	 *
	 * @return void
	 */
	public function extractData($inputData)
	{
		// Verifica se todos os parametros necessários existem
		if (!isset($inputData['name']) || !isset($inputData['outputFilename'])) {
			// Encerra a aplicação e exibe uma mensagem de erro
			die('Os parametros necessários para extrair os dados não foram encontrados. Certifique-se de que existam os parametros "name" e "outputFilename" dentro do array de entrada.');
		}

		echo "Recuperando anuncios\n";
		
		$this->outputFilename = $inputData['outputFilename'];

		// Guarda o conteudo da página
		$pageContents = "";

		// Formata o nome do produto para os parametros de busca da OLX
		$searchString = str_replace(" ", "+", $inputData['name']);

		// Pega o conteudo da página
		$pageContents = $this->getPageContents("https://www.olx.com.br/brasil?q={$searchString}");

		// Pega quantas páginas existem
		$pagesAmount = $this->countPages($pageContents);

		// Guarda os dados obtidos
		$data = array();

		for ($i=1; $i <= $pagesAmount; $i++) {
			// Pega o conteudo da pagina atual
			$pageContents = $this->getPageContents("https://www.olx.com.br/brasil?o={$i}&q={$searchString}");

			// Filtra os dados da página
			$data[$i] = @$this->filterULdata($pageContents);
		}

		echo "\nExtraindo dados\n";

		// Retorna os dados obtidos, paginados e ordenados
		return $data;
	}

	/**
	 * Extrai o nome do vendedor de dentro do anuncio.
	 *
	 * @param Array $data
	 *
	 * @return Array $data
	 */
	public function getSellerName($data)
	{
		$totalItems = 0;
		$percentage = 0;
		$counter = 0;

		// Loop para o contador
		foreach ($data as $page => $items) {
			foreach ($items as $item => $attr) {
				$totalItems++;
			}
		}

		// Define o enconding das strings
		mb_internal_encoding("UTF-8");

		// Loop em $data
		foreach ($data as $page => $items) {

			// Loop em $items
			foreach ($items as $item => $attrs) {
				$counter++;
				$pageContents = $this->getPageContents($attrs['link']);

				// Localiza e recorta o nome do vendedor
				$target = mb_substr($pageContents, mb_strpos($pageContents, "sellerName")+13);
				$target = str_replace(mb_substr($target, mb_strpos($target, '"')), '', $target);

				// Guarda o nome do vendedor nas informações do anuncio
				$data[$page][$item]['sellerName'] = $target;

				echo "{$counter}/{$totalItems}\n";
			}
		}

		// Retorna os dados obtidos
		return $data;
	}

	/**
	 * Gera o arquivo de saida a partir de um array de entrada.
	 *
	 * @param Array $inputData
	 * @param String $outputFolder (opcional)
	 *
	 * @return void
	 */
	public function generateOutputFile($inputData, $outputFolder='')
	{
		// Guarda as linhas do arquivo
		$fileRows = array(
			['ID do produto', 'Nome', 'Link', 'Loja', 'Imagem', 'Preço', 'Vendedor', 'Cidade', 'Estado', 'Estoque inicial', 'Estoque atual', 'Estoque vendido']
		);

		// Controlador de loop
		$i = 1;

		// Loop em $inputData
		foreach ($inputData as $page => $items) {
			// Loop em $items
			foreach ($items as $item => $attr) {
				// Adiciona os dados da linha atual ao arquivo
				$fileRows[$i] = [' ', $attr['name'], $attr['link'], 'OLX', $attr['img'], $attr['price'].',00', $attr['sellerName'], $attr['city'], $attr['uf'], ' ', ' ', ' '];

				// Incrementa o controlador de loop
				$i++;
			}
		}

		// Tenta gravar o arquivo
		$writer = new XLSXWriter();
		$writer->writeSheet($fileRows);
		$writer->writeToFile('output/' . $this->outputFilename);

		// Retorna true
		return true;
	}
}

?>