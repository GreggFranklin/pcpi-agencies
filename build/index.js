( function ( blocks, element, blockEditor, components, data, i18n ) {

    const { registerBlockType }                             = blocks;
    const { createElement: el, Fragment }                  = element;
    const { useBlockProps, InspectorControls }             = blockEditor;
    const { PanelBody, SelectControl, Placeholder, Spinner } = components;
    const { useSelect }                                    = data;
    const { decodeEntities }                               = window.wp.htmlEntities;
    const { __ }                                           = i18n;

    /**
     * Normalise a raw address string into an array of display lines.
     * Mirrors the PHP pcpi_parse_address_lines() logic in helpers.php.
     *
     * @param  {string} raw  Newline-delimited address string.
     * @return {string[]}    Merged address lines.
     */
    function parseAddressLines( raw ) {
        if ( ! raw ) return [];
        const lines  = raw.split( '\n' ).map( s => s.trim() ).filter( Boolean );
        const merged = [];
        lines.forEach( function( line ) {
            if ( /^\d{5}(-\d{4})?$/.test( line ) && merged.length ) {
                merged[ merged.length - 1 ] += ' ' + line;
            } else {
                merged.push( line );
            }
        } );
        return merged;
    }

    registerBlockType( 'pcpi/agency-card', {

        title       : __( 'Agency Card', 'pcpi' ),
        description : __( 'Display an agency logo, name, address, and phone number.', 'pcpi' ),
        icon        : 'building',
        // 'widgets' is the correct category since WP 5.8; 'common' was its predecessor
        category    : 'widgets',

        attributes: {
            agencyId: {
                type    : 'number',
                default : 0,
            },
        },

        edit: function( props ) {

            const { attributes, setAttributes } = props;
            const { agencyId }                  = attributes;
            const blockProps = useBlockProps( { className: 'pcpi-agency-card-editor-wrap' } );

            // Fetch all agencies for the dropdown (capped at 100; increase if needed)
            const agencies = useSelect( function( select ) {
                return select( 'core' ).getEntityRecords( 'postType', 'pcpi_agency', {
                    per_page : 100,
                    _fields  : 'id,title',
                    orderby  : 'title',
                    order    : 'asc',
                } );
            }, [] );

            // Fetch the selected agency's full data for the live preview
            const selectedAgency = useSelect( function( select ) {
                if ( ! agencyId ) return null;
                return select( 'core' ).getEntityRecord( 'postType', 'pcpi_agency', agencyId );
            }, [ agencyId ] );

            // Build dropdown options — sorted alphabetically (API handles ordering above)
            const agencyOptions = [
                { label: __( '— Select an Agency —', 'pcpi' ), value: 0 },
            ];
            if ( agencies ) {
                agencies.forEach( function( agency ) {
                    agencyOptions.push( {
                        label : decodeEntities( agency.title.rendered || agency.title.raw || '(no title)' ),
                        value : agency.id,
                    } );
                } );
            }

            // Inspector sidebar
            const inspectorControls = el(
                InspectorControls, null,
                el( PanelBody, { title: __( 'Agency Settings', 'pcpi' ), initialOpen: true },
                    el( SelectControl, {
                        label    : __( 'Select Agency', 'pcpi' ),
                        value    : agencyId,
                        options  : agencyOptions,
                        onChange : function( val ) {
                            setAttributes( { agencyId: parseInt( val, 10 ) } );
                        },
                    } )
                )
            );

            // ── States ────────────────────────────────────────────────────────

            if ( ! agencyId ) {
                return el( Fragment, null,
                    inspectorControls,
                    el( 'div', blockProps,
                        el( Placeholder, {
                            icon         : 'building',
                            label        : __( 'Agency Card', 'pcpi' ),
                            instructions : __( 'Select an agency from the block settings panel on the right.', 'pcpi' ),
                        } )
                    )
                );
            }

            if ( ! selectedAgency ) {
                return el( Fragment, null, inspectorControls, el( 'div', blockProps, el( Spinner ) ) );
            }

            // ── Live preview ──────────────────────────────────────────────────

            const name    = selectedAgency.title
                ? decodeEntities( selectedAgency.title.rendered || selectedAgency.title.raw )
                : '';
            // Read from top-level REST fields registered via register_rest_field()
            // NOT from .meta — see class-meta.php register_rest_fields() for why
            const address = selectedAgency.pcpi_address  || '';
            const phone   = selectedAgency.pcpi_phone    || '';
            const website = selectedAgency.pcpi_website  || '';
            const logoUrl = selectedAgency.pcpi_logo_url || '';

            const addressEls = parseAddressLines( address ).map( function( line, i ) {
                return el( 'span', { key: i }, line );
            } );

            return el( Fragment, null,
                inspectorControls,
                el( 'div', blockProps,
                    el( 'div', { className: 'pcpi-agency-card' },

                        logoUrl ? el( 'div', { className: 'pcpi-agency-card__logo-wrap' },
                            el( 'img', { src: logoUrl, alt: name + ' logo', className: 'pcpi-agency-card__logo' } )
                        ) : null,

                        el( 'div', { className: 'pcpi-agency-card__info' },
                            name          ? el( 'p', { className: 'pcpi-agency-card__name' },    name )          : null,
                            addressEls.length ? el( 'p', { className: 'pcpi-agency-card__address' }, ...addressEls ) : null,
                            phone         ? el( 'p', { className: 'pcpi-agency-card__phone' },   'Phone: ' + phone ) : null,
                            website       ? el( 'p', { className: 'pcpi-agency-card__website' },
                                el( 'a', { href: website, target: '_blank', rel: 'noopener noreferrer' }, 'Visit Website' )
                            ) : null
                        )
                    )
                )
            );
        },

        // Server-side rendered — save() must return null
        save: function() { return null; },

    } );

} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.data,
    window.wp.i18n
);
