<?xml version="1.0" encoding="UTF-8"?>
<!-- Шаблон формы -->
<xsl:stylesheet
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:obj="urn:ru:ilb:meta:TestApp:Document"
	xmlns:req="urn:ru:ilb:meta:TestApp:DocumentListRequest"
	xmlns:res="urn:ru:ilb:meta:TestApp:DocumentListResponse"
	exclude-result-prefixes="xsl req res obj"
	version="1.0">

	<xsl:output
		media-type="application/xhtml+xml"
		method="xml"
		encoding="UTF-8"
		indent="yes"
		omit-xml-declaration="no"
		doctype-public="-//W3C//DTD XHTML 1.1//EN"
		doctype-system="http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd" />

	<xsl:strip-space elements="*" />

	<xsl:template match="/">
		<html xml:lang="ru">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				<title>Список документов</title>
			</head>
			<body onload="">
				<xsl:apply-templates />
			</body>
		</html>
	</xsl:template>

	<xsl:template match="res:DocumentListResponse">
		<xsl:variable name="req" select="req:DocumentListRequest"/>
		<form action="documentList.php">
			<fieldset>
				<input type="hidden" name="run" value="1"/>
				<legend>Список документов</legend>
				<div>
					<label title="ГГГГ-ММ-ДД">
						Дата с
						<input size="10" type="text" name="dateStart-0" value="{$req/req:dateStart}"/>
					</label>
					<label title="ГГГГ-ММ-ДД">
						по
						<input size="10" type="text" name="dateEnd-0" value="{$req/req:dateEnd}"/>
					</label>
				</div>
				<div style="margin-top: 10px;">
					<label title="Наименование">
						Наименование
						<input type="text" name="name-0" value="{$req/req:name}"/>
					</label>
				</div>
				<div style="margin-top: 10px;">
					<span>Формат вывода
						<label style="margin-right: 5px;">
							<input type="radio" name="outputFormat-0" value="html">
								<xsl:attribute name="checked"/>
							</input>
							html
						</label>
						<label style="margin-right: 5px;">
							<input type="radio" name="outputFormat-0" value="pdf"/>
							pdf
						</label>
					</span>
				</div>
				<div style="margin-top: 10px;">
					<button  type="submit">Отправить</button>
				</div>
			</fieldset>
		</form>
		<xsl:if test="$req/following-sibling::*">
			<table class="report" summary="Тестовый отчет">
				<caption>
					Документы с
					<xsl:value-of select="req:DocumentListRequest/req:dateStart"/> по
					<xsl:value-of select="req:DocumentListRequest/req:dateEnd"/>
					<xsl:if test="req:DocumentListRequest/req:name"><!--
						-->, содержащие в названии "<xsl:value-of select="req:DocumentListRequest/req:name"/>"
					</xsl:if>
				</caption>
				<tr>
					<th>Дата</th>
					<th>Наименование</th>
					<th>Ключевые слова</th>
					<th>Удален</th>
				</tr>
				<xsl:for-each select="obj:Document">
					<tr>
						<td>
							<xsl:value-of select="obj:docDate"/>
						</td>
						<td>
							<a href="document.php?objectId-0={obj:objectId}">
								<xsl:value-of select="obj:displayName"/>
							</a>
						</td>
						<td>
							<xsl:value-of select="obj:keywords"/>
						</td>
						<td>
							<xsl:value-of select="obj:deleted"/>
						</td>
					</tr>
				</xsl:for-each>
			</table>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>
