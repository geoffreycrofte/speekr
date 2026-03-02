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
import { image, formatBold, share, formatListBullets } from '@wordpress/icons';

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

/**
 * HeadshotItem sub-component.
 *
 * Renders a single headshot row using getMedia from WordPress core store to
 * display an actual image thumbnail. Supports HTML5 drag-to-reorder.
 */
const HeadshotItem = ( { shot, index, dragIndex, onDragStart, onDrop, onRemove, onLabelChange } ) => {
	const media = useSelect(
		( select ) => select( 'core' ).getMedia( shot.id ),
		[ shot.id ]
	);

	return (
		<div
			className={ `speekr-headshot-item${ dragIndex === index ? ' is-dragging' : '' }` }
			draggable
			onDragStart={ () => onDragStart( index ) }
			onDragOver={ ( e ) => e.preventDefault() }
			onDrop={ () => onDrop( index ) }
		>
			{ index === 0 && (
				<span className="speekr-primary-badge">
					{ __( 'Primary', 'speekr' ) }
				</span>
			) }
			{ media?.source_url ? (
				<img
					src={ media.source_url }
					alt={ media.alt_text || shot.label || __( 'Headshot', 'speekr' ) }
					className="speekr-headshot-thumb"
					width={ 80 }
					height={ 80 }
					style={ { objectFit: 'cover', borderRadius: '4px' } }
				/>
			) : (
				<div className="speekr-headshot-placeholder">
					{ __( 'Loading\u2026', 'speekr' ) }
				</div>
			) }
			<TextControl
				label={ __( 'Label', 'speekr' ) }
				value={ shot.label }
				onChange={ ( label ) => onLabelChange( index, label ) }
			/>
			<Button
				isDestructive
				variant="secondary"
				onClick={ () => onRemove( index ) }
				aria-label={ __( 'Remove headshot', 'speekr' ) }
			>
				{ '\u00d7' }
			</Button>
		</div>
	);
};

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

	// Headshots drag state.
	const [ dragIndex, setDragIndex ] = useState( null );

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

	const handleDragStart = ( index ) => setDragIndex( index );
	const handleDrop = ( toIndex ) => {
		if ( dragIndex !== null && dragIndex !== toIndex ) {
			const updated = [ ...headshots ];
			const [ moved ] = updated.splice( dragIndex, 1 );
			updated.splice( toIndex, 0, moved );
			setMeta( { ...meta, _speekr_headshots: updated } );
		}
		setDragIndex( null );
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
				icon={ image }
				className={ `speekr-panel-headshots${ headshots.length > 0 ? ' is-filled' : '' }` }
			>
				<div className="speekr-headshots-list">
					{ headshots.map( ( shot, index ) => (
						<HeadshotItem
							key={ shot.id }
							shot={ shot }
							index={ index }
							dragIndex={ dragIndex }
							onDragStart={ handleDragStart }
							onDrop={ handleDrop }
							onRemove={ removeHeadshot }
							onLabelChange={ updateLabel }
						/>
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
			     Long Bio removed — post_content (block editor body) is the
			     long-form content area for Speaker Profiles.
			     ---------------------------------------------------------------- */ }
			<PluginDocumentSettingPanel
				name="speekr-speaker-bio"
				title={ __( 'Bio', 'speekr' ) }
				icon={ formatBold }
				className={ `speekr-panel-bio${ meta._speekr_bio_short ? ' is-filled' : '' }` }
			>
				<TextareaControl
					label={ __( 'Short Bio', 'speekr' ) }
					value={ meta._speekr_bio_short ?? '' }
					onChange={ ( v ) => setMeta( { ...meta, _speekr_bio_short: v } ) }
					rows={ 3 }
					help={ __( 'Short Bio for program intros. Use the post content area below for your full bio.', 'speekr' ) }
				/>
			</PluginDocumentSettingPanel>

			{ /* ----------------------------------------------------------------
			     Panel 3 — Social Links
			     ---------------------------------------------------------------- */ }
			<PluginDocumentSettingPanel
				name="speekr-speaker-social"
				title={ __( 'Social Links', 'speekr' ) }
				icon={ share }
				className={ `speekr-panel-social${ socialLinks.length > 0 ? ' is-filled' : '' }` }
			>
				{ socialLinks.length > 0 && (
					<ul className="speekr-social-links-list">
						{ socialLinks.map( ( entry, index ) => (
							<li key={ index }>
								<span>
									{ entry.platform === 'other' && entry.label
										? entry.label
										: entry.platform }
									{ ' \u2014 ' }
									{ entry.url }
								</span>
								<Button
									isDestructive
									variant="secondary"
									onClick={ () => removeLink( index ) }
									aria-label={ __( 'Remove link', 'speekr' ) }
								>
									{ '\u00d7' }
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
				icon={ formatListBullets }
				className={ `speekr-panel-rider${ Object.values( rider ).some( Boolean ) ? ' is-filled' : '' }` }
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
