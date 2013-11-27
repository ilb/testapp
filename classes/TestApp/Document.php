<?php
/**
 * @version $Id: Document.php 115 2013-11-27 17:43:09Z slavb $
 */
/**
 * Документ электронного каталога
 * @xmlns urn:ru:ilb:meta:TestApp:Document
 * @xmlname Document
 * @codegen true
 */
class TestApp_Document extends Adaptor_XMLBase implements Adaptor_Array {
	/**
	 * Идентификатор
	 * Пример указания имени sql-отображения (на самом деле если имя поля в sql совпадает с именем свойства, sqlname не нужно)
	 * @sqlname objectId
	 * Пример указания направления sql-отображения. Для того чтобы свойство не
	 * оторажалось в sql можно указать sqlio=none
	 * @sqlio inout
	 * @var string
	 */
	private $objectId;
	/**
	 * Дата создания
	 *
	 * @var Basictypes_DateTime
	 */
	private $createDate;
	/**
	 * Создал
	 *
	 * @var string
	 */
	private $createUid;
	/**
	 * Дата модификации
	 *
	 * @var Basictypes_DateTime
	 */
	private $modifyDate;
	/**
	 * Изменил
	 *
	 * @var string
	 */
	private $modifyUid;
	/**
	 * Дата удаления
	 *
	 * @var Basictypes_DateTime
	 */
	private $deleteDate;
	/**
	 * Удалил
	 *
	 * @var string
	 */
	private $deleteUid;
	/**
	 * Удален
	 *
	 * @var string
	 */
	private $deleted;
	/**
	 * Идентификатор раздела
	 *
	 * @var string
	 */
	private $containerId;
	/**
	 * Тип объекта контейнера
	 *
	 * @var string
	 */
	private $containerType;
	/**
	 * Раздел
	 *
	 * @var string
	 */
	private $chapterPath;
	/**
	 * Наименование
	 *
	 * @var string
	 */
	private $displayName;
	/**
	 * Описание
	 *
	 * @var string
	 */
	private $description;
	/**
	 * Ключевые слова
	 *
	 * @var string
	 */
	private $keywords;
	/**
	 * Дата документа
	 *
	 * @var Basictypes_Date
	 */
	private $docDate;
	/**
	 * Ключ
	 *
	 * @var string
	 */
	private $keyId;
	/**
	 * Вывод в XMLWriter
	 * @codegen true
	 * @param XMLWriter $xw
	 * @param string $xmlname Имя корневого узла
	 * @param int $mode
	 */
	public function toXmlWriter(XMLWriter &$xw,$xmlname=NULL,$xmlns=NULL,$mode=Adaptor_XML::ELEMENT){
		$xmlname=$xmlname?$xmlname:"Document";
		$xmlns=$xmlns?$xmlns:"urn:ru:ilb:meta:TestApp:Document";
		if ($mode&Adaptor_XML::STARTELEMENT) $xw->startElementNS(NULL,$xmlname,$xmlns);
			if($this->objectId!==NULL) {$xw->writeElement("objectId",$this->objectId);}
			if($this->createDate!==NULL) {$xw->writeElement("createDate",$this->createDate->LogicalToXSD());}
			if($this->createUid!==NULL) {$xw->writeElement("createUid",$this->createUid);}
			if($this->modifyDate!==NULL) {$xw->writeElement("modifyDate",$this->modifyDate->LogicalToXSD());}
			if($this->modifyUid!==NULL) {$xw->writeElement("modifyUid",$this->modifyUid);}
			if($this->deleteDate!==NULL) {$xw->writeElement("deleteDate",$this->deleteDate->LogicalToXSD());}
			if($this->deleteUid!==NULL) {$xw->writeElement("deleteUid",$this->deleteUid);}
			if($this->deleted!==NULL) {$xw->writeElement("deleted",$this->deleted);}
			if($this->containerId!==NULL) {$xw->writeElement("containerId",$this->containerId);}
			if($this->containerType!==NULL) {$xw->writeElement("containerType",$this->containerType);}
			if($this->chapterPath!==NULL) {$xw->writeElement("chapterPath",$this->chapterPath);}
			if($this->displayName!==NULL) {$xw->writeElement("displayName",$this->displayName);}
			if($this->description!==NULL) {$xw->writeElement("description",$this->description);}
			if($this->keywords!==NULL) {$xw->writeElement("keywords",$this->keywords);}
			if($this->docDate!==NULL) {$xw->writeElement("docDate",$this->docDate->LogicalToXSD());}
			if($this->keyId!==NULL) {$xw->writeElement("keyId",$this->keyId);}
		if ($mode&Adaptor_XML::ENDELEMENT) $xw->endElement();
	}
	/**
	 * Чтение из  XMLReader
	 * @codegen true
	 * @param XMLReader $xr
	 */
	public function fromXmlReader(XMLReader &$xr){
		while($xr->nodeType!=XMLReader::ELEMENT) $xr->read();
		$root=$xr->localName;
		if($xr->isEmptyElement) return $this;
		while($xr->read()){
			if($xr->nodeType==XMLReader::ELEMENT) {
				$xsinil=$xr->getAttributeNs("nil","http://www.w3.org/2001/XMLSchema-instance")=="true";
				switch($xr->localName){
					case "objectId": $this->objectId=$xsinil?NULL:$xr->readString(); break;
					case "createDate": $this->createDate=$xsinil?NULL:new Basictypes_DateTime($xr->readString(),Adaptor_DataType::XSD); break;
					case "createUid": $this->createUid=$xsinil?NULL:$xr->readString(); break;
					case "modifyDate": $this->modifyDate=$xsinil?NULL:new Basictypes_DateTime($xr->readString(),Adaptor_DataType::XSD); break;
					case "modifyUid": $this->modifyUid=$xsinil?NULL:$xr->readString(); break;
					case "deleteDate": $this->deleteDate=$xsinil?NULL:new Basictypes_DateTime($xr->readString(),Adaptor_DataType::XSD); break;
					case "deleteUid": $this->deleteUid=$xsinil?NULL:$xr->readString(); break;
					case "deleted": $this->deleted=$xsinil?NULL:$xr->readString(); break;
					case "containerId": $this->containerId=$xsinil?NULL:$xr->readString(); break;
					case "containerType": $this->containerType=$xsinil?NULL:$xr->readString(); break;
					case "chapterPath": $this->chapterPath=$xsinil?NULL:$xr->readString(); break;
					case "displayName": $this->displayName=$xsinil?NULL:$xr->readString(); break;
					case "description": $this->description=$xsinil?NULL:$xr->readString(); break;
					case "keywords": $this->keywords=$xsinil?NULL:$xr->readString(); break;
					case "docDate": $this->docDate=$xsinil?NULL:new Basictypes_Date($xr->readString(),Adaptor_DataType::XSD); break;
					case "keyId": $this->keyId=$xsinil?NULL:$xr->readString(); break;
				}
			}elseif($xr->nodeType==XMLReader::END_ELEMENT&&$root==$xr->localName){
				return;
			}
		}
		return $this;
	}
	/**
	 * Для настройки sql отобажения у свойства можно указывать параметры @sqlname, @sqlio
	 * @codegen true
	 */
	public function fromArray($row,$mode=Adaptor_DataType::XSD){
		if(isset($row["objectId"])) $this->objectId=$row["objectId"];
		if(isset($row["createDate"])) $this->createDate=new Basictypes_DateTime($row["createDate"],$mode);
		if(isset($row["createUid"])) $this->createUid=$row["createUid"];
		if(isset($row["modifyDate"])) $this->modifyDate=new Basictypes_DateTime($row["modifyDate"],$mode);
		if(isset($row["modifyUid"])) $this->modifyUid=$row["modifyUid"];
		if(isset($row["deleteDate"])) $this->deleteDate=new Basictypes_DateTime($row["deleteDate"],$mode);
		if(isset($row["deleteUid"])) $this->deleteUid=$row["deleteUid"];
		if(isset($row["deleted"])) $this->deleted=$row["deleted"];
		if(isset($row["containerId"])) $this->containerId=$row["containerId"];
		if(isset($row["containerType"])) $this->containerType=$row["containerType"];
		if(isset($row["chapterPath"])) $this->chapterPath=$row["chapterPath"];
		if(isset($row["displayName"])) $this->displayName=$row["displayName"];
		if(isset($row["description"])) $this->description=$row["description"];
		if(isset($row["keywords"])) $this->keywords=$row["keywords"];
		if(isset($row["docDate"])) $this->docDate=new Basictypes_Date($row["docDate"],$mode);
		if(isset($row["keyId"])) $this->keyId=$row["keyId"];
		return $this;
	}
}
