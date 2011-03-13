<?php
/**
 * Этот скрипт содержит класс, предназначенный для преобразования PHP массивов в
 * XML формат. Поддерживаются многомерные массивы.
 * 
 * Пример использования:
 * 
 *
 * @author Стаценко Владимир http://www.simplecoding.org <vova_33@gala.net>
 * @version 0.1
 */

/**
 * Этот класс предназначен для преобразования PHP массива в XML формат
 */
class Array2XML {
	
	private $writer;
	private $version = '1.0';
	private $encoding = 'UTF-8';
	private $rootName = 'root';
	
	//конструктор
	function __construct() {
		$this->writer = new XMLWriter();
	}
	
	/**
	 * Преобразование PHP массива в XML формат.
	 * Если исходный массив пуст, то XML файл будет содержать только корневой тег.
	 *
	 * @param $data - PHP массив
	 * @return строка в XML формате
	 */
	public function convert($data) {
		$this->writer->openMemory();
		$this->writer->startDocument($this->version, $this->encoding);
		$this->writer->startElement($this->rootName);
		if (is_array($data)) {
			$this->getXML($data, $this->rootName);
		}
		$this->writer->endElement();
		return $this->writer->outputMemory();
	}
	
	/**
	 * Установка версии XML
	 *
	 * @param $version - строка с номером версии
	 */
	public function setVersion($version) {
		$this->version = $version;
	}
	
	/**
	 * Установка кодировки
	 *
	 * @param $version - строка с названием кодировки
	 */
	public function setEncoding($encoding) {
		$this->encoding = $encoding;
	}
	
	/**
	 * Установка имени корневого тега
	 *
	 * @param $version - строка с названием корневого тега
	 */
	public function setRootName($rootName) {
		$this->rootName = $rootName;
	}
	
	/*
	 * Этот метод преобразует данные массива в XML строку.
	 * Если массив многомерный, то метод вызывается рекурсивно.
	 */
	private function getXML($data, $parentKey) {
		foreach ($data as $key => $val) {
			if (is_numeric($key)) {
				$key = rtrim(substr($parentKey, 0, -1), 'e');
			}
			if (is_array($val)) {
				$e = explode(' ', $key, 2);
				$this->writer->startElement($e[0]);
				if (isset($e[1])) {
					$p = json_decode($e[1], true);
					foreach ($p as $k => $v) {
						$this->writer->writeAttribute($k, $v);
					}
				}
				$this->getXML($val, $e[0]);
				$this->writer->endElement();
			}
			elseif (substr($key, 0, 1) == '!') {
				$this->writer->startElement(substr($key, 1));
				$this->writer->writeCData($val);
				$this->writer->endElement();
			}
			else {
				$this->writer->writeElement($key, $val);
			}
		}
	}
}
//end of Array2XML.php