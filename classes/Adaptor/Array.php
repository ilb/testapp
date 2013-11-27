<?php
/**
 * @author Борисов В.В.
 * @version $Id: Array.php 224 2013-07-11 08:57:02Z slavb $
 */
 /**
  * @ignore
  */
interface Adaptor_Array {
	public function fromArray($row,$mode=Adaptor_DataType::SQL);
	//public function toArray($mode = Adaptor_DataType::SQL);
}
?>
