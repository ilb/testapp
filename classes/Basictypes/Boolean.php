<?php
/**
 * @author Борисов В.В.
 * @version $Id: Boolean.php 1 2012-12-25 08:23:30Z slavb $
 */
 /**
  * @ignore
  */
class Basictypes_Boolean implements Adaptor_DataType{
	protected $value;
	public function __construct($value){
		$this->setValue($value);
	}
	public function getValue($mode=Adaptor_DataType::INT){
		switch($mode){
			case Adaptor_DataType::INT: return $this->value;
			case Adaptor_DataType::XSD: return $this->LogicalToXSD();
			case Adaptor_DataType::SQL: return $this->LogicalToSQL();
			default: trigger_error(__CLASS__."->".__METHOD__.": validation error: mode=".$mode);
		}
	}
	public function setValue($value){
		$svalue=(string)$value;
		if($svalue==="1"||$svalue==="true") $this->value=TRUE;
		else if($svalue==="0"||$svalue==="false"||$value===FALSE) $this->value=FALSE;
		else trigger_error("Basictypes_Boolean validation error: $value");
		return $this->value;
	}
	public function __toString(){
		return $this->value?"Да":"Нет";
	}
	public function LogicalToXSD(){
		return $this->value?"true":"false";
	}
	public function LogicalToSQL(){
		return $this->value?"true":"false";
	}
}
?>