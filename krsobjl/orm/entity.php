<?php
abstract class Entity {
	protected $module;
	protected $table;
	protected $data;
	protected $fields;
	
	private $fields_values;
	
	abstract public function getKey();
	
	public function setData($data) {
		$this->fields_values = array();
		
		foreach ($this->fields as $field => $default) {
			$this->fields_values[$field] = $data[$field];
		}
	}
	
	public function insert() {
		$data['NAME'] = &$nome;
		$data['ACTIVATION'] = &$action;
	
	
		return $data;
	}
	
	public function getModule() {
		return $this->module;
	}
	
	public function getTable() {
		return $this->table;
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function getFields() {
		return $this->fields_values;
	}
	
	public function getInstanceOf($field) {
		return KPDO::TYPE_VARCHAR;
	}
}