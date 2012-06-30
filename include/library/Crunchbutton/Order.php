<?php

class Crunchbutton_Order extends Cana_Table {
	public function process($params) {
		// @todo: add more security here

		$total = 0;

		foreach ($params['cart'] as $type => $typeItem) {
			switch ($type) {
				case 'dishes':
					foreach ($typeItem as $item) {
						$total += Dish::o($item['id'])->price;
						foreach ($item['toppings'] as $topping => $bleh) {
							$total += Topping::o($topping)->price;
						}
						foreach ($item['toppings'] as $topping => $bleh) {
							$total += Substitution::o($topping)->price;
						}
					}		
					break;
				case 'sides':
					foreach ($typeItem as $item) {
						$total += Side::o($item['id'])->price;
					}
					break;
				case 'extras':
					foreach ($typeItem as $item) {
						$total += Extra::o($item['id'])->price;
					}					
					break;
			}
		}

		$this->price = number_format($total, 2);
		$this->tip = $params['tip'];
		$this->id_restaurant = $params['restaurant'];
		$this->tax = $this->restaurant()->tax;
		$this->final_price = Util::ceil(
			($this->price * ($this->tip/100)) + // tip
			($this->price * ($this->tax/100)) + // tax
			$this->price
		, 2); // price

		$this->pay_type = $params['pay_type'] == 'cash' ? 'cash' : 'credit';
		$this->delivery_type = $params['delivery_type'] == 'delivery' ? 'delivery' : 'takeout';
		$this->_deliver = $request['deliver'];
		$this->_name = $request['name'];
		
		$this->_number = $request['card']['number'];
		$this->_exp_month = $request['card']['exp_month'];
		$this->_exp_year = $request['card']['exp_year'];
		
		$this->order = json_encode($params['cart']);
		
		if (!$this->verifyPayment()) {
			return false;
		} else {
			$this->txn = $this->transaction()->id;
		}
		
		c::auth()->session()->id_user = $user->id_user;
		
		return true;
	}
	
	public function restaurant() {
		return Restaurant::o($this->id_restaurant);
	}
	
	public function transaction() {
		return $this->_txn;
	}
	
	public function verifyPayment() {
		switch ($this->pay_type) {
			case 'cash':
				return true;
				break;

			case 'credit':
				$r = Charge::charge([
					'amount' => $this->final_price,
					'number' => $this->_number,
					'exp_month' => $this->_exp_month,
					'exp_year' => $this->_exp_year,
					'name' => $this->_name,
					'user' => c::user()->id_user ? c::user() : null
				]);
				if ($r['status']) {
					$this->_txn = $r['txn'];
					return true;
				} else {
					return false;
				}
				break;
		}
	}

	public function __construct($id = null) {
		parent::__construct();
		$this
			->table('order')
			->idVar('id_order')
			->load($id);
	}
}