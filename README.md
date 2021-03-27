# CrawlerOLX
Script para recuperar dados de anuncios da [OLX](https://www.olx.com.br/) com base em uma planilha de entrada.

### Indice
* Sobre o código
	* Organização de diretórios
* Instalação
* Configurações
* Uso
	* Planillha de entrada
	* Processamento de dados
	* O que fazer caso o robô caia no meio de uma solicitação
* Como implementar
* Depêndencias
* Páginas fonte
* Contato

## Sobre o código
O script foi inteiramente desenvolvido em PHP 8, mas também é compativel com PHP 7. Nomes de funções e variaveis estão em Inglês Americano, porém todos os comentários em blocos de código estão em Português do Brasil.

O script lê dados da planilha de entrada recursivamente, ou seja, funciona com multiplas linhas de uma só vez, gravando os arquivos conforme a busca atual é encerrada.

Os caminhos de inclusão dos scripts abaico partem do prinicípio que todos os arquivos estejam no mesmo diretório. Caso isso não seja possível, ou o usuário simplesmente prefira outra estruturação de diretórios, será necessário alterar as linha que possuem a keyword *require_once*, onde os caminhos são definidos.

#### Organização de diretórios
* **input/** - Possui o arquivo *input.xlsx*, arquivo responsável pela entrada de dados. Dentro dessa planilha, são recuperados o produto que será pesquisado e o nome do arquivo de saída.
* **output/** - Possui um arquivo *output.xlsx* demonstrativo para o usuário visualizar como será o formato da saída. Os resultados presentes nesse arquivo são gerados a partir de uma busca real. Caso a configuração padrão não seja alterada, diretório padrão para arquivos de saída.
* **CrawlerOLX.php** - Script PHP que contém a classe responsável por todos os métodos do crawler.
* **SimpleXLSX.php** e **XLSXWriter.php** - Arquivos responsáveis por ler e escrever arquivos com a extensão *xlsx*, respectivamente. Dependencias de **CrawlerOLX.php**. Caso o caminho de inclusão não seja alterado dentro deste último, precisarão estar dentro do mesmo diretório.
* **index.php** - Arquivo responsável por automatizar o processo de busca e gravação de dados. aso o caminho de inclusão de **Crawler.php** não seja alterado dentro deste, precisarão estar dentro do mesmo diretório.

## Instalação
Para instalar o CrawlerOLX, basta baixar ou clonar esse repositório e copiar os arquivos para alguma hospedagem.

* **Microsoft Windows**:
> 1. Instalar o [XAMPP](https://www.apachefriends.org/pt_br/download.html)
> 2. Baixar esse repositório.

* **Debian/Ubuntu linux based**:
> 1. sudo apt-get install php -y
> 2. sudo apt-get install git -y
> 3. git clone https://github.com/eEmmy/CrawlerOLX.git

* **Arch linux**:
> 1. sudo pacman -S php
> 2. sudo pacman -S git
> 3. git clone https://github.com/eEmmy/CrawlerOLX.git

## Configurações
Além dos caminhos de inclusão dos arquvos, existem també mais algumas configurações que podem ser alteradas conforme a necessidade do usuário na hora de implementar o crawler.

* Diretório de trabalho *(**index.php**, Linha 24)* - Aqui você pode alterar o diretório de trabalho. Caso modifique a estruturação dos arquivos, você deve definir qual a pasta padrão para que o script procure os arquivos solicitados. Aqui podem ser usados alguns pârametros do PHP, como $_SERVER["DOCUMENT_ROOT"], que indica para começar da raiz do servidor; e DIRECTORY_SEPARATOR, que garante total compatibilidade com o tipo de sistema operacional do seu servidor, independente de qual seja ele. (Nota: Isso não é necessário, sendo empregado seu uso apenas por convenção do padrão de desenvolvimento do desenvolvedor inicial).
* Caminho da planilha de entrada *(**index.php**, Linha 27)* - Aqui você deve definir qual o caminho da planilha de entrada (Incluindo o próprio arquivo .xlsx), lembrando que o script procurará por esse arquivo partindo do diretório de trabalho definido anteriormente. O padrão é pegar o arquivo **input/input.xlsx**.
* Caminho da planilha de saída *(**index.php**, Linhas 36)* - Aqui você deve definir em qual pasta os arquivos de saída serão salvos (Nota: Não incluir nomes de arquivo, pois o mesmo já é definido na planilha de entrada), lembrando que o script procurará por essa pasta partindo do diretório de trabalho definido anteriormente. O padrão é salvar dentro de **output/**.

**Nota**: Ao definir caminhos, sempre terminar com uma barra (/), ou a constante DIRECTORY_SEPARATOR.

## Uso
É importante lembrar que as planilhas devem sempre estar com formato XLSX, não sendo compátivel com planilhas de outros formatos (XLS, CSV, etc.).

#### Planilha de dados
A planilha de entrada deve conter a seguintes colunas: NOME_SITE, ID_PRODUTO, ID_MARCA, PG e OUTPUT_FILE. Sendo essas seguidas imediatamente pelos itens das buscas.

Vale ressaltar que mesmo que os parametros realmente usados sejam NOME_SITE e OUTPUT_FILE, todas as colunas devem estar presentes, sendo que as outras podem ser definidas como vazias.

#### Processamento de dados
A extração dos dados, consiste numa série de requisições HTTP, que podem ser divididas em duas partes.

Primeiramente, ao iniciar a extração de dados, o script fará uma busca pelo produto dentro do site da OLX. Em seguida, calculará quantas páginas existem, para então começar a gravar os dados dentro do array de saída. Essa primeira parte retornará um array com a maioria dos dados dos anuncios.

Na segunda parte do script, esse array será usado como parametro de busca (Nota: Na prática, apenas o link será usado, computando menos dados e consequentemente, diminuindo o tempo do processo). Então, será feita uma solicitação HTTP para cada anúncio, e dentro da página retornada, o crawler extrai o nome do anunciante. Será retornado então, o array de dados final.

O processo de extração de dados por busca é bem longo, tendo demorado cerca de 1 minuto para retornar resultados da busca por 16 itens.

#### O que fazer caso o robô caia no meio de uma solicitação
Caso o robô caia no meio de uma solicitação, há duas linhas de ação a se seguir

1. Apagar os arquivos gerados (caso haja algum) e reiniciar as buscas.
2. Alterar as permissões da pasta dentro do servidor, para permitir que o próprio script sobrescreva os arquivos gerados anteriormente.

Como se trata de um script recursivo, independente de definir uma página inicial mais á frente da primeira, o tempo de execução do processo será o mesmo, por isso é recomendado que o script tenha permissões para sobrescrever os arquivos.

## Como implementar
A implementação é bem simples, o uso do robo deve ser feito via terminal em ambos os sistemas:

* **Todos os sistemas**:
> cd caminho/para/Crawler
> php index.php

(Nota: Em máquinas com Microsoft Windows, o prompt deve ser aberto pelo XAMPP. Para isso, abra como administrador o XAMPP Control Panel, em seguida clique em shell.)

## Dependências
* [SimpleXLSX](https://github.com/shuchkin/simplexlsx) - Para ler dados de arquivos em formato XLSX.
* [XLSXWriter](https://github.com/mk-j/PHP_XLSXWriter) - Para escrever arquivos em formato XLSX.

## Páginas fonte
* https://www.olx.com.br/brasil

## Contato
* Email para contato: mailto:aou-emmy@outlook.com
* Telefone para contato: +55 (11) 95837-8163