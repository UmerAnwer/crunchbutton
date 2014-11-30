<?php

class Controller_api_order extends Crunchbutton_Controller_RestAccount {

	public function init() {

						
		$restaurant = Admin::restaurantOrderPlacement();
		
		// list recent orders for restaurants
		// @todo: move this over to orders php
		if (c::getPagePiece(2) == 'restaurant-list-last') {
			if (is_numeric(c::getPagePiece(3)) && c::admin()->permission()->check(['global'])) {
				$restaurant = Restaurant::o(intval(c::getPagePiece(3)));
			}

			if ($restaurant->id_restaurant) {
				$out = [ 'id_restaurant' => intval( $restaurant->id_restaurant ), 'orders' => [] ];
				$orders = Order::q( 'SELECT * FROM `order` o WHERE id_restaurant = "' . $restaurant->id_restaurant . '" AND o.date BETWEEN NOW() - INTERVAL 7 DAY AND NOW() ORDER BY id_order DESC' );
				foreach( $orders as $order ) {
					$out[ 'orders' ][]	= array( 	'id_order' => $order->id_order,
																				'lastStatus' => $order->deliveryLastStatus(),
																				'name' => $order->name,
																				'phone' => $order->phone,
																				'date' => $order->date()->format( 'M jS Y g:i:s A' ),
																		);
				}
				echo json_encode( $out );
			} else {
				echo json_encode(['error' => 'invalid object']);
			}
			
			exit;
		}

		// post an order
		if (!c::getPagePiece(2) && $this->method() == 'post') {

			if (is_numeric($_POST['restaurant']) && c::admin()->permission()->check(['global'])){
				$restaurant = Restaurant::o(intval($_POST['restaurant']));
			}

			if ($restaurant && $restaurant->id_restaurant && $_POST[ 'restaurant' ] == $restaurant->id_restaurant) {
				$order = new Order;
				// card, subtotal, tip, name, phone, address
				$charge = $order->process( $_POST, 'restaurant' );
				if ($charge === true) {
					echo json_encode([
						'id_order' => $order->id_order,
						'id_user' => $order->user()->id_user,
						'final_price' => $order->final_price,
						'uuid' => (new Order($order->id_order))->uuid
					]);
				} else {
					echo json_encode(['status' => 'false', 'errors' => $charge]);
				}
			} else {
				echo json_encode(['status' => 'false', 'errors' => 'invalid request' ] );
			}
			exit;
		}

		$order = Order::uuid(c::getPagePiece(2));

		if (!$order->id_order) {
			$order = Order::o(c::getPagePiece(2));
		}

		if (get_class($order) != 'Cockpit_Order') {
			$order = $order->get(0);
		}

		if (!$order->id_order) {
			header('HTTP/1.0 404 Not Found');
			exit;
		}

		if (!c::admin()->permission()->check(['global','orders-all','orders-list-page']) && $restaurant->id_restaurant != $order->id_restaurant) {
			header('HTTP/1.1 401 Unauthorized');
			exit;
		}
		
		
		// update an order
		if ($this->method() == 'put') {

			$allowed = ['lat','lon','notes','address','phone','name'];
			$changed = false;
			foreach ($this->request() as $k => $v) {
				if (in_array($k, $allowed)) {
					$order->{$k} = c::db()->escape($v);
					$changed = true;
				}
			}
			
			if ($changed) {
				$order->save();
			}
		}
		
		switch (c::getPagePiece(3)) {
			case 'eta':
				if (c::getPagePiece(4) == 'refresh') {
					$order->eta(true);
				}
				if ($this->method() == 'post') {
					$eta = new Order_Eta([
						'id_order' => $order->id_order,
						'method' => $this->request()['method'],
						'time' => $this->request()['time'],
						'distance' => $this->request()['distance'],
						'date' => date('Y-m-d H:i:s')
					]);
					$eta->save();
				}
				echo $order->eta()->json();
				break;

			case 'status':
				echo json_encode($order->status()->last());
				break;

			default:
				$out = $order->ordersExports();
				$out['user'] = $order->user()->id_user ? $order->user()->exports() : null;
				$out['restaurant'] = $order->restaurant()->id_restaurant ? $order->restaurant()->exports() : null;

				echo json_encode($out);
				break;
		}

	}
}