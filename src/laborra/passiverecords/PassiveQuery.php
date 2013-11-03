<?php

namespace laborra\passiverecords;

class PassiveQuery
{
    public $modelClass;

    private $rows = null;
    private $where = null;
    private $order = array();

    public function __call($name, $params)
	{
		if (method_exists($this->modelClass, $name)) {
			array_unshift($params, $this);
			call_user_func_array(array($this->modelClass, $name), $params);
			return $this;
        }
	}

    /**
     * Sets the where condition of the query.
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

    public function orderBy ($field) {
        if (is_string($field)) {
            $field = explode(" ", $field);
        }

        if (!isset($field[1])) {
            $field[1] = "desc";
        }

        $this->order[$field[0]] = $field[1];

        return $this;
    }

    public function orderByArray () {
        // TODO
    }

    public function andWhere ($condition)
    {
        new PassiveAndCondition();
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
        $this->applyOrder();
        return $this->createModels();
    }

    public function one ()
    {
        $modelClass = $this->modelClass;
        $this->rows = $modelClass::getData();
        $this->applyWhere();
        $this->applyOrder();
        if (count($this->rows) == 0) {
            return null;
        }

        return $modelClass::create($this->rows[0]);
    }

    public function count ()
    {
        $modelClass = $this->modelClass;
        $this->rows = $modelClass::getData();
        $this->applyWhere();
        $this->applyOrder();
        return count($this->rows);
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
        
        $checker = new PassiveChecker($this->where, PassiveSchema::getForClass($this->modelClass));

        $result = array();
        foreach ($this->rows as $row) {
            if ($checker->isAcceptable($row) === true) {
                $result[] = $row;
            }
        }

        $this->rows = $result;
    }

    private function applyOrder ()
    {
        if (count($this->order) == 0) {
            return;
        }
        
        $schema = PassiveSchema::getForClass($this->modelClass);
        $orders = $this->order;
        usort($this->rows, function ($a, $b) use ($schema, $orders) {
            foreach ($orders as $columnName => $value) {
                $aValue = $schema->getValue($columnName, $a);
                $bValue = $schema->getValue($columnName, $b);
                if ($aValue > $bValue) {
                    return $value == 'asc' ? 1 : -1;
                }

                if ($aValue < $bValue) {
                    return $value == 'desc' ? 1 : -1;
                }
            }

            return 0;
        });
    }
}

interface Condition
{
    public function evaluate (PassiveSchema $schema, array $row);
}

abstract class BinaryCondition implements Condition
{
    protected $op1;
    protected $op2;

    public function __construct($op1, $op2) {
        if (strpos($op1, '`') !== false) {
            $op1 = new ColumnOperand(str_replace('`', '', $op1));
        } else {
            $op1 = new LiteralOperand($op1);
        }

        if (strpos($op2, '`') !== false) {
            $op2 = new ColumnOperand(str_replace('`', '', $op2));
        } else {
            $op2 = new LiteralOperand($op2);
        }
    }
}

class HashCondition implements Condition
{
    private $columnOperand;
    private $valueOperand;

    public function __construct ($columnName, $value) {
        $this->columnOperand = new ColumnOperand($columnName);
        $this->valueOperand = new LiteralOperand($value);
    }

    public function evaluate (PassiveSchema $schema, array $row)
    {
        return $this->columnOperand->getValue($schema, $row) == $this->valueOperand->getValue($schema, $row);
    }
}

class CompareCondition extends BinaryCondition
{
    private $operator;
    
    public function __construct ($operator, $op1, $op2)
    {
        parent::__construct($op1, $op2);
        $this->operator = $operator;
    }
    
    public function evaluate (PassiveSchema $schema, array $row)
    {
        $left = $this->op1->getValue($schema, $row);
        $right = $this->op2->getValue($schema, $row);
        
        if ($operator == '=') {
            return $left == $right;
        } elseif ($operator == '!=') {
            return $left == $right;
        } elseif ($operator == '>') {
            return $left > $right;
        } elseif ($operator == '>=') {
            return $left >= $right;
        } elseif ($operator == '<') {
            return $left < $right;
        } elseif ($operator == '<=') {
            return $left <= $right;
        }

        throw new PassiveQueryException('Invalid compare operator: '.print_r($this->operator, true));
    }
}

class LikeCondition extends BinaryCondition
{
    public function evaluate (PassiveSchema $schema, array $row)
    {
        // TODO - op1 must be a column and op2 must be a literal
        $colValue = $op1->getValue();
        $literalValue = str_replace('%', '*',
                str_replace('_', '.', $op2->getValue()));

        return preg_match("/".$literalValue."/", $colValue) > 0;
    }
}

class LogicCondition implements Condition
{
    private $conditions;
    private $operator;

    public function __construct ($operator, array $conditions)
    {
        $this->conditions = $conditions;
        $this->operator = $operator;
    }

    public function evaluate (PassiveSchema $schema, array $row)
    {
        foreach ($this->conditions as $condition) {
            $parts[] = $condition->evaluate($schema, $row);
        }

        if ($this->operator == 'AND') {
            return $this->arrayAnd($parts);
        }
        return $this->arrayOr($parts);
    }

    private function arrayAnd ($values)
    {
        $ret = true;
        foreach ($values as $value) {
            $ret = $ret && $value;
        }
        return $ret;
    }

    private function arrayOr ($values)
    {
        $ret = false;
        foreach ($values as $value) {
            $ret = $ret || $value;
        }
        return $ret;
    }
}

interface Operand
{
    public function getValue (PassiveSchema $schema, array $row);
}

class ColumnOperand implements Operand
{
    private $columnName;
    
    public function __construct ($columnName) {
        $this->columnName = $columnName;
    }
    public function getValue (PassiveSchema $schema, array $row) {
        return $schema->getValue($this->columnName, $row);
    }
}

class LiteralOperand implements Operand
{
    private $value;
    
    public function __construct($value) {
        $this->value = $value;
    }
    
    public function getValue(PassiveSchema $schema, array $row) {
        return $this->value;
    }
}

class ConditionFactory
{
    /**
     * 
     * @param string|array $conditionStr
     * @return Condition
     */
    public static function create ($condition)
    {
        if (is_array($condition)) {
            if (count($condition > 1)) {
                $conditions = array();
                foreach ($condition as $key => $value) {
                    $conditions[] = new HashCondition($key, $value);
                }
                return new LogicCondition('AND', $conditions);
            }
            $keys = array_keys($conditions);
            return new HashCondition($key[0], $conditions[$keys[0]]);
        }

        if (is_string($condition)) {
            return $this->parseString($condition);
        }
    }

    /**
     * LR Parsing system
     * 
     * @param type $condition
     */

    private function parseString ($condition)
    {
        // TODO
        throw new Exception("String parsing is not supported yet");
    }
}

