<?php
namespace Node;

class MulExprNode extends AbstractNode {
	public $factor;
	public $mulExpr;
	public $mulOp;

	// <MulExpr> ::= <Factor> 
	function _initFactorNode(FactorNode $factor){
		$this->factor = $factor;
	}

	// <MulExpr> ::= <MulExpr> <MulOp> <Factor> 
	function _init3(MulExprNode $mulExpr, MulOpNode $mulOp, FactorNode $factor){
		$this->mulExpr = $mulExpr;
		$this->mulOp = $mulOp;
		$this->factor = $factor;
	}

	function execute(){
		//code
	}

}

