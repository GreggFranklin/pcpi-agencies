<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Normalise a raw address string for display.
 *
 * Trims blank lines, then merges any line that contains only a bare
 * 5- or 9-digit ZIP code onto the end of the preceding line.
 *
 * @param  string   $raw  Raw address text (newline-delimited).
 * @return string[]       Ordered array of merged address lines (unescaped).
 *                        Join with implode( '<br>', ... ) for HTML or ' ' for plain text.
 */
function pcpi_parse_address_lines( string $raw ): array {
    if ( ! trim( $raw ) ) return [];

    $lines  = array_values( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) );
    $merged = [];

    foreach ( $lines as $line ) {
        if ( preg_match( '/^\d{5}(-\d{4})?$/', $line ) && ! empty( $merged ) ) {
            $merged[ count( $merged ) - 1 ] .= ' ' . $line;
        } else {
            $merged[] = $line;
        }
    }

    return $merged;
}

/**
 * Render a single agency card as an HTML string.
 *
 * Used by the block render callback and the [pcpi_agency] shortcode.
 *
 * @param  int $id  Agency post ID.
 * @return string   HTML output, or empty string on failure.
 */
function pcpi_render_agency_card( int $id ): string {
    $post = get_post( $id );
    if ( ! $post || $post->post_type !== 'pcpi_agency' ) return '';

    $name    = get_the_title( $post );
    $address = (string) get_post_meta( $id, '_pcpi_address', true );
    $phone   = (string) get_post_meta( $id, '_pcpi_phone',   true );
    $website = (string) get_post_meta( $id, '_pcpi_website', true );
    $logo_id = (int)    get_post_meta( $id, '_pcpi_logo_id', true );

    $logo = $logo_id
        ? wp_get_attachment_image( $logo_id, 'medium', false, [
            'class' => 'pcpi-agency-card__logo',
            'alt'   => esc_attr( $name . ' logo' ),
        ] )
        : '';

    $address_lines = pcpi_parse_address_lines( $address );
    $address_html  = implode( '<br>', array_map( 'esc_html', $address_lines ) );

    ob_start();
    ?>
    <div class="pcpi-agency-card">
        <?php if ( $logo ) : ?>
            <div class="pcpi-agency-card__logo-wrap">
                <?php echo $logo; ?>
            </div>
        <?php endif; ?>
        <div class="pcpi-agency-card__info">
            <?php if ( $name ) : ?>
                <p class="pcpi-agency-card__name"><?php echo esc_html( $name ); ?></p>
            <?php endif; ?>
            <?php if ( $address_html ) : ?>
                <p class="pcpi-agency-card__address"><?php echo $address_html; ?></p>
            <?php endif; ?>
            <?php if ( $phone ) : ?>
                <p class="pcpi-agency-card__phone">Phone: <?php echo esc_html( $phone ); ?></p>
            <?php endif; ?>
            <?php if ( $website ) : ?>
                <p class="pcpi-agency-card__website">
                    <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer">
                        Visit Website
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Shortcode: [pcpi_agency id="123"]
 *
 * Renders an agency card inline. Returns empty string for invalid IDs.
 */
add_shortcode( 'pcpi_agency', function ( $atts ): string {
    $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'pcpi_agency' );
    return pcpi_render_agency_card( (int) $atts['id'] );
} );
