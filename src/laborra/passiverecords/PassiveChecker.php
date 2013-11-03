<?php

namespace laborra\passiverecords;

class PassiveChecker
{
    private $condition = array();
    private $schema;
    
    public function __construct (array $condition, PassiveSchema $schema)
    {
        $this->condition = $condition;
        $this->schema = $schema;
    }
    public function isAcceptable ($row)
    {
        return $this->checkRow($row, $this->condition);
    }
    
    public function checkRow ($row, $condition)
    {
        $checkers = array(
            'AND' => 'checkLogic',
            'OR' => 'checkLogic',
            'NOT' => 'checkNot',
            '!' => 'checkNot',
            'IN' => 'checkIn',
            '=' => 'checkCompare',
            '!=' => 'checkCompare',
            '<' => 'checkCompare',
            '>' => 'checkCompare',
            '<=' => 'checkCompare',
            '>=' => 'checkCompare',
        );
        
        if (!is_array($condition)) {
            throw new PassiveQueryException('Bad condition: '.print_r($condition, true));
        }
        if (!isset($condition[0])) {
            return $this->checkHashCondition($row, $condition);
        }
        $operator = strtoupper($condition[0]);
        if (!isset($checkers[$operator])) {
            throw new PassiveQueryException('Bad operator: '.print_r($operator, true));
        }
        array_shift($condition);
        $checker = $checkers[$operator];
        return $this->$checker($row, $operator, $condition);
    }
    
    public function checkHashCondition ($row, $condition)
    {
        $columns = $this->schema->columns;
        foreach ($condition as $key => $value) {
            $columnIndex = array_search($key, $columns);
            if ($columnIndex === false) {
                throw new PassiveQueryException("Bad column: $key");
            }
            if ($row[$columnIndex] != $value) {
                return false;
            }
        }
        
        return true;
    }
    
    public function checkLogic ($row, $operator, $operands)
    {
        $parts = array();
        
        foreach ($operands as $operand) {
            if (is_array($operand)) {
                $parts[] = $this->checkRow($row, $operand);
            } else {
                $parts[] = $operand == true;
            }
        }
        
        if ($operator == 'AND') {
            return $this->arrayAnd($parts);
        }
        return $this->arrayOr($parts);
    }
    
    public function arrayAnd ($values)
    {
        $ret = true;
        foreach ($values as $value) {
            $ret = $ret && $value;
        }
        return $ret;
    }
    
    public function arrayOr ($values)
    {
        $ret = false;
        foreach ($values as $value) {
            $ret = $ret || $value;
        }
        return $ret;
    }
    
    public function checkNot ($row, $operator, $operands)
    {
        if (count($operands) > 1) {
            throw new PassiveQueryException('NOT operator accepts only one operand: '.print_r($operands, true));
        }
        if (is_array($operands[0])) {
            return !$this->checkRow($row, $operands);
        }
        return $operands[0] == false;
    }
    
    public function checkIn ($row, $operator, $operands)
    {
        if (count($operands) != 2) {
            throw new PassiveQueryException('IN operator must have two operands: '.print_r($operands, true));
        }
        
        $set = $operands[1];
        if(!is_array($set)) {
            throw new PassiveQueryException('Bad set for IN operator: '.print_r($set, true));
        }
        
        $column = $operands[0];
        $columns = $this->schema->columns;
        $columnIndex = 0;
        
        if(!is_string($column) || ($columnIndex = array_search($column, $columns)) === false) {
            throw new PassiveQueryException('Bad column for IN operator: '.print_r($column, true));
        }
        
        
        
        
        $value = $row[$columnIndex];
        return array_search($value, $set) !== false;
    }
    
    public function checkCompare ($row, $operator, $operands)
    {
        // XXX - Distinguere meglio fra nomi di colonne e valori
        $columns = $this->schema->columns;
        
        $left = $operands[0];
        if (is_string($left)) {
            $columnIndex = array_search($left, $columns);
            if ($columnIndex !== false) {
                $left = $row[$columnIndex];
            }
        }
        
        $right = $operands[1];
        if (is_string($right)) {
            $columnIndex = array_search($right, $columns);
            if ($columnIndex !== false) {
                $right = $row[$right];
            }
        }
        
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
        
        throw new PassiveQueryException('Invalid compare operator: '.print_r($operator, true));
    }
}
