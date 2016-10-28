<?php
namespace Node;

class ExpressionNode extends AbstractNode {
	public $addExpr;
	public $synError;

	// <Expression> ::= <AddExpr> 
	function _initAddExprNode(AddExprNode $addExpr){
		$this->addExpr = $addExpr;
	}

	// <Expression> ::= SynError 
	function _initSynErrorNode(SynErrorNode $synError){
		$this->synError = $synError;
	}

	function execute(){
		//code
	}

}

