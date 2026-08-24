<?php
namespace VentraConnect\SocialLogin\Diagnostics;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Report {
    /**
     * Build a full diagnostics report payload.
     *
     * @param int         $events_limit
     * @param string|null $events_type
     * @return array {
     *   @type array  $snapshot     Site / plugin snapshot.
     *   @type array  $checks       Derived diagnostics checks.
     *   @type array  $events       Recent diagnostic events.
     *   @type string $support_blob Plain-text blob suitable for support tickets.
     * }
     */
    public static function build_full_report( int $events_limit = 20, ?string $events_type = null ): array {
        $result = Tools::run_full();

        $snapshot = isset( $result['snapshot'] ) && is_array( $result['snapshot'] ) ? $result['snapshot'] : [];
        $checks   = isset( $result['checks'] ) && is_array( $result['checks'] ) ? $result['checks'] : [];

        $events = Logs::get_recent_events( $events_limit, $events_type );

        $blob = Export::build_support_blob( $snapshot, $events );

        return [
            'snapshot'     => $snapshot,
            'checks'       => $checks,
            'events'       => $events,
            'support_blob' => $blob,
        ];
    }
}

