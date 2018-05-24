<?php
/** PROGRAMA DE SELEÇÃO DOS 1000 EMPREGADOS PARA O eSOCIAL - BUSCA DOS XMLs GERADOS NA TIDEXA RHSOFT
 ** DATA: 02/11/2017
 ** AUTOR: EDSON MARQUES (E001057)
 **
 **
 **/
echo "<pre>";
// Define as variáveis iniciais do PHP.
ini_set('max_execution_time','3600');

ini_set('display_erros',true); // TODO: Alterar para 'FALSE'
#ini_set('display_startup_erros',1);
#error_reporting(E_ALL);


// Idioma de Formatação no Banco de Dados (Oracle - FolhaSoft)
putenv("NLS_LANG=BRAZILIAN PORTUGUESE_BRAZIL.WE8ISO8859P1");

// Define o tempo máximo de execução em 0 para as conexões lentas.
set_time_limit(0);

// Inicia o contador de tempo de processamento (servidor) deste arquivo.
$GLOBALS['startTime'] = microtime(true);

// Função para cálculo do tempo de processamento deste arquivo.
function displayStatistics()
{
	$endTime = microtime(true);
	$deltaTime = $endTime - $GLOBALS['startTime'];
	$response_time_string = "Tempo de resposta do servidor: " . number_format(round($deltaTime, 2), 2) . " segundos";
	echo $response_time_string;
}


// Define variáveis iniciais.
$regLidosS2200   = 0;
$regLidosS2300   = 0;
$regErrosS2200   = 0;
$regErrosS2300   = 0;
$regSucessoS2200 = 0;
$regSucessoS2300 = 0;

// Manipulação dos arquivos de Entrada.
$LISTA2200  = fopen("LISTA_S-2200.TXT", 'r') or die("Erro ao abrir o arquivo: LISTA_S-2200.TXT");
#$LISTA2300  = fopen("LISTA_S-2300.TXT", 'r') or die("Erro ao abrir o arquivo: LISTA_S-2300.TXT");
$LOGERROS   = fopen("LOGERROS-".date('Ymd-His').".txt",'w') or die("Erro ao criar o arquivo de LOG de ERRO");
$LOGSUCESSO = fopen("LOGSUCESSO-".date('Ymd-His').".txt",'w') or die("Erro ao criar o arquivo de LOG de SUCESSO");

fwrite($LOGERROS,"");
fwrite($LOGSUCESSO,"");



echo "<br>Carregando Conexão com Banco de Dados FolhaSoft...";
$user = "**usuario_bd**";
$pass = "**senha_bd**";	
$db = "(DESCRIPTION =(ADDRESS_LIST =(ADDRESS = (PROTOCOL = TCP)(LOAD_BALANCE = OFF)(HOST = sd038cld)(PORT = 1521)) ) (CONNECT_DATA = (SID = TD05) (SERVER = DEDICATED)) )";	
$connection = oci_connect($user,$pass,$db) or die (ocierror());



echo "<br>Carregando Lista S-2200...";
while (!feof($LISTA2200))
{
	$linhaS2200 = fgets($LISTA2200,4096);
	$S2200_MATRICULA[] = (int)$linhaS2200;
	$regLidosS2200++;
}
echo " [$regLidosS2200] ";

/*echo "<br>Carregando Lista S-2300...";
while (!feof($LISTA2300))
{
	$linhaS2300 = fgets($LISTA2300,4096);
	#$S2300_MATRICULA[] = SUBSTR($linhaS2300,0,7);
	$S2300_MATRICULA[] = (int)$linhaS2300;
	$regLidosS2200++;
}
echo " [$regLidosS2300] ";
*/



echo "<br>Processando Arquivos...";

	#print_r($S2200_MATRICULA); die();

