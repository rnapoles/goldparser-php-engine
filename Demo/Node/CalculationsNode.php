<?php
namespace Node;

class CalculationsNode extends AbstractNode {
	public $expression;
	public $calculations;

	// <Calculations> ::= <Expression> <Calculations> 
	function _init2(ExpressionNode $expression, CalculationsNode $calculations){
		$this->expression = $expression;
		$this->calculations = $calculations;
	}

	function execute(){
		//code
	}

}

