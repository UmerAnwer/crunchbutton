NGApp.factory( 'CreditService', function( $rootScope, $resource, ResourceFactory ) {

	var credit = $resource( App.service + 'credit/:action/:id_user/:method', { action: '@action', id_user: '@id_user', method: '@method' }, {
				'add' : { 'method': 'POST', params : { action: 'add' } },
				'history' : { 'method': 'GET', params : { action: 'log', method: 'history' } },
				'status' : { 'method': 'GET', params : { action: 'log', method: 'status' } },
			}
		);

	var service = {};

	service.add = function( params, callback ){
		credit.add( params, function( json ){
			callback( json );
		} );
	}

	service.status = function( id_user, callback ){
		credit.status( { 'id_user': id_user }, function( data ){
			callback( data );
		} );
	}

	service.history = function( id_user, callback ){
		credit.history( { 'id_user': id_user }, function( data ){
			callback( data );
		} );
	}

	return service;

} );