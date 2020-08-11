<?php declare (strict_types=1);

Namespace KuboPlugin\TestPlugin;

class HelloWorld {

	public function getMessage(array $options = ["message"=>"Hello World"]){
		return [
			"message"=>$options["message"],
			"timestamp"=>time()
		];
	}
	
}