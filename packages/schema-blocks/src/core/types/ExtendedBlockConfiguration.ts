import { BlockConfiguration } from "@wordpress/blocks";

/*
 * See https://developer.wordpress.org/block-editor/developers/block-api/block-registration/#variations-optional
 * for other options.
 */
export type BlockVariation = {
	name: string;
	title: string;
	description?: string;
	icon?: string | object;
	isDefault?: boolean;
};

export type ExtendedBlockConfiguration = BlockConfiguration & {
	variations: BlockVariation[];
};
