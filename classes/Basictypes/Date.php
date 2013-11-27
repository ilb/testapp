<?php
/**
 * Дата.
 * @author Борисов В.В.
 * @version $Id: Date.php 11 2013-01-11 10:33:26Z slavb $
 */
 /**
  * Дата.
  * Обертка над встроенным DateTime для корректной сериализации.
  * (корректная сериализация DateTime реализована в php >= 5.3)
  */
class Basictypes_Date extends DateTime implements Adaptor_DataType{
	private $unixtimestamp;
	function __sleep(){
		$this->unixtimestamp=$this->format("U");
		return array("unixtimestamp");
	}
	function __wakeup(){
		parent::__construct("@".$this->unixtimestamp);
		parent::setTimeZone(new DateTimeZone(date_default_timezone_get()));
	}
	public function __construct($value=""){
		parent::__construct($value);
	}
	public function setValue($value){
		parent::__construct($value);
	}
	public function getValue($mode=Adaptor_DataType::INT){
		switch($mode){
			case Adaptor_DataType::INT: return $this;
			case Adaptor_DataType::XSD: return $this->LogicalToXSD();
			case Adaptor_DataType::SQL: return $this->LogicalToSQL();
			default: trigger_error(__CLASS__."->".__METHOD__.": validation error: mode=".$mode);
		}
	}

	public function __toString(){
		return $this->format("Y-m-d");
	}
	public function LogicalToXSD(){
		return $this->format("Y-m-d");
	}
	public function LogicalToSQL(){
		return $this->format("Y-m-d");
	}

}
?>