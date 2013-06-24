<?php

class PassiveQuery extends CComponent
{
    public $modelClass;
    
    private $rows = null;
    private $where = null;
    
    public function __call($name, $params)
	{
		if (method_exists($this->modelClass, $name)) {
			array_unshift($params, $this);
			call_user_func_array(array($this->modelClass, $name), $params);
			return $this;
		} else {
			return parent::__call($name, $params); // XXX - Questo potrebbe non servire
		}
	}
    
    /**
     * Set the where condition of the query.
     * Accept condition in the form:
     * 
     * array('column1' => 'value1', 'column2' => 'value2', ...)
     * for exact matching of the value.
     * 
     * array('<', 'column', 'value')
     * for comparing 
     */
    public function where ($condition)
    {
        $this->where = $condition;
        return $this;
    }
    
    public function andWhere ($condition)
    {
        if ($this->where === null)
            $this->where = $condition;
        else
            $this->where = array('and', $this->where, $condition);
            
        return $this;
    }
    
    public function orWhere ($condition)
    {
        if ($this->where === null)
            $this->where = $condition;
        else
            $this->where = array('or', $this->where, $condition);
            
        return $this;
    }
    
    public function all ()
    {
        $modelClass = $this->modelClass;
        $this->rows = $modelClass::getData();
        $this->applyWhere();
        return $this->createModels();
    }
    
    public function one ()
    {
        $modelClass = $this->modelClass;
        $this->rows = $modelClass::getData();
        $this->applyWhere();
        if (count($this->rows) == 0)
            return null;
        
        return $modelClass::create($this->rows[0]);
    }
    
    public function createModels ()
    {
        $models = array();
        $class = $this->modelClass;
        foreach ($this->rows as $row) {
            $models[] = $class::create($row);
        }
        return $models;
    }
    
    private function applyWhere ()
    {
        if ($this->where == null) {
            return;
        }
        $modelClass = $this->modelClass;
        $checker = new PassiveChecker($this->where, $modelClass::getSchema());
        
        $result = array();
        foreach ($this->rows as $row)
            if ($checker->isAcceptable($row) === true)
                $result[] = $row;
            
        $this->rows = $result;
    }
}

