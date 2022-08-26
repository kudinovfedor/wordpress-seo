import getSentences from "../../../helpers/sentence/getSentences";
import getWords from "../../../helpers/word/getWords";

/**
 * Based on Seraph Jin (2022) https://github.com/Seraaphonano/formal_and_informal_english_classification.
 *
 * This implementation is specifically based on the Decision tree model with 6 layers maximum.
 */

const informalPronouns = [ "I", "you", "me", "my", "mine", "your", "yours", "myself", "yourself", "yourselves" ];
const formalPronouns = [ "we", "they", "their", "theirs", "themselves", "us", "our", "ours", "ourselves", "it", "its", "itself" ];

/**
 * Calculates the average length of units in an array.
 *
 * @param {Array} array The array to calculate.
 *
 * @returns {number} The total length of a units in the array. If the array is empty, returns 0.
 */
function calculateTotalLength( array ) {
	if ( array.length === 0 ) {
		return 0;
	}

	return array.map( unit => unit.length ).reduce( ( a, b ) => a + b );
}

/**
 * Gets the array of pronouns occurrences in the text.
 *
 * @param {Array} wordsArray    The array of words in the text to check.
 * @param {Array} pronouns      The array of pronouns.
 *
 * @returns {Array} The array of pronouns found.
 */
function getPronouns( wordsArray, pronouns ) {
	return wordsArray.filter( word => pronouns.includes( word ) );
}

/**
 * Gets all the required features for the calculation of the formality level of the text.
 *
 * @param {Object} paper        The paper to analyze.
 * @param {Object} researcher   The researcher object.
 *
 * @returns {Object} The object containing the features needed for the calculation of the formality level of the text.
 */
function getRequiredFeatures( paper, researcher ) {
	const text = paper.getText();
	const memoizedTokenizer = researcher.getHelper( "memoizedTokenizer" );
	const sentences = getSentences( text, memoizedTokenizer );
	const words = getWords( text );

	// Calculate the average letter length per sentence.
	// The total number of characters in the sentences in the text / the total number of the sentences.
	// 0.
	const totalCharactersInSentences = calculateTotalLength( sentences );
	const averageSentenceLength = totalCharactersInSentences / sentences.length;
	// Calculate the average word length per sentence = the total number of words in the text divided by the total number of the sentences.
	// 1.
	const averageWordLengthPerSentence = words.length / sentences.length;
	// Calculate the average word length = the total number of characters in the words in the text / the total number of words in the text.
	// 2.
	const averageWordLength = calculateTotalLength( words ) / words.length;
	// Calculate normalized passive voice size = the number of passives occurrences in the text / the total number of sentences.
	// 3.
	const passiveSentences = researcher.getResearch( "getPassiveVoiceResult" ).passives;
	const averagePassives = passiveSentences.length / sentences.length;
	// Calculate the average number of formal pronouns = the number of occurrences of formal pronouns / the total number of words in the text.
	// 4.
	const averageFormalPronouns = getPronouns( words, formalPronouns ).length / words.length;
	// Calculate the average number of informal pronouns = the number of occurrences of informal pronouns / the total number of words in the text.
	// 5.
	const averageInformalPronouns = getPronouns( words, informalPronouns ).length / words.length;

	return {
		averageSentenceLength,
		averageWordLengthPerSentence,
		averageWordLength,
		averagePassives,
		averageFormalPronouns,
		averageInformalPronouns,
	};
}

/* eslint-disable complexity */
/**
 * Checks if a text is formal or informal.
 *
 * @param {Object} paper        The paper to analyze.
 * @param {Object} researcher   The researcher object.
 *
 * @returns {string} The string "Formal" if the text is formal, and the string "Informal" if the text is informal.
 */
export default function( paper, researcher ) {
	const {
		averageWordLengthPerSentence,
		averageWordLength,
		averagePassives,
		averageFormalPronouns,
		averageInformalPronouns,
	} = getRequiredFeatures( paper, researcher );

	if ( averageWordLength <= 4.811 ) {
		if ( averageFormalPronouns <= 0.053 ) {
			if ( averageInformalPronouns <= 0.028 ) {
				if ( averagePassives <= 0.063 ) {
					return "formal";
				}
				return "informal";
			}
			return "informal";
		}
		if ( averagePassives <= 0.094 ) {
			return "informal";
		}
		return "formal";
	}

	if ( averageInformalPronouns <= 0.006 ) {
		if ( averagePassives <= 0.088 ) {
			if ( averageFormalPronouns <= 0.015 ) {
				return "informal";
			}
			return "formal";
		}
		return "formal";
	}

	if ( averageWordLengthPerSentence <= 21.365 ) {
		if ( averageFormalPronouns <= 0.006 ) {
			return "formal";
		}
		return "informal";
	}

	return "formal";
}
/* eslint-enable complexity */
