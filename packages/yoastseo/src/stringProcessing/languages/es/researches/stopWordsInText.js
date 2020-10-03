import getStopWords from "../config/passiveVoice/stopwords";
import stopWordsInText from "../../../abstract/stopWordsInText";
const stopwords = getStopWords();

/**
 * Checks a text to see if there are any stopwords, that are defined in the stopwords config.
 *
 * @param {string} text The input text to match stopwords.
 * @returns {Array} An array with all stopwords found in the text.
 */
export default function( text ) {
	stopWordsInText( text, stopwords );
}
