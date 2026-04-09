<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PCPI_Agencies_Meta {

    private array $string_fields = [ 'address', 'city', 'state', 'phone', 'website', 'contact', 'email' ];

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

    $map = [
        'address'  => 'sanitize_textarea_field',
        'city'     => 'sanitize_text_field',
        'state'    => 'sanitize_text_field',
        'phone'    => 'sanitize_text_field',
        'website'  => 'esc_url_raw',
        'contact'  => 'sanitize_text_field',
        'email'    => 'sanitize_email',
    ];

    foreach ( $map as $field => $sanitize ) {
        register_post_meta( 'pcpi_agency', "_pcpi_{$field}", [
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => $sanitize,
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

        register_rest_field( 'pcpi_agency', 'pcpi_address', [
            'get_callback' => fn( array $post ): string => (string) get_post_meta( $post['id'], '_pcpi_address', true ),
            'schema'       => [ 'type' => 'string' ],
        ] );

        register_rest_field( 'pcpi_agency', 'pcpi_phone', [
            'get_callback' => fn( array $post ): string => (string) get_post_meta( $post['id'], '_pcpi_phone', true ),
            'schema'       => [ 'type' => 'string' ],
        ] );

        register_rest_field( 'pcpi_agency', 'pcpi_website', [
            'get_callback' => fn( array $post ): string => (string) get_post_meta( $post['id'], '_pcpi_website', true ),
            'schema'       => [ 'type' => 'string' ],
        ] );

        register_rest_field( 'pcpi_agency', 'pcpi_logo_url', [
            'get_callback' => function ( array $post ): string {
                $logo_id = (int) get_post_meta( $post['id'], '_pcpi_logo_id', true );
                return $logo_id ? ( wp_get_attachment_image_url( $logo_id, 'medium' ) ?: '' ) : '';
            },
            'schema' => [ 'type' => 'string', 'readonly' => true ],
        ] );

        register_rest_field( 'pcpi_agency', 'pcpi_contact', [
            'get_callback' => fn( array $post ): string => (string) get_post_meta( $post['id'], '_pcpi_contact', true ),
            'schema'       => [ 'type' => 'string' ],
        ] );

        register_rest_field( 'pcpi_agency', 'pcpi_email', [
            'get_callback' => fn( array $post ): string => (string) get_post_meta( $post['id'], '_pcpi_email', true ),
            'schema'       => [ 'type' => 'string' ],
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

    public function enqueue_meta_assets( string $hook ): void {

        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;

        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'pcpi_agency' ) return;

        wp_enqueue_media();

        wp_enqueue_style(
            'pcpi-agency-admin',
            PCPI_AGENCIES_URL . 'assets/css/admin.css',
            [],
            PCPI_AGENCIES_VERSION
        );

        wp_enqueue_script(
            'pcpi-agency-admin',
            PCPI_AGENCIES_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            PCPI_AGENCIES_VERSION,
            true
        );
    }

    public function render_meta_box( WP_Post $post ): void {

        wp_nonce_field( 'pcpi_agency_save_meta', 'pcpi_agency_meta_nonce' );

        $address  = (string) get_post_meta( $post->ID, '_pcpi_address', true );
        $contact  = (string) get_post_meta( $post->ID, '_pcpi_contact', true );
        $email    = (string) get_post_meta( $post->ID, '_pcpi_email', true );
        $phone    = (string) get_post_meta( $post->ID, '_pcpi_phone', true );
        $logo_id  = (int)    get_post_meta( $post->ID, '_pcpi_logo_id', true );
        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        ?>

        <div class="pcpi-field">
            <label for="pcpi_contact">Agency Contact</label>
            <input type="text" id="pcpi_contact" name="pcpi_contact" value="<?php echo esc_attr( $contact ); ?>">
        </div>

        <div class="pcpi-field">
            <label for="pcpi_email">Email Address</label>
            <input type="email" id="pcpi_email" name="pcpi_email" value="<?php echo esc_attr( $email ); ?>">
        </div>

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
                <?php echo $logo_url ? '' : 'style="display:none"'; ?>>
                Remove Logo
            </button>
        </div>

        <?php
    }

    // -------------------------------------------------------------------------
    // Save
    // -------------------------------------------------------------------------

    public function save_meta( int $post_id ): void {

        if (
            empty( $_POST['pcpi_agency_meta_nonce'] ) ||
            ! wp_verify_nonce( $_POST['pcpi_agency_meta_nonce'], 'pcpi_agency_save_meta' )
        ) return;

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta( $post_id, '_pcpi_contact', sanitize_text_field( wp_unslash( $_POST['pcpi_contact'] ?? '' ) ) );
        update_post_meta( $post_id, '_pcpi_email', sanitize_email( wp_unslash( $_POST['pcpi_email'] ?? '' ) ) );
        update_post_meta( $post_id, '_pcpi_address', sanitize_textarea_field( wp_unslash( $_POST['pcpi_address'] ?? '' ) ) );
        update_post_meta( $post_id, '_pcpi_phone', sanitize_text_field( wp_unslash( $_POST['pcpi_phone'] ?? '' ) ) );

        $logo_id = absint( $_POST['pcpi_logo_id'] ?? 0 );

        if ( $logo_id ) {
            update_post_meta( $post_id, '_pcpi_logo_id', $logo_id );
        } else {
            delete_post_meta( $post_id, '_pcpi_logo_id' );
        }
    }
}