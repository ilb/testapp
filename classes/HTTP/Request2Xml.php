<?php
/**
 * $Id: Request2Xml.php 340 2013-10-15 08:17:00Z slavb $
 *
 * Получение входных данных из HTTP-запроса полученного с HTML-формы или POST-ом в XML
 *
 * читает входящие данные и если требуется сериализует из urlencoded/urlpostdata в XML.
 * данные переданные post с типом XML передаются как есть.
 * для get-post порядок элементов соответствует переданному в запросе.
 * файлы переданные multipart/form-data также включаются в XML-документ и проверяются схемой. пример:
 * <uploadedFile>
 *  <name>Документ.odt</name>
 *  <tmp_name>/tmp/phpXXXX</tmp_name>
 *  <size>999</size>
 * </uploadedFile>
 *
 * чтобы иметь возможность использовать валидные имена в HTML-форме для "id" и "name" применяется
 * ограниченный набор символов для псевдо-XPath разметки:
 * ... to be backward-compatible, only strings matching the pattern [A-Za-z][A-Za-z0-9:_.-]* should be used.
 * See Section 6.2 of [HTML4] for more information.
 * "." тоже выпадает из доступных символов - её PHP заменяет на "_".
 *
 * "азбука морзе":
 * ":" - разделитель префикса и локального имени элемента, дефолтный нэймспейс можно упускать,
 * "-" - разделяет узелки пути к расположению элемента и числовые индексы в родительском элементе:
 * ns0:el1-0-ns0:el2-7-ns1:el3-1
 * допускается использование принятых в PHP "[]" в имени параметра (только к последнему элементу):
 * root-el-[], root-el-[],... = root-el-0, root-el-1,...
 * если в конце имени указан ":" (ну не хватает букав чтоб разметить!)
 *  пустое значение этого элемента сериализуется как nillable.
 * если в конце имени указаны "::" (два двоеточия!!!)
 *  пустое значение этого элемента никак не сериализуется.
 * аттрибут определяется по отсутствую индекса после имени:
 * root-el-0-attr
 * элементы без "-" в имени пропускаются (считаются служебными, управляющими и пр. игнорируемыми).
 * значения переданные из формы нормализуются: обрезаются концевые пробелы, исправляются переводы строки.
 *
 * @author dab@bystrobank.ru
 *
 */

class HTTP_Request2Xml {
	/**
	 * разделитель элементов в идентификаторах элемнетов формы ( вместо XPath-ового "/" )
	 */
	const XPATH_DELIMITER="-";
	/**
	 * @var DOMDocument модель данных запроса в DOM
	 */
	private $dom=NULL;
	/**
	 * сериализованный DOMDocument
	 * @var type 
	 */
	private $domxml;
	/**
	 * @var DOMXPath документа содержащегося в $dom
	 */
	private $xpath=NULL;
	/**
	 * @var string файл XML-схемы запроса
	 */
	public $schema=NULL;
	/**
	 * @var array массив мапинга "префикс"=>"нэймспейс"
	 */
	public $xmlnss=array();
	/**
	 * @var string имя корневого элемента документа
	 */
	public $root=NULL;
	/**
	 * @var boolean признак пустого запроса
	 */
	public $empty=TRUE;
	/**
	 * метод вызывается перед сериализаций
	 * свойство $dom не сохраняется, т.к. после десериализации DOMDocument не работает (php 5.3)
	 * вместо $dom сохраняется $domxml
	 * @return type
	 */
	public function __sleep() {
		$this->domxml=$this->dom->saveXML();
		return array (
			"domxml",
			"schema",
			"xmlnss",
			"root",
			"empty",
		);
	}
	/**
	 *метод вызывается после десериализации
	 */
	public function __wakeup() {
		$this->dom=new DOMDocument();
		$this->dom->loadXML($this->domxml);
		$this->domxml=NULL;
		$this->initXpath();
	}

