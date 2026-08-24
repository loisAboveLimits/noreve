<?php
namespace VentraConnect\SocialLogin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper for rendering Pro-only preview sections in the free plugin.
 */
class Pro_Preview {
    /**
     * Render a Pro-only notice and wrap a layout callback in a disabled preview container.
     *
     * @param array<string,string> $args {
     *     @type string $title       Upsell title.
     *     @type string $description Short description text.
     *     @type string $upgrade_url Upgrade URL.
     * }
     * @param callable              $layout_callback Callback that echoes the layout HTML.
     */
    public static function render( array $args, callable $layout_callback ): void {
        $title       = isset( $args['title'] ) ? $args['title'] : '';
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $upgrade_url = isset( $args['upgrade_url'] ) ? $args['upgrade_url'] : '';

        if ( '' === $upgrade_url ) {
            $upgrade_url = 'https://wpventra.com/pricing/';
        }
        ?>
        <div class="vcs-pro-notice">
            <?php if ( '' !== $title ) : ?>
                <h2><?php echo esc_html( $title ); ?></h2>
            <?php endif; ?>

            <?php if ( '' !== $description ) : ?>
                <p><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>

            <p>
                <a href="<?php echo esc_url( $upgrade_url ); ?>"
                   class="button button-primary"
                   target="_blank"
                   rel="noopener noreferrer">
                    <?php esc_html_e( 'Upgrade to Pro', 'ventraconnect-social-login' ); ?>
                </a>
            </p>
        </div>

        <div class="vcs-pro-preview">
            <?php
            // Layout is rendered with disabled/no-name fields via the renderer.
            call_user_func( $layout_callback );
            ?>
        </div>
        <?php
    }
}

