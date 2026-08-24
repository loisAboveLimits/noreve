/**
 * WordPress components that create the necessary UI elements for the block
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-components/
 */
import { PanelBody, ExternalLink, SelectControl, TextControl } from '@wordpress/components';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';

/**
 * WordPress server-side render component
 */
import ServerSideRender from '@wordpress/server-side-render';

/**
 * WordPress data hooks
 */
import { useSelect } from '@wordpress/data';

import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @param {Object}   props               Properties passed to the function.
 * @param {Object}   props.attributes    Available block attributes.
 * @param {Function} props.setAttributes Function that updates individual attributes.
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	const { engine, template, text, type } = attributes;

	// Get post ID using useSelect hook
	const post_id = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostId();
	}, [] );

	const typeOptions = [
		{
			label: __( 'Link', 'searchwp-modal-search-form' ),
			value: 'link'
		},
		{
			label: __( 'Button', 'searchwp-modal-search-form' ),
			value: 'button'
		},
		{
			label: __( 'Icon', 'searchwp-modal-search-form' ),
			value: 'icon'
		}
	];

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'searchwp-modal-search-form' ) } initialOpen={ true }>
					<TextControl
						label={ __( 'Text', 'searchwp-modal-search-form' ) }
						value={ text }
						onChange={ ( value ) => {
							setAttributes( { text: value } );
						} }
						__next40pxDefaultSize={ true }
						__nextHasNoMarginBottom={ true }
					/>

					<SelectControl
						label={ __( 'Template', 'searchwp-modal-search-form' ) }
						value={ `${ template }` }
						options={ typeof _SEARCHWP_MODAL_FORM_DATA !== 'undefined' ? _SEARCHWP_MODAL_FORM_DATA.templates : [] }
						onChange={ ( value ) => {
							setAttributes( { template: value } );
						} }
						__next40pxDefaultSize={ true }
						__nextHasNoMarginBottom={ true }
					/>

					<SelectControl
						label={ __( 'Type', 'searchwp-modal-search-form' ) }
						value={ `${ type }` }
						options={ typeOptions }
						onChange={ ( value ) => {
							setAttributes( { type: value } );
						} }
						__next40pxDefaultSize={ true }
						__nextHasNoMarginBottom={ true }
					/>
					<ExternalLink
						className={_SEARCHWP_MODAL_FORM_DATA.searchwp ? "hidden" : ""}
						href="https://searchwp.com/?utm_source=wordpressorg&..."
					>
						{__("Improve your search with SearchWP", "searchwp-modal-search-form")}
					</ExternalLink>
				</PanelBody>
			</InspectorControls>

			<ServerSideRender
				block="searchwp/modal-form"
				attributes={ { engine, template, text, type } }
			/>
		</div>
	);
}