	/**
	 * Получение входных данных из HTTP-запроса полученного с HTML-формы.
	 * читает входящие данные и если требуется сериализует из urlencoded/urlpostdata в XML
	 * @param string $schema имя файла схемы входного запроса
	 * @param array $request массив входных данных, если не указан - берется из $_GET + $_POST
	 * @param string $root имя корневого узла, если не передан, берется имя первого xsd:element в схеме
	 */
	public function __construct( $schema, $request=NULL, $root=NULL ) {
		$this->dom=new DOMDocument("1.0","UTF-8");
		$this->schema=$schema;
		$this->root=$root;

		if( !$request &&
			$_SERVER["REQUEST_METHOD"]=="POST" &&
			array_key_exists("CONTENT_TYPE",$_SERVER) &&
			strpos($_SERVER["CONTENT_TYPE"],"/xml")!==FALSE ) {
			//используем старую переменную HTTP_RAW_POST_DATA для передачи постом
			//1. можно переопределить где-нить в отличие от потока 2. оставляем возможность подглядеть содержимое поста в дампе при ошибке 3. типа для совместимости
			if(!isset($GLOBALS["HTTP_RAW_POST_DATA"])){
				$GLOBALS["HTTP_RAW_POST_DATA"]=file_get_contents("php://input");
			}
			//пытаемся грузить хмл как есть в дом
			$this->dom->loadXML($GLOBALS["HTTP_RAW_POST_DATA"]);
			//после успешной загрузки в дом - сырое содержимое не нужно уже - можно из дома достать
			$GLOBALS["HTTP_RAW_POST_DATA"]=NULL;
			$this->empty=FALSE;
		} else {
			if( !$request ) {
				//не берем из $_REQUEST потому что там еще и куки
				$request=array_merge($_GET,$_POST);
			}
			//есть загруженные файлы
			if( isset($_FILES) ) {
				//сообщения об ошибках из мануала
				$uploaderrors=array(
					UPLOAD_ERR_INI_SIZE=>"1 The uploaded file exceeds the upload_max_filesize directive in php.ini.",
					UPLOAD_ERR_FORM_SIZE=>"2 The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
					UPLOAD_ERR_PARTIAL=>"3 The uploaded file was only partially uploaded.",
					UPLOAD_ERR_NO_FILE=>"4 No file was uploaded.",
					UPLOAD_ERR_NO_TMP_DIR=>"6 Missing a temporary folder.",
					UPLOAD_ERR_CANT_WRITE=>"7 Failed to write file to disk.",
					UPLOAD_ERR_EXTENSION=>"8 File upload stopped by extension."
				);
				foreach( $_FILES as $key=>$val ) {
					//попутно проверяем ошибки загрузки - поломанные файлы нам не нужны
					//пустые файлы пропускаем - они могут быть предусмотрены схемой, пусть схемой и проверяются
					if( is_array($val["error"]) ) {
						$request[$key]=array();
						$count=count($val["error"]);
						for( $i=0; $i<$count; $i++ ) {
							if( !( ($val["error"][$i]==UPLOAD_ERR_NO_FILE && strlen($val["name"][$i])==0) ||
								$val["error"][$i]==UPLOAD_ERR_OK ) ) {
								throw new Exception("upload failed: ".$uploaderrors[$val["error"][$i]]);
							}
							if( strlen($val["name"][$i])!=0 ) {
								$request[$key][$i]=$val["name"][$i];
							}
						}
					} else {
						if( !( ($val["error"]==UPLOAD_ERR_NO_FILE && strlen($val["name"])==0) ||
							$val["error"]==UPLOAD_ERR_OK ) ) {
							throw new Exception("upload failed: ".$uploaderrors[$val["error"]]);
						}
						if( strlen($val["name"])!=0 ) {
							$request[$key]=$val["name"];
						}
					}
				}
			}

			if(count($request)) {
				//прочитаем из файла схемы информацию о нэйпмпейсах,префиксах и корне
				$xr=new XMLReader();
				$xr->open($this->schema);
				$this->readNSinfo($xr);
				if ($this->root===NULL) {
					$this->readRootName($xr);
				}
				$xr->close();

				//создаем корневой элемент с нэймспесом
				if( !$this->root || !array_key_exists("_target_ns", $this->xmlnss)  ) {
					throw new Exception("targetNamespace or root element not found: check '".$this->schema."'");
				}
				$root_el=$this->dom->createElementNS($this->xmlnss["_target_ns"],$this->root);
				$this->dom->appendChild($root_el);

				//регистрируем нэймспейс для nillable (пока неизвестно пригодится ли)
				$root_el->setAttributeNS("http://www.w3.org/2000/xmlns/","xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance");

				//тут будем запоминать созданные узелки чтоб не бегать по дому в поисках куда пристроить элемент
				$children=array();
				$localname=$prefix=$name=$ns=NULL;
				foreach( $request as $key=>$val ) {
					//пропускаем элементы без разделителя - про них мы ниче не знаем
					if( !strpos($key,self::XPATH_DELIMITER) ) {
						continue;
					}
					//из имени параметра получаем путь к элементу в доме
					$parts=preg_split("/\\".self::XPATH_DELIMITER."/",$key,-1,PREG_SPLIT_NO_EMPTY);
					//выбираем xpath индексы в массив "номер элемента пути"=>"индекс в хпат"
					$indexes=array();
					$nolastindex=FALSE; //признак либо аттрибута либо массива переданного через []
					$len=count($parts);
					for( $i=0; $i<$len; $i++ ) {
						if( $i%2 ) { //нечетный
							if( is_numeric($parts[$i]) ) { //цифра - значит индекс
								$indexes[$i-1]=$parts[$i];
								unset($parts[$i]);
							} else {
								throw new Exception("even elements must be numbers. ".$i."=".$parts[$i],450);
							}
						} elseif( is_numeric($parts[$i]) ) {
							throw new Exception("add elements must be names. ".$i."=".$parts[$i],450);
						} else {
							$indexes[$i]=0;
						}
					}
					if( $len%2 ) { //если нечетное кол-во элемнтов пути - значит нет последнего индекса
						$nolastindex=TRUE;
					}
					//пересчитаем кол-во узелков исключив индексы
					$len=count($indexes);
					//текущий контекст собираем сюда
					$path="";
					//добавлять начинаем от корня
					$parent=$root_el;
					$j=0;
					//проходим по узелкам пути
					foreach( $indexes as $i=>$index ) {
						//выгрызаем префикс узелка - префиксы в форме должны совпадать со схемой
						$atoms=preg_split("/:/",$parts[$i],3,PREG_SPLIT_NO_EMPTY);
						if( array_key_exists(1,$atoms) ) {
							$prefix=$atoms[0];
							$localname=$atoms[1];
						} else {
							$prefix="_target_ns";
							$localname=$atoms[0];
						}
						//перепроверяем для надежности
						if( !array_key_exists($prefix, $this->xmlnss) ) {
							throw new Exception("namespace for prefix '".$prefix."' not found : check '".$this->schema."'");
						}
						$name=($prefix!="_target_ns"?$prefix.":":"").$localname;
						$ns=$this->xmlnss[$prefix];
						//переводим контекст на узелок
						$path.=($path?self::XPATH_DELIMITER:"").($parts[$i]."-".$index);
						try {
							//создаем контейнер для вложенных элементов
							if( $j<$len-1 ) {
								if( !isset($children[$path]) ) {
									$children[$path]=$parent->appendChild($this->dom->createElementNS($ns,$name));
								}
								//установим родителя будущего элемента
								$parent=$children[$path];
							} else {
								//для последнего узелка складываем значения прямо в элементы
								if( is_array($val) ) {
									//для параметров типа blabla[]
									if( array_key_exists($key, $_FILES) ) {
										//этот элемент - загруженный файл
										$count=count($val);
										for( $a=0; $a<$count; $a++ ) {
											$children[$path]=$parent->appendChild($this->dom->createElementNS($ns,$name));
											$children[$path]->appendChild($this->dom->createElementNS($ns,"name",self::cleanup($_FILES[$key]["name"][$a])));
											$children[$path]->appendChild($this->dom->createElementNS($ns,"tmp_name",$_FILES[$key]["tmp_name"][$a]));
											$children[$path]->appendChild($this->dom->createElementNS($ns,"size",$_FILES[$key]["size"][$a]));
										}
									} else {
										foreach( $val as $subval ) {
											$subval=self::cleanup($subval);
											if( strlen($subval)>0 ) { //есть значение
												$children[$path]=$parent->appendChild($this->dom->createElementNS($ns,$name,$subval));
											} elseif( substr($parts[$i],-2)=="::" ) { //пусто , есть признак skip
											} elseif( substr($parts[$i],-1)==":" ) { //пусто , есть признак nillable
												$children[$path]=$parent->appendChild($this->dom->createElementNS($ns,$name));
												$children[$path]->setAttributeNS("http://www.w3.org/2001/XMLSchema-instance","xsi:nil","true");
											} else { //пусто, создаем пустой элемент
												$children[$path]=$parent->appendChild($this->dom->createElementNS($ns,$name));
											}
										}
									}
								} else {
									$val=self::cleanup($val);
									if( $nolastindex ) {
										//нет индекса - значит аттрибут. массивы мы отмели раньше
										if( $parent->namespaceURI==$ns ) {
											$parent->setAttribute($localname,$val);
										}else {
											$parent->setAttributeNS($ns,$name,$val);
										}
									} elseif( array_key_exists($key, $_FILES) ) { //этот элемент - загруженный файл
										$children[$path]=$parent->appendChild($this->dom->createElementNS($ns,$name));
										$children[$path]->appendChild($this->dom->createElementNS($ns,"name",$val));
										$children[$path]->appendChild($this->dom->createElementNS($ns,"tmp_name",$_FILES[$key]["tmp_name"]));
										$children[$path]->appendChild($this->dom->createElementNS($ns,"size",$_FILES[$key]["size"]));
									} elseif( strlen($val)>0 ) { //есть значение
										$children[$path]=$parent->appendChild($this->dom->createElementNS($ns,$name,$val));
									} elseif( substr($parts[$i],-2)=="::" ) { //пусто , есть признак skip
									} elseif( substr($parts[$i],-1)==":" ) { //пусто , есть признак nillable
										$children[$path]=$parent->appendChild($this->dom->createElementNS($ns,$name));
										$children[$path]->setAttributeNS("http://www.w3.org/2001/XMLSchema-instance","xsi:nil","true");
									} else { //пусто, создаем пустой элемент
										$children[$path]=$parent->appendChild($this->dom->createElementNS($ns,$name));
									}
								}
								$this->empty=FALSE;
							}
						}catch(Exception $e) {
							trigger_error($e->getMessage());
						}
						$j++;
					} //конец обхода узелков пути элемента
				} //конец обхода элементов запроса
			}
		} //конец разбора url-form-encoded
	}

