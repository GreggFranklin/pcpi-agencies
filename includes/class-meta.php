<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers post meta, exposes them via the REST API, and renders
 * the Agency Details meta box in the wp-admin post editor.
 *
 * All meta keys from both original plugins are preserved so existing
 * data and any front-end management plugins continue to work unchanged.
 *
 * Meta keys
 * ─────────
 *  _pcpi_address  – full multi-line mailing address (primary)
 *  _pcpi_city     – city  (back-compat; managed by front-end plugin)
 *  _pcpi_state    – state (back-compat; managed by front-end plugin)
 *  _pcpi_phone    – phone number
 *  _pcpi_website  – website URL (back-compat; managed by front-end plugin)
 *  _pcpi_logo_id  – attachment ID for the agency logo
 *
 * Note: The meta box intentionally exposes only address, phone, and logo.
 * City, state, and website are registered for REST but not shown here so
 * the front-end management plugin remains their sole editor.
 */
class PCPI_Agencies_Meta {

    /** All string keys — registered for REST/back-compat regardless of UI visibility. */
    private array $string_fields = [ 'address', 'city', 'state', 'phone', 'website' ];

    public function init(): void {
        add_action( 'init',                  [ $this, 'register_meta'        ] );
        add_action( 'rest_api_init',         [ $this, 'register_rest_fields' ] );
        add_action( 'add_meta_boxes',        [ $this, 'add_meta_box'         ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_meta_assets'  ] );
        add_action( 'save_post_pcpi_agency', [ $this, 'save_meta'            ] );
    }

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    public function register_meta(): void {
        foreach ( $this->string_fields as $field ) {
            register_post_meta( 'pcpi_agency', "_pcpi_{$field}", [
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_textarea_field',
                'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
                'default'           => '',
            ] );
        }

        register_post_meta( 'pcpi_agency', '_pcpi_logo_id', [
            'type'          => 'integer',
            'single'        => true,
            'show_in_rest'  => true,
            'auth_callback' => fn() => current_user_can( 'edit_posts' ),
            'default'       => 0,
        ] );
    }

    public function register_rest_fields(): void {
        // These top-level REST fields are read directly by the block editor JS
        // (e.g. selectedAgency.pcpi_address). They must be top-level — not nested
        // under .meta — because the core data store meta exposure requires
        // auth_callback to pass, which is not guaranteed in the editor context.
        register_rest_field( 'pcpi_agency', 'pcpi_address', [
            'get_callback' => fn( array $post ): string => (string) get_post_meta( $post['id'], '_pcpi_address', true ),
            'schema'       => [ 'type' => 'string', 'description' => 'Mailing address.' ],
        ] );

        register_rest_field( 'pcpi_agency', 'pcpi_phone', [
            'get_callback' => fn( array $post ): string => (string) get_post_meta( $post['id'], '_pcpi_phone', true ),
            'schema'       => [ 'type' => 'string', 'description' => 'Phone number.' ],
        ] );

        register_rest_field( 'pcpi_agency', 'pcpi_website', [
            'get_callback' => fn( array $post ): string => (string) get_post_meta( $post['id'], '_pcpi_website', true ),
            'schema'       => [ 'type' => 'string', 'description' => 'Website URL.' ],
        ] );

        register_rest_field( 'pcpi_agency', 'pcpi_logo_url', [
            'get_callback' => function ( array $post ): string {
                $logo_id = (int) get_post_meta( $post['id'], '_pcpi_logo_id', true );
                if ( ! $logo_id ) return '';
                return wp_get_attachment_image_url( $logo_id, 'medium' ) ?: '';
            },
            'schema' => [
                'type'        => 'string',
                'description' => 'Resolved medium-size logo URL (read-only).',
                'readonly'    => true,
            ],
        ] );
    }

    // -------------------------------------------------------------------------
    // Admin meta box
    // -------------------------------------------------------------------------

    public function add_meta_box(): void {
        add_meta_box(
            'pcpi_agency_details',
            'Agency Details',
            [ $this, 'render_meta_box' ],
            'pcpi_agency',
            'normal',
            'high'
        );
    }

    /**
     * Enqueue meta box styles/scripts only on the Agency edit screen.
     * Using wp_add_inline_style avoids a separate HTTP request.
     */
    public function enqueue_meta_assets( string $hook ): void {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
        if ( get_current_screen()->post_type !== 'pcpi_agency' ) return;

        // Ensure the WP media uploader is available
        wp_enqueue_media();

        // Register a minimal stub so we can attach inline script cleanly
        wp_register_script( 'pcpi-agency-meta', false, [ 'jquery', 'media-upload' ], PCPI_AGENCIES_VERSION, true );
        wp_enqueue_script( 'pcpi-agency-meta' );

        wp_add_inline_style( 'wp-admin', '
            #pcpi_agency_details { padding: 4px 0; }
            #pcpi_agency_details .pcpi-field { margin-bottom: 16px; }
            #pcpi_agency_details .pcpi-field label { display:block; font-weight:600; margin-bottom:4px; }
            #pcpi_agency_details .pcpi-field input[type=text],
            #pcpi_agency_details .pcpi-field textarea { width:100%; }
            #pcpi_agency_details .pcpi-field textarea { height:80px; resize:vertical; }
            #pcpi-logo-preview { display:block; max-width:150px; height:auto; margin-bottom:8px; }
            #pcpi-logo-preview.pcpi-hidden { display:none; }
        ' );

        wp_add_inline_script( 'pcpi-agency-meta', '
( function( $ ) {
    var frame;
    $( "#pcpi-logo-upload" ).on( "click", function( e ) {
        e.preventDefault();
        if ( frame ) { frame.open(); return; }
        frame = wp.media( { title: "Select Agency Logo", button: { text: "Use this logo" }, multiple: false } );
        frame.on( "select", function() {
            var att = frame.state().get( "selection" ).first().toJSON();
            var url = ( att.sizes && att.sizes.medium ) ? att.sizes.medium.url : att.url;
            $( "#pcpi_logo_id" ).val( att.id );
            $( "#pcpi-logo-preview" ).attr( "src", url ).removeClass( "pcpi-hidden" );
            $( "#pcpi-logo-remove" ).show();
        } );
        frame.open();
    } );
    $( "#pcpi-logo-remove" ).on( "click", function( e ) {
        e.preventDefault();
        $( "#pcpi_logo_id" ).val( "" );
        $( "#pcpi-logo-preview" ).attr( "src", "" ).addClass( "pcpi-hidden" );
        $( this ).hide();
    } );
} )( jQuery );
        ' );
    }

    public function render_meta_box( WP_Post $post ): void {
        wp_nonce_field( 'pcpi_agency_save_meta', 'pcpi_agency_meta_nonce' );

        $address  = (string) get_post_meta( $post->ID, '_pcpi_address', true );
        $phone    = (string) get_post_meta( $post->ID, '_pcpi_phone',   true );
        $logo_id  = (int)    get_post_meta( $post->ID, '_pcpi_logo_id', true );
        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        ?>
        <div class="pcpi-field">
            <label for="pcpi_address">Address</label>
            <textarea id="pcpi_address" name="pcpi_address"><?php echo esc_textarea( $address ); ?></textarea>
        </div>

        <div class="pcpi-field">
            <label for="pcpi_phone">Phone</label>
            <input type="text" id="pcpi_phone" name="pcpi_phone" value="<?php echo esc_attr( $phone ); ?>">
        </div>

        <div class="pcpi-field">
            <label>Agency Logo</label>
            <img id="pcpi-logo-preview"
                 src="<?php echo esc_url( $logo_url ); ?>"
                 class="<?php echo $logo_url ? '' : 'pcpi-hidden'; ?>">
            <input type="hidden" id="pcpi_logo_id" name="pcpi_logo_id" value="<?php echo esc_attr( $logo_id ?: '' ); ?>">
            <button type="button" class="button" id="pcpi-logo-upload">Upload Logo</button>
            <button type="button" class="button" id="pcpi-logo-remove"
                <?php echo $logo_url ? '' : 'style="display:none"'; ?>>Remove Logo</button>
        </div>

        <?php
    }

    // -------------------------------------------------------------------------
    // Save
    // -------------------------------------------------------------------------

    /**
     * Persist meta when the agency post is saved.
     *
     * Only saves address, phone, and logo — the three fields this meta box owns.
     * City, state, and website are intentionally left untouched so the
     * front-end management plugin retains sole control over them.
     */
    public function save_meta( int $post_id ): void {
        // Nonce, autosave, and capability checks
        if ( empty( $_POST['pcpi_agency_meta_nonce'] )
            || ! wp_verify_nonce( $_POST['pcpi_agency_meta_nonce'], 'pcpi_agency_save_meta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta(
            $post_id,
            '_pcpi_address',
            sanitize_textarea_field( wp_unslash( $_POST['pcpi_address'] ?? '' ) )
        );

        update_post_meta(
            $post_id,
            '_pcpi_phone',
            sanitize_text_field( wp_unslash( $_POST['pcpi_phone'] ?? '' ) )
        );

        $logo_id = absint( $_POST['pcpi_logo_id'] ?? 0 );
        if ( $logo_id ) {
            update_post_meta( $post_id, '_pcpi_logo_id', $logo_id );
        } else {
            delete_post_meta( $post_id, '_pcpi_logo_id' );
        }
    }
}
