<?php
namespace Node;

class FactorNode extends AbstractNode {
	public $decLiteral;
	public $floatLiteral;
	public $stringLiteral;
	public $id;
	public $char1;
	public $params;
	public $char2;
	public $expression;
	public $factor;

	// <Factor> ::= DecLiteral 
	function _initDecLiteralNode(DecLiteralNode $decLiteral){
		$this->decLiteral = $decLiteral;
	}

	// <Factor> ::= FloatLiteral 
	function _initFloatLiteralNode(FloatLiteralNode $floatLiteral){
		$this->floatLiteral = $floatLiteral;
	}

	// <Factor> ::= StringLiteral 
	function _initStringLiteralNode(StringLiteralNode $stringLiteral){
		$this->stringLiteral = $stringLiteral;
	}

	// <Factor> ::= Id '(' <Params> ')' 
	function _init4(IdNode $id, $char1, ParamsNode $params, $char2){
		$this->id = $id;
		$this->char1 = $char1;
		$this->params = $params;
		$this->char2 = $char2;
	}

	// <Factor> ::= '(' <Expression> ')' 
	function _init3($char1, ExpressionNode $expression, $char2){
		$this->char1 = $char1;
		$this->expression = $expression;
		$this->char2 = $char2;
	}

	// <Factor> ::= '+' <Factor> 
	function _init2($char1, FactorNode $factor){
		$this->char1 = $char1;
		$this->factor = $factor;
	}

	function execute(){
		//code
	}

}