	/**
	 * @return boolean признак пустого запроса
	 */
	public function isEmpty() {
		return $this->empty;
	}
	/**
	 * Получить входные данные в виде XML
	 * @return string
	 */
	public function getAsXML() {
		//в отладке включить форматирование документа
		$this->dom->formatOutput=($_SERVER["PHP_SELF"][1]=="~"?TRUE:FALSE);
		return $this->dom->saveXML();
	}

	/**
	 * Получить входные данные в виде DOM
	 * @return DomDocument
	 */
	public function getAsDOM() {
		return $this->dom;
	}

	/**
	 * получить значение элемента по имени. подходит только для уникальных элементов (укороченная версия вызова getElementsByTagName)
	 * @param string $elname имя элемента
	 * @return string результат строкой или NULL
	 */
	public function getElementByTagName( $name ) {
		$entries=$this->dom->getElementsByTagName( $name );
		if( $entries->length==0 ) {
			return NULL;
		} elseif( $entries->length==1 ) {
			return $entries->item(0)->nodeValue;
		}
		//если нашлось больше одного - вероятно имя не уникально
		throw new Exception("duplicate name: '".$name."'");
	}
	/**
	 * получить значение элементов по имени в виде массива
	 * @param string $elname имя элемента
	 * @return string результат строкой или NULL
	 */
	public function getElementByTagNameArr( $name ) {
		$entries=$this->dom->getElementsByTagName( $name );
		$result=array();
		for($i=0; $i<$entries->length;$i++){
			$result[]=$entries->item($i)->nodeValue;
		}
		return $result;
	}
	/**
	 * Получить значение элемента по XPath-запросу. Запрос должен выбирать уникальный элемент - не набор узлов.
	 * @param string $query XPath-запрос
	 * @return string текстовое значение узла или NULL
	 */
	public function getVar( $query ) {
		$this->initXpath();
		$entries=$this->xpath->evaluate( $query );
		if( $entries->length==0 ) {
			return NULL;
		} elseif( $entries->length==1 ) {
			return $entries->item(0)->nodeValue;
		}
		//если несколько узлов - считаем запрос поставлен неверно
		throw new Exception("invalid xpath: '".$query."'");
	}
	/**
	 * Получить значение элементов по XPath-запросу в виде массива
	 * @param string $query XPath-запрос
	 * @return string текстовое значение узла или NULL
	 */
	public function getVarArray( $query ) {
		$this->initXpath();
		$result=array();
		$entries=$this->xpath->evaluate( $query );
		for($i=0; $i<$entries->length;$i++){
			$result[]=$entries->item($i)->nodeValue;
		}
		return $result;
	}

