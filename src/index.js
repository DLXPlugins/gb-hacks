import { setDefaultBlockName } from '@wordpress/blocks';
import { addAction } from '@wordpress/hooks';
import './js/blocks/pattern-importer/index.js';

let previousBlocks = [];

// Run on load.
( function( wp ) {
	// Check to see if the default block is a headline. If not, return.
	const defaultHeadlineBlockEnabled = gbHacksPatternInserter.defaultHeadlineBlockEnabled;
	if ( ! defaultHeadlineBlockEnabled ) {
		return;
	}

	// Get the default element name.
	const defaultHeadlineElement = gbHacksPatternInserter.defaultHeadlineBlockElement;

	wp.data.subscribe( () => {
		// Try to find if the paragraph needs to be converted to a headline.
		const currentBlocks = wp.data.select( 'core/block-editor' ).getBlocks();
		const currentBlock = wp.data.select( 'core/block-editor' ).getSelectedBlock();

		// Set the default block. Needs to run every render otherwise is forgotten.
		setDefaultBlockName( 'generateblocks/headline' );

		// If no block is selected, no need to go further.
		if ( null === currentBlock || 'undefined' === typeof currentBlock ) {
			previousBlocks = currentBlocks;
			return;
		}

		// Check that selected block's client ID is not in previous blocks.
		if ( previousBlocks.includes( currentBlock.clientId ) ) {
			previousBlocks = currentBlocks;
			return;
		}
		previousBlocks = currentBlocks;

		// Get the block's index.
		const blockIndex = wp.data.select( 'core/block-editor' ).getBlockIndex( currentBlock.clientId );

		// If previous block is a headline, then the next block should be a headline too.
		if ( blockIndex > 0 ) {
			const previousBlock = wp.data.select( 'core/block-editor' ).getBlocks()[ blockIndex - 1 ];
			if ( previousBlock.name === 'generateblocks/headline' && currentBlock.name === 'core/paragraph' && currentBlock.attributes.content === '' ) {
				wp.data.dispatch( 'core/block-editor' ).replaceBlocks( currentBlock.clientId, [
					wp.blocks.createBlock( 'generateblocks/headline', {
						uniqueId: '',
						content: currentBlock.attributes.content,
						element: defaultHeadlineElement,
					} ),
				] );
			}
		}
	} );

	/**
	 * Change default headline element to paragraph.
	 */
	addAction( 'generateblocks.editor.renderBlock', 'generateblocks/editor/renderBlock', function( props ) {
		if ( props.attributes.uniqueId === '' ) {
			props.attributes.element = defaultHeadlineElement;

			// Max iterations.
			const maxIterations = 50;
			let currentIteration = 0;

			const intervalId = setInterval( function() {
				if ( currentIteration > maxIterations ) {
					clearInterval( intervalId );
				}
				if ( 'undefined' !== typeof props.headlineRef && props.headlineRef.current !== null ) {
					const headline = props.headlineRef.current;
					headline.querySelector( '.block-editor-rich-text__editable' ).focus();
					clearInterval( intervalId );
				}
				currentIteration++;
			}, 200 );
		}
	} );
}( window.wp ) );
