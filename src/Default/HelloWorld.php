<?php declare (strict_types=1);

Namespace KuboPlugin\Default;

class HelloWorld {

	protected string $message = "";

	public function __construct(string $message = "Hello World!"){
		$this->message = $message;
	}

	public function getMessage(){
		return $this->message;
	}
}