	/**
	 * Валидация данных запроса по схеме
	 * @return bool результат проверки
	 */
	public function validate() {
		try {
			return $this->dom->schemaValidate($this->schema);
		} catch(Exception $e) {
			throw new Exception($e->getMessage(),450);
		}
	}

	/**
	 * Подчищает входные данные - лишние пробелы, переносы и пр.
	 * @param string $value
	 * @return string
	 */
	public static function cleanup($value) {
		$replace_pairs=array();
		$replace_pairs["\r"]=''; //браузер для совместимости добавляет долбаный вендовый перевод строки - вырезаем его
		$replace_pairs["&"]='&amp;'; //экранировать амперсанд
		$replace_pairs["&#8470;"]='N'; //русский "номер" заменить на N
		$replace_pairs["&#171;"]='"';//заменить ковычки << на "
		$replace_pairs["&#187;"]='"';//заменить ковычки >> на "
		$replace_pairs["&#8211;"]='-';//заменить "длинное тире" на -
		$replace_pairs["&#8212;"]='-';//заменить "длинное тире" на -
		$replace_pairs["–"]='-';
		$replace_pairs["—"]='-';
		$replace_pairs["―"]='-';
		$replace_pairs["«"]='"';
		$replace_pairs["»"]='"';
		$replace_pairs["№"]='N';
		return trim(strtr($value, $replace_pairs));
	}

