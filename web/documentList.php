<?php

require_once("../conf/bootstrap.php");

//читаем данные и HTTP-запроса, строим из них XML по схеме
$hreq = new HTTP_Request2Xml("schemas/TestApp/DocumentListRequest.xsd");
$req=new TestApp_DocumentListRequest();
if (!$hreq->isEmpty()) {
	$hreq->validate();
	$req->fromXmlStr($hreq->getAsXML());
}

// формируем xml-ответ
$xw = new XMLWriter();
$xw->openMemory();
$xw->setIndent(TRUE);
$xw->startDocument("1.0", "UTF-8");

if ($req->outputFormat == 'pdf') {
	$xw->startElementNS(NULL, "DocumentListResponse", "urn:ru:ilb:meta:TestApp:DocumentListResponse");
	$req->toXmlWriter($xw);
	if (!$hreq->isEmpty()) documentWriter($xw, $req);
	$xw->endElement();
	$xw->endDocument();

	$xml = $xw->flush();

	$xmldom = new DOMDocument();
	$xmldom->loadXML($xml);
	$xsldom = new DomDocument();
	$xsldom->load("stylesheets/TestApp/DocumentListFO.xsl");
	$proc = new XSLTProcessor();
	$proc->importStyleSheet($xsldom);
	$xml = $proc->transformToXML($xmldom);

	$url = "https://demo01.ilb.ru/fopservlet/fopservlet";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	$res = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ($code != 200) {
		throw new Exception($res . PHP_EOL . $url . " " . curl_error($ch), 450);
	}
	curl_close($ch);

	$headers = array(
		"Content-Type: application/pdf",
		"Content-Disposition: inline; filename*=UTF-8''" . "DocumentList.pdf" //TODO
	); 

	foreach ($headers as $h) {
		header($h);
	}

	echo $res;
}
else {
	$xw->writePi("xml-stylesheet", "type=\"text/xsl\" href=\"stylesheets/TestApp/DocumentList.xsl\"");
	$xw->startElementNS(NULL, "DocumentListResponse", "urn:ru:ilb:meta:TestApp:DocumentListResponse");
	$req->toXmlWriter($xw);
	// Если есть входные данные, проведем вычисления и выдадим ответ
	if (!$hreq->isEmpty()) documentWriter($xw, $req);
	$xw->endElement();
	$xw->endDocument();
	
	$xml = $xw->flush();

	//Вывод ответа клиенту
	header("Content-Type: text/xml");
	echo $xml;
}

function documentWriter(XMLWriter &$xw, $req) {
	$pdo=new PDO("mysql:host=localhost;dbname=testapp;charset=utf8","testapp","1qazxsw2",array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	//prior to PHP 5.3.6, the charset option was ignored. If you're running an older version of PHP, you must do it like this:
	//$pdo->exec("set names utf8");
	$query = "SELECT * FROM document WHERE (docDate BETWEEN :dateStart AND :dateEnd)";
	$query = "SELECT * FROM document WHERE docDate BETWEEN :dateStart AND :dateEnd";
	if ($req->name !== NULL) {
		$query .= " AND (displayName LIKE '%{$req->name}%');";
	}
	$sth=$pdo->prepare($query);
	$statement = array(":dateStart"=>$req->dateStart,":dateEnd"=>$req->dateEnd);
	$sth->execute($statement);
	while($row=$sth->fetch(PDO::FETCH_ASSOC)) {
		$doc = new TestApp_Document();
		$doc->fromArray($row);
		$doc->toXmlWriter($xw);
	}
}