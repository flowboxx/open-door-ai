( function ( wp ) {
	var el = wp.element.createElement;
	wp.blocks.registerBlockType( 'open-door-ai/ask', {
		edit: function () {
			return el( wp.serverSideRender, { block: 'open-door-ai/ask' } );
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
