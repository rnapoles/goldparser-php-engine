<?php
namespace Node;

class ParamsNode extends AbstractNode {
	public $expression;
	public $char1;
	public $params;

	// <Params> ::= <Expression> ',' <Params> 
	function _init3(ExpressionNode $expression, $char1, ParamsNode $params){
		$this->expression = $expression;
		$this->char1 = $char1;
		$this->params = $params;
	}

	// <Params> ::= <Expression> 
	function _initExpressionNode(ExpressionNode $expression){
		$this->expression = $expression;
	}

	function execute(){
		//code
	}

}

