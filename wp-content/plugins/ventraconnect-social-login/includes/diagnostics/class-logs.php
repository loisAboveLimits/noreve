<?php
namespace VentraConnect\SocialLogin\Diagnostics;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Logs {
    /**
     * Get recent diagnostic events (stub for now).
     *
     * @param int         $limit
     * @param string|null $type
     * @return array
     */
    public static function get_recent_events( int $limit = 20, ?string $type = null ): array {
        $events = [];

        $events = apply_filters(
            'ventraconnect_sl_diagnostics_events',
            $events,
            $limit,
            $type
        );

        return $events;
    }
}

