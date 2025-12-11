(function ( $ ) {
	// Exit early if ACF is not present.
	if ( typeof acf === 'undefined' ) {
		return;
	}

	const stored = window.impekaAeScoreData || { fields: {}, groups: {}, all: 0 };

	const selectors = {
		score: '.acf-field[data-type="score"] input',
		subtotal: '.acf-field[data-type="score_subtotal"] input',
		total: '.acf-field[data-type="score_total"] input',
	};

	const gatherCurrentScores = () => {
		const current = {};

		$( selectors.score ).each( function () {
			const $el   = $( this );
			const key   = $el.data( 'score-key' );
			const group = $el.data( 'score-group' ) || '';
			const value = parseFloat( $el.val() );

			if ( ! key ) {
				return;
			}

			if ( ! Number.isNaN( value ) ) {
				current[ key ] = { value, group };
			}
		} );

		return current;
	};

	const sumScores = ( group ) => {
		const current = gatherCurrentScores();
		let sum       = 0;

		// Start with stored values, override with current if present.
		Object.keys( stored.fields || {} ).forEach( ( key ) => {
			const entry = stored.fields[ key ];
			if ( group && entry.group !== group ) {
				return;
			}

			const value = current[ key ] ? current[ key ].value : entry.value;

			if ( ! Number.isNaN( value ) ) {
				sum += Number( value );
			}
		} );

		// Add current values for any new score fields not yet stored.
		$( selectors.score ).each( function () {
			const $el      = $( this );
			const key      = $el.data( 'score-key' );
			const elGroup  = $el.data( 'score-group' ) || '';
			const hasStore = key && stored.fields && stored.fields[ key ];

			if ( group && elGroup !== group ) {
				return;
			}

			if ( hasStore ) {
				return;
			}

			const value = parseFloat( $el.val() );
			if ( ! Number.isNaN( value ) ) {
				sum += Number( value );
			}
		} );

		return sum;
	};

	const recalcSubtotals = () => {
		$( selectors.subtotal ).each( function () {
			const $input = $( this );
			const group  = $input.data( 'score-group' ) || '';
			const sum    = sumScores( group );

			$input.val( sum );
		} );
	};

	const recalcTotals = () => {
		$( selectors.total ).each( function () {
			const $input = $( this );
			const group  = $input.data( 'score-group' ) || '';
			const sum    = sumScores( group || null );

			$input.val( sum );
		} );
	};

	const recalc = () => {
		recalcSubtotals();
		recalcTotals();
	};

	acf.add_action( 'ready append', function ( $el ) {
		recalc();
	} );

	$( document ).on( 'input change', selectors.score, function () {
		recalc();
	} );
})( jQuery );