	/**
	 * Читает информацию о префиксах и нэймспейсах во внутреннюю структуру
	 * @param XMLReader $xr
	 */
	protected function readNSinfo( $xr ) {
		$this->xmlnss["_target_ns"]=NULL;
		while( $xr->read() ) {
			//первым попадется корневой элемент документа
			if( $xr->nodeType==XMLReader::ELEMENT ) {
				if($xr->hasAttributes && $xr->moveToFirstAttribute()) {
					do {
						if( $xr->name=="xmlns" ) {
							$this->xmlnss["_default_ns"]=$xr->value;
						} elseif( $xr->name=="targetNamespace") {
							$this->xmlnss["_target_ns"]=$xr->value;
						} elseif( !strncmp($xr->name,"xmlns:",6) ) {
							$this->xmlnss[substr($xr->name,6)]=$xr->value;
						}
					} while($xr->moveToNextAttribute());
				}
				//больше ничего неинтересно - выходим
				break;
			}
		}
	}

	/**
	 * Ищет имя первого описанного в схеме элемента
	 * @param XMLReader $xr
	 */
	protected function readRootName( $xr ) {
		while( $xr->read() ) {
			//считаем первый описанный в схеме элемент корневым в документе
			if( $xr->nodeType==XMLReader::ELEMENT ) {
				if( $xr->name==($xr->prefix?$xr->prefix.":element":"element") ) {
					$this->root=$xr->getAttribute("name");
					//больше ничего неинтересно - выходим
					break;
				}
			}
		}
	}

	/**
	 * Инициализирует XPath для документа, регистрирует префиксы к нэймспейсам в нем
	 */
	protected function initXpath() {
		if( !$this->xpath ) {
			//инициализируем при первом обращении
			$this->xpath=new DOMXPath( $this->dom );
			//нужно установить префиксы для нэймспейсов чтобы они совпадали
			//с локальными прописанными при выборе значений в коде (как в схеме)
			if( !count($this->xmlnss) ) {
				//прочитаем из файла схемы информацию о нэйпмпейсах и префиксах
				$xr=new XMLReader();
				$xr->open($this->schema);
				$this->readNSinfo($xr);
				$xr->close();
			}
			//регистрируем известные по схеме префиксы-нэймспейсы
			foreach ( $this->xmlnss as $prefix=>$ns ) {
				$this->xpath->registerNamespace($prefix,$ns);
			}
		}
	}

}

/* eof */
