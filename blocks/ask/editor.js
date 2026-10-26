( function ( wp ) {
	var el = wp.element.createElement;
	wp.blocks.registerBlockType( 'ai-door/ask', {
		edit: function () {
			return el( wp.serverSideRender, { block: 'ai-door/ask' } );
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