/** *** SUBPROCESSO BUSCA ARQUIVOS NO DIRETÓRIO DO eSOCIAL - S-2200 *** **/
foreach($S2200_MATRICULA as $res)
{
	#echo $res; die();
	//SELECT BANCO FOLHASOFT TD05 - BUSCA DA MATRICULA NA TABELA ES_2200 E PEGA O ID.
	$sql = "SELECT ID, MATRICULA FROM ES_2200 WHERE MATRICULA = ".$res;
	$result = oci_parse($connection,$sql);
	oci_execute($result);

	while ($res = oci_fetch_array($result))
	{
		$eSocialID        = $res['ID'];
		$eSocialMATRICULA = $res['MATRICULA'];

		//MONTA O ID: 'S-2200-'.$ID.'.XML';
		//exemplo: S-2200-ID1620703620000002017101119015900002.xml
		$arq2200 = 'S-2200-'.$eSocialID.'.xml';

		//BUSCA O ARQUIVO CORRESPONDENTE NA PASTA /todos2200 E INFORMA O DESTINO
		$file = '/edson/todos2200/'.$arq2200;
		$newfile = '/edson/lista2200/'.$arq2200;

		//COPIA O ARQUIVO PARA A PASTA /lista2200
		//GRAVA OS LOGS EM CASO DE ERRO OU SUCESSO
		if(!copy($_SERVER['DOCUMENT_ROOT'].$file,$_SERVER['DOCUMENT_ROOT'].$newfile))
		{
			fwrite($LOGERROS,"Falha ao copiar arquivo: $file - Matricula: $eSocialMATRICULA \r\n");
			echo "<br>[<font color=RED>FALHA</font>]Falha ao copiar arquivo: $file - Matricula: $eSocialMATRICULA";
			$regErrosS2200++;
		}
		else
		{
			fwrite($LOGSUCESSO,"Copiado arquivo: $file - Matricula: $eSocialMATRICULA \r\n");
			echo "<br>[<font color=GREEN> OK  </font>]Copiado arquivo: $file - Matricula: $eSocialMATRICULA";
			$regSucessoS2200++;
		}





			/*
			//Exemplo 1:
				#copy('foo/test.php', 'bar/test.php');
			
			//Exemplo 2:
				#$src = "/home/www/example.com/source/folders/123456";  // source folder or file
				#$dest = "/home/www/example.com/test/123456";   // destination folder or file        
				#shell_exec("cp -r $src $dest");
			
			//Exemplo 3:
				#$file = '/edson/todos2200/'.$arq2200;
				#$newfile = '/edson/lista2200/'.$arq2200;
				#if(!copy($file,$newfile))
				#{
				#	fwrite($LOGERROS,"Falha ao copiar arquivo: $file - Matricula: $res");
				#	echo "failed to copy $file";
				#}
				#else
				#{
				#	echo "copied $file into $newfile\n";
				#}
			
			
			
			*/
	}
}



/** *** SUBPROCESSO FINALIZAR *** **/
// Fecha os arquivos.
@fclose($LISTA2200);
@fclose($LISTA2300);
@fclose($LOGERROS);
@fclose($LOGSUCESSO);


// Exibe informações na tela (resultados).
echo "<br>";
echo "<hr>";
echo "<br>";
echo "<br>Quantidade de Registros Lidos S-2200: <b>".$regLidosS2200."</b>";
echo "<br>Quantidade de Registros Lidos S-2300: <b>".$regLidosS2300."</b>";
echo "<br>Quantidade de Registros com Erros S-2200: <b>".$regErrosS2200."</b>";
echo "<br>Quantidade de Registros com Erros S-2300: <b>".$regErrosS2300."</b>";
echo "<br>Quantidade de Registros com Sucesso S-2200: <b>".$regSucessoS2200."</b>";
echo "<br>Quantidade de Registros com Sucesso S-2300: <b>".$regSucessoS2300."</b>";
echo "<hr>";
echo "<br>";


// Destrói as variáveis e libera memória do servidor
unset($regLidosS2200, $regErrosS2200, $regSucessoS2200, $LISTA2200, $linhaS2200, $S2200_MATRICULA);
unset($regLidosS2300, $regErrosS2300, $regSucessoS2300, $LISTA2300, $linhaS2300, $S2300_MATRICULA);
unset($res, $eSocialID, $result, $LOGERROS, $LOGSUCESSO);
unset($user, $pass, $db, $connection);


// Exibe o tempo total de processamento desta rotina.
echo "<hr>";
echo "<br><br><center>";
displayStatistics();
/**FIM DO SUBPROCESSO FINALIZAR**/
?>