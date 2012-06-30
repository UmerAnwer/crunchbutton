<?php

class Controller_api_order extends Crunchbutton_Controller_Rest {
	public function init() {
		switch ($this->method()) {
			case 'get':
				$order = Order::o(c::getPagePiece(2));
				if ($order->id_order) {
					echo $order->json();
				} else {
					echo json_encode(['error' => 'invalid object']);
				}
				break;

			case 'post':
				$order = new Order;
				$charge = $order->process($this->request());
				if ($charge) {
					c::auth()->session()->id_user
				} else {
					echo json_encode(['error' => 'failed for something']);
				}
				break;
		}
	}
}