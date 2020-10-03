import SyllableCountStep from "../../src/helpers/_todo/syllableCountStep";

describe( "SyllableCountStep", function() {
	it( "will not count syllables without a regex", function() {
		var countStep = new SyllableCountStep();

		expect( countStep.countSyllables( "word" ) ).toBe( 0 );
	} );
} );
