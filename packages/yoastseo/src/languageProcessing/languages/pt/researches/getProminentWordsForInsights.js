import { get } from "lodash-es";
import getProminentWordsForInsights from "../../../researches/base/getProminentWordsForInsights";
import stemmer from "../morphology/stem";
import getFunctionWords from "../config/functionWords";
const functionWords = getFunctionWords().all;

/**
 * Retrieves the prominent words from the given paper.
 *
 * @inheritDoc getProminentWordsForInsights
 */
export default function( paper, researcher ) {
	// Assign the stemmer to identity function for when there is no available morphology data file.
	// eslint-disable-next-line require-jsdoc
	let stemmerPT = word => word;
	const morphologyData = get( researcher.getData( "morphology" ), "pt", false );

	if ( morphologyData ) {
		stemmerPT = stemmer;
	}
	return getProminentWordsForInsights( paper, researcher, stemmerPT, functionWords, morphologyData );
}
