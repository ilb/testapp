<?php
/**
 * Дата и время.
 * @author Борисов В.В.
 * @version $Id: DateTime.php 486 2013-11-19 13:19:45Z slavb $
 */
 /**
  * Дата и время.
  * Обертка над встроенным DateTime для корректной сериализации.
  * (корректная сериализация DateTime реализована в php >= 5.3)
  */
class Basictypes_DateTime extends DateTime implements Adaptor_DataType{
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
		return $this->format("c");
	}
	/**
	 * преобразовать в дату
	 * @return Basictypes_Date
	 */
	public function toDate(){
		return new Basictypes_Date($this->format("Y-m-d"));
	}

	public function LogicalToXSD(){
		return $this->format("c");
	}
	public function LogicalToSQL(){
		return $this->format("c");
	}

}
?>