<?php 

namespace library;

use library\DB;

class Crud {

    protected $fields = [];
    protected $variables = [];

    protected $table = '';
    protected $pk = 'id';

    protected $link = 'mysql';

	public function __construct() {
	    if (empty($this->table)) {
            $this->table = get_class($this);
            $this->table = substr($this->table, strrpos($this->table, '\\') + 1, -5);
        }
	}

    protected function getDb($link = null) {
        $link = $link ? $link : $this->link;
        return Db::getInstance($link);
    }

	public function __set($name, $value){
		if($name === $this->pk) {
			$this->variables[$this->pk] = $value;
		} else {
            if (empty($this->fields) || in_array($name, $this->fields)) {
                $this->variables[$name] = $value;
            }
		}
	}

	public function __get($name)
	{	
		if(is_array($this->variables)) {
			if(array_key_exists($name, $this->variables)) {
				return $this->variables[$name];
			}
		}
		$trace = debug_backtrace();
		trigger_error(
		'Undefined property via __get(): ' . $name .
		' in ' . $trace[0]['file'] .
		' on line ' . $trace[0]['line'],
		E_USER_NOTICE);
		return null;
	}

    protected function save($id = 0) {
		$this->variables[$this->pk] = $id ? $id : $this->variables[$this->pk];

		$fieldsvals = [];
		foreach($this->variables as $k => $v) {
			if($k !== $this->pk) {
                $fieldsvals[$k] = ':' . $k;
            }
		}

		if($fieldsvals) {
			return $this->getDb()->update('__tablepre__' . $this->table, $fieldsvals,  '`' . $this->pk . '` = :' . $this->pk, $this->variables);
		}
		return null;
	}

	public function create() {
        $fieldsvals = [];
        foreach($this->variables as $k => $v) {
            if($k !== $this->pk) {
                $fieldsvals[$k] = ':' . $k;
            }
        }

        if($fieldsvals) {
            return $this->getDb()->insert('__tablepre__' . $this->table, $fieldsvals, $this->variables);
        }
        return null;
	}

	public function delete($id = 0) {
		$id = (empty($this->variables[$this->pk])) ? $id : $this->variables[$this->pk];

		if(!empty($id)) {
			return $this->getDb()->delete('__tablepre__' . $this->table, '`' . $this->pk . '` = :' . $this->pk, [$this->pk => $id]);
		}
		return null;
	}

	public function get($id = 0) {
		$id = $id ? $id : $this->variables[$this->pk];

		if(!empty($id)) {
			$this->variables = $this->getDb()->field('*')->table('__tablepre__' . $this->table)->where('`' . $this->pk . '` = :' . $this->pk)->bindValue([$this->pk => $id])->find();
		}
        return $this->variables;
	}

}
