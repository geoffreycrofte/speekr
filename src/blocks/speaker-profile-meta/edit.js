/**
 * Speaker Profile Meta Panels
 *
 * Four PluginDocumentSettingPanel components for Speaker Profile metadata.
 *
 * Note on RichText: RichText is broken in PluginDocumentSettingPanel since WP 6.5
 * (Gutenberg issue #60524, still unresolved). TextareaControl is used for all bio
 * and rider fields instead. Stored data is plain text; rich formatting can be
 * applied at render time in Phase 4 display blocks.
 */

import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { TextControl, TextareaControl, SelectControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const PLATFORMS = [
	{ value: '', label: __( 'Select platform…', 'speekr' ) },
	{ value: 'linkedin', label: 'LinkedIn' },
	{ value: 'facebook', label: 'Facebook' },
	{ value: 'instagram', label: 'Instagram' },
	{ value: 'bluesky', label: 'Bluesky' },
	{ value: 'mastodon', label: 'Mastodon' },
	{ value: 'x', label: 'X' },
	{ value: 'github', label: 'GitHub' },
	{ value: 'personal', label: __( 'Personal website', 'speekr' ) },
	{ value: 'other', label: __( 'Other', 'speekr' ) },
];

const SpeakerProfileMetaPanels = () => {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	// Social links add-form local state.
	const [ newPlatform, setNewPlatform ] = useState( '' );
	const [ newUrl, setNewUrl ] = useState( '' );
	const [ newLabel, setNewLabel ] = useState( '' );

	if ( ! meta ) return null;

	// -------------------------------------------------------------------------
	// Panel 1 — Headshots
	// -------------------------------------------------------------------------

	const headshots = meta._speekr_headshots ?? [];

	const addHeadshot = ( media ) => {
		setMeta( {
			...meta,
			_speekr_headshots: [ ...headshots, { id: media.id, label: '' } ],
		} );
	};

	const removeHeadshot = ( index ) => {
		const updated = headshots.filter( ( _, i ) => i !== index );
		setMeta( { ...meta, _speekr_headshots: updated } );
	};

	const updateLabel = ( index, label ) => {
		const updated = headshots.map( ( shot, i ) =>
			i === index ? { ...shot, label } : shot
		);
		setMeta( { ...meta, _speekr_headshots: updated } );
	};

	// Arrow-button reorder: ↑/↓ buttons move item up or down in the array.
	// Simpler and more accessible than drag-and-drop for the MVP.
	// Drag-to-reorder can be layered in as a future enhancement.
	const reorder = ( fromIndex, toIndex ) => {
		const updated = [ ...headshots ];
		const [ moved ] = updated.splice( fromIndex, 1 );
		updated.splice( toIndex, 0, moved );
		setMeta( { ...meta, _speekr_headshots: updated } );
	};

	// -------------------------------------------------------------------------
	// Panel 3 — Social Links
	// -------------------------------------------------------------------------

	const socialLinks = meta._speekr_social_links ?? [];

	const addLink = () => {
		if ( ! newPlatform || ! newUrl ) return;
		const entry = { platform: newPlatform, url: newUrl, label: newLabel };
		setMeta( { ...meta, _speekr_social_links: [ ...socialLinks, entry ] } );
		setNewPlatform( '' );
		setNewUrl( '' );
		setNewLabel( '' );
	};

	const removeLink = ( index ) => {
		setMeta( {
			...meta,
			_speekr_social_links: socialLinks.filter( ( _, i ) => i !== index ),
		} );
	};

	// -------------------------------------------------------------------------
	// Panel 4 — Rider
	// RichText not used — broken in PluginDocumentSettingPanel since WP 6.5
	// (Gutenberg issue #60524). Using TextareaControl per category.
	// -------------------------------------------------------------------------

	const rider = meta._speekr_rider ?? { av: '', travel: '', dietary: '', accessibility: '' };
	const updateRider = ( key, value ) =>
		setMeta( { ...meta, _speekr_rider: { ...rider, [ key ]: value } } );

	return (
		<>
			{ /* ----------------------------------------------------------------
			     Panel 1 — Headshots
			     ---------------------------------------------------------------- */ }
			<PluginDocumentSettingPanel
				name="speekr-speaker-headshots"
				title={ __( 'Headshots', 'speekr' ) }
			>
				<div className="speekr-headshots-list">
					{ headshots.map( ( shot, index ) => (
						<div key={ shot.id } className="speekr-headshot-item">
							{ index === 0 && (
								<span className="speekr-primary-badge">
									{ __( 'Primary', 'speekr' ) }
								</span>
							) }
							<span>{ `${ __( 'Attachment', 'speekr' ) } #${ shot.id }` }</span>
							<TextControl
								label={ __( 'Label', 'speekr' ) }
								value={ shot.label }
								onChange={ ( label ) => updateLabel( index, label ) }
							/>
							<Button
								variant="secondary"
								onClick={ () => reorder( index, index - 1 ) }
								disabled={ index === 0 }
								aria-label={ __( 'Move up', 'speekr' ) }
							>
								{ '↑' }
							</Button>
							<Button
								variant="secondary"
								onClick={ () => reorder( index, index + 1 ) }
								disabled={ index === headshots.length - 1 }
								aria-label={ __( 'Move down', 'speekr' ) }
							>
								{ '↓' }
							</Button>
							<Button
								isDestructive
								variant="secondary"
								onClick={ () => removeHeadshot( index ) }
								aria-label={ __( 'Remove headshot', 'speekr' ) }
							>
								{ '×' }
							</Button>
						</div>
					) ) }
				</div>
				<MediaUploadCheck>
					<MediaUpload
						onSelect={ addHeadshot }
						allowedTypes={ [ 'image' ] }
						render={ ( { open } ) => (
							<Button onClick={ open } variant="secondary">
								{ __( 'Add Headshot', 'speekr' ) }
							</Button>
						) }
					/>
				</MediaUploadCheck>
			</PluginDocumentSettingPanel>

			{ /* ----------------------------------------------------------------
			     Panel 2 — Bio
			     RichText not used — broken in PluginDocumentSettingPanel since
			     WP 6.5 (Gutenberg issue #60524). Using TextareaControl instead.
			     ---------------------------------------------------------------- */ }
			<PluginDocumentSettingPanel
				name="speekr-speaker-bio"
				title={ __( 'Bio', 'speekr' ) }
			>
				<TextareaControl
					label={ __( 'Short Bio', 'speekr' ) }
					value={ meta._speekr_bio_short ?? '' }
					onChange={ ( v ) => setMeta( { ...meta, _speekr_bio_short: v } ) }
					rows={ 3 }
					help={ __( 'Used for program intros. Plain text only in editor — formatting applied at render.', 'speekr' ) }
				/>
				<TextareaControl
					label={ __( 'Long Bio', 'speekr' ) }
					value={ meta._speekr_bio_long ?? '' }
					onChange={ ( v ) => setMeta( { ...meta, _speekr_bio_long: v } ) }
					rows={ 6 }
				/>
			</PluginDocumentSettingPanel>

			{ /* ----------------------------------------------------------------
			     Panel 3 — Social Links
			     ---------------------------------------------------------------- */ }
			<PluginDocumentSettingPanel
				name="speekr-speaker-social"
				title={ __( 'Social Links', 'speekr' ) }
			>
				{ socialLinks.length > 0 && (
					<ul className="speekr-social-links-list">
						{ socialLinks.map( ( entry, index ) => (
							<li key={ index }>
								<span>
									{ entry.platform === 'other' && entry.label
										? entry.label
										: entry.platform }
									{ ' — ' }
									{ entry.url }
								</span>
								<Button
									isDestructive
									variant="secondary"
									onClick={ () => removeLink( index ) }
									aria-label={ __( 'Remove link', 'speekr' ) }
								>
									{ '×' }
								</Button>
							</li>
						) ) }
					</ul>
				) }
				<SelectControl
					label={ __( 'Platform', 'speekr' ) }
					options={ PLATFORMS }
					value={ newPlatform }
					onChange={ setNewPlatform }
				/>
				{ newPlatform !== '' && (
					<TextControl
						label={ __( 'URL', 'speekr' ) }
						type="url"
						value={ newUrl }
						onChange={ setNewUrl }
					/>
				) }
				{ newPlatform === 'other' && (
					<TextControl
						label={ __( 'Label', 'speekr' ) }
						value={ newLabel }
						onChange={ setNewLabel }
					/>
				) }
				{ newPlatform && newUrl && (
					<Button variant="primary" onClick={ addLink }>
						{ __( 'Add', 'speekr' ) }
					</Button>
				) }
			</PluginDocumentSettingPanel>

			{ /* ----------------------------------------------------------------
			     Panel 4 — Rider
			     RichText not used — broken in PluginDocumentSettingPanel since
			     WP 6.5 (Gutenberg issue #60524). Using TextareaControl per
			     category.
			     ---------------------------------------------------------------- */ }
			<PluginDocumentSettingPanel
				name="speekr-speaker-rider"
				title={ __( 'Rider', 'speekr' ) }
			>
				<TextareaControl
					label={ __( 'AV / Tech requirements', 'speekr' ) }
					value={ rider.av ?? '' }
					onChange={ ( v ) => updateRider( 'av', v ) }
					rows={ 3 }
				/>
				<TextareaControl
					label={ __( 'Travel & accommodation', 'speekr' ) }
					value={ rider.travel ?? '' }
					onChange={ ( v ) => updateRider( 'travel', v ) }
					rows={ 3 }
				/>
				<TextareaControl
					label={ __( 'Dietary restrictions', 'speekr' ) }
					value={ rider.dietary ?? '' }
					onChange={ ( v ) => updateRider( 'dietary', v ) }
					rows={ 3 }
				/>
				<TextareaControl
					label={ __( 'Accessibility needs', 'speekr' ) }
					value={ rider.accessibility ?? '' }
					onChange={ ( v ) => updateRider( 'accessibility', v ) }
					rows={ 3 }
				/>
			</PluginDocumentSettingPanel>
		</>
	);
};

export default SpeakerProfileMetaPanels;
