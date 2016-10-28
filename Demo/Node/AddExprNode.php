<?php
namespace Node;

class AddExprNode extends AbstractNode {
	public $mulExpr;
	public $addExpr;
	public $addOp;

	// <AddExpr> ::= <MulExpr> 
	function _initMulExprNode(MulExprNode $mulExpr){
		$this->mulExpr = $mulExpr;
	}

	// <AddExpr> ::= <AddExpr> <AddOp> <MulExpr> 
	function _init3(AddExprNode $addExpr, AddOpNode $addOp, MulExprNode $mulExpr){
		$this->addExpr = $addExpr;
		$this->addOp = $addOp;
		$this->mulExpr = $mulExpr;
	}

	function execute(){
		//code
	}

}

