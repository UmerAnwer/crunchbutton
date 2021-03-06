<?php

class Controller_api_restaurant_hours extends Crunchbutton_Controller_Rest {
	public function init() {

		$r = Restaurant::o( c::getPagePiece( 3 ) );
		if( !$r->id_restaurant ){
			echo json_encode( [ 'error' => 'invalid object' ] );
			exit;
		}

		switch ( c::getPagePiece( 4 ) ) {

			case 'week':
				// export the hours for the whole week
				$no_utc = ( c::getPagePiece( 5 ) != 'regular' );
				$hours = $r->hours_week( $no_utc );
				if( !$hours ){ $hours = []; }
				echo json_encode( $hours );exit;;
				break;

			case 'status':
				$status = ( $r->open() ) ? 'open' : 'closed';
				echo json_encode( [ 'status' => $status ] );exit;;
				break;

			case 'next-open':
				$date = $r->next_open_time();
				if( $date ){
					$data = [];
					$data[ 'restaurant' ] = [ 'time' => $date->format( 'Y-m-d H:i' ), 'tz'=> $date->getTimezone()->getName() ];
					$date->setTimezone( new DateTimeZone( 'GMT' ) );
					$data[ 'utc' ] = [ 'time' => $date->format( 'Y-m-d H:i' ), 'tz'=> $date->getTimezone()->getName() ];
					echo json_encode( [ 'next-open' => $data ] );exit;;
				}
				echo json_encode( [ 'next-open' => false ] );exit;;
				break;

			case 'next-close':
				$date = $r->next_close_time();
				if( $date ){
					$data = [];
					$data[ 'restaurant' ] = [ 'time' => $date->format( 'Y-m-d H:i' ), 'tz'=> $date->getTimezone()->getName() ];
					$date->setTimezone( new DateTimeZone( 'GMT' ) );
					$data[ 'utc' ] = [ 'time' => $date->format( 'Y-m-d H:i' ), 'tz'=> $date->getTimezone()->getName() ];
					echo json_encode( [ 'next-close' => $data ] );exit;;
				}
				echo json_encode( [ 'next-close' => false ] );exit;;
				break;

			// return the closed message
			case 'closed-message':
				echo json_encode( [ 'closed-message' => $r->closed_message() ] );exit;;
				break;

			// return the amount of time to open
			case 'closes-in':
				$minutes = $r->closesIn();
				if( $minutes ){
					echo json_encode( [ 'closes-in' => Cana_Util::formatMinutes( $minutes ) ] );exit;;
				}
				echo json_encode( [ 'closes-in' => false ] );exit;;
				break;

			// return the amount of time to open
			case 'opens-in':
				$minutes = $r->opensIn();
				if( $minutes ){
					echo json_encode( [ 'opens-in' => Cana_Util::formatMinutes( $minutes ) ] );exit;;
				}
				echo json_encode( [ 'opens-in' => false ] );exit;;
				break;

			case 'pre-order':
				echo json_encode( $r->preOrderHours() );
				break;

			case 'gmt':
				$no_utc = ( c::getPagePiece( 4 ) != 'regular' );
				$hours = $r->hours_next_24_hours( $no_utc );
				if( !$hours ){ $hours = []; }
				$utc_str = gmdate( 'Y/n/d/H/i/s', time() );

				$hours = [ 'hours' => $hours, 'gmt' => $utc_str ];

				if( $r->allowPreorder() ){
					$hours[ 'allow_preorder' ] = true;
					$hours[ '_preOrderDays' ] = $r->preOrderHours();
					if( $r->preOrderTimeToTime ){
						$hours[ 'preOrderTimeToTime' ] = 'Pre-order for ' . $r->preOrderTimeToTime;
					}
					if( !$hours[ '_preOrderDays' ] ){
						$hours[ 'allow_preorder' ] = false;
					}
				} else {
					$hours[ 'allow_preorder' ] = false;
				}

				if( !$hours[ '_open' ] && $hours[ 'allow_preorder' ] ){
					$hours[ 'force_pre_order' ] = true;
				}


				echo json_encode( $hours );exit;;
				break;

			// export the hours for the next 24 hours
			default:
				$no_utc = ( c::getPagePiece( 4 ) != 'regular' );
				$hours = $r->hours_next_24_hours( $no_utc );
				if( !$hours ){ $hours = []; }
				echo json_encode( $hours );exit;;
				break;
		}
	}
}