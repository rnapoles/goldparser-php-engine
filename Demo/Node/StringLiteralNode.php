<?php
namespace Node;

class StringLiteralNode extends AbstractNode {
	public $data;

	function __construct($data){
		$this->data = $data;
}

	function execute(){
		//code
	}

}

