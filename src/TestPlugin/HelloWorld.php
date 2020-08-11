<?php declare (strict_types=1);

Namespace KuboPlugin\TestPlugin;

class HelloWorld {

	public function getMessage(string $message = "Hello World!"){
		return [
			"message"=>$message,
			"timestamp"=>time()
		];
	}
	
}