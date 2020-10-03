/** @module analyses/calculateFleschReading */

import formatNumber from "../../../../helpers/formatNumber.js";
import getFleschReadingStatistics from "../../../researches/getFleschReadingStatistics";

/**
 * This calculates the flesch reading score for a given text.
 *
 * @param {object} paper The paper containing the text
 * @returns {number} The score of the flesch reading test
 */
export default function( paper ) {
	const { numberOfWords, numberOfSyllables, numberOfSentences } = getFleschReadingStatistics( paper );

	const score = 207 - ( 1.015 * numberOfWords / numberOfSentences ) - ( 73.6 * numberOfSyllables / numberOfWords );
	return formatNumber( score );
}